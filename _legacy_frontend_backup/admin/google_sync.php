<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_stock');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

$admin_webhook_key = 'google_sheet_webhook_admin_' . intval($_SESSION['user_id']);

// Handle saving Webhook URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_url') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $url = trim($_POST['webhook_url']);

    // Check if setting exists for this admin
    $stmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
    $stmt->execute([$admin_webhook_key]);
    if ($stmt->fetch()) {
        $update = $pdo->prepare("UPDATE Settings SET setting_value = ? WHERE setting_key = ?");
        $update->execute([$url, $admin_webhook_key]);
    } else {
        $insert = $pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?)");
        $insert->execute([$admin_webhook_key, $url]);
    }
    $success = "Webhook URL saved successfully.";
}

// Fetch current URL for this admin
$webhook_url = '';
$stmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
$stmt->execute([$admin_webhook_key]);
if ($row = $stmt->fetch()) {
    $webhook_url = $row['setting_value'];
}

// Handle Sync from Sheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_products') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    if (empty($webhook_url)) {
        $error = "Please configure the Webhook URL first.";
    } else {
        // Fetch data from the webhook
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200 && $response) {
            $sheet_data = json_decode($response, true);
            if (is_array($sheet_data)) {
                $pdo->beginTransaction();
                try {
                    $stats = ['added' => 0, 'updated' => 0, 'exported' => 0, 'skipped' => 0];
                    $syncing_admin_id = $_SESSION['user_id'];

                    // 1. Get all products from Sheet and DB, map by item_code
                    $sheet_products_map = [];
                    foreach ($sheet_data as $row) {
                        if (!empty($row['item_code'])) {
                            $sheet_products_map[$row['item_code']] = $row;
                        }
                    }

                    $db_products_map = [];
                    $db_query = "SELECT * FROM Products";
                    $db_params = [];
                    // Admins can only sync against their own products. Superadmins sync against all.
                    if ($_SESSION['role'] === 'admin') {
                        $db_query .= " WHERE created_by_admin_id = ?";
                        $db_params[] = $syncing_admin_id;
                    }
                    $db_products_stmt = $pdo->prepare($db_query);
                    $db_products_stmt->execute($db_params);
                    foreach ($db_products_stmt as $row) {
                        $db_products_map[$row['item_code']] = $row;
                    }

                    // Detect cross-admin item_code conflicts before inserting new rows
                    $itemCodeOwners = [];
                    if (!empty($sheet_products_map)) {
                        $placeholders = implode(',', array_fill(0, count($sheet_products_map), '?'));
                        $ownerQuery = "SELECT item_code, created_by_admin_id FROM Products WHERE item_code IN ($placeholders)";
                        $ownerStmt = $pdo->prepare($ownerQuery);
                        $ownerStmt->execute(array_keys($sheet_products_map));
                        foreach ($ownerStmt as $ownerRow) {
                            $itemCodeOwners[$ownerRow['item_code']] = $ownerRow['created_by_admin_id'];
                        }
                    }

                    // Prepare DB statements
                    $insert_product_stmt = $pdo->prepare("INSERT INTO Products (item_code, name, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($_SESSION['role'] === 'admin') {
                        $update_product_stmt = $pdo->prepare("UPDATE Products SET name=?, attribute=?, type=?, purchasing_price=?, margin_percent=?, selling_price=?, unit=?, description=?, supplier_name=? WHERE item_code=? AND created_by_admin_id = ?");
                    } else {
                        $update_product_stmt = $pdo->prepare("UPDATE Products SET name=?, attribute=?, type=?, purchasing_price=?, margin_percent=?, selling_price=?, unit=?, description=?, supplier_name=? WHERE item_code=?");
                    }
                    $delete_prod_cats_stmt = $pdo->prepare("DELETE FROM Product_Categories WHERE product_id = ?");
                    $catLinkStmt = $pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");

                    // 2. Process Adds and Updates
                    foreach ($sheet_products_map as $item_code => $sheet_product) {
                        if (empty($sheet_product['name'])) {
                            $stats['skipped']++;
                            continue;
                        }

                        $params = [
                            $sheet_product['name'] ?? '',
                            $sheet_product['attribute'] ?? '',
                            $sheet_product['type'] ?? '',
                            empty($sheet_product['purchasing_price']) ? null : $sheet_product['purchasing_price'],
                            empty($sheet_product['margin_percent']) ? null : $sheet_product['margin_percent'],
                            empty($sheet_product['selling_price']) ? null : $sheet_product['selling_price'],
                            $sheet_product['unit'] ?? '',
                            $sheet_product['description'] ?? '',
                            $sheet_product['supplier_name'] ?? ''
                        ];

                        if (isset($db_products_map[$item_code])) {
                            // UPDATE existing product
                            // Ownership is already confirmed by the initial DB query for admins. Superadmin can update anything.
                            if ($_SESSION['role'] === 'admin') {
                                $update_product_stmt->execute(array_merge($params, [$item_code, $syncing_admin_id]));
                            } else {
                                $update_product_stmt->execute(array_merge($params, [$item_code]));
                            }
                            $product_id = $db_products_map[$item_code]['id'];
                            $stats['updated']++;
                        } else {
                            // Block insertion if the item code already exists under another admin.
                            if (isset($itemCodeOwners[$item_code]) && $itemCodeOwners[$item_code] !== $syncing_admin_id) {
                                throw new Exception("Sync blocked: the item_code '$item_code' already belongs to another admin.");
                            }

                            // INSERT new product
                            // The new product will be owned by the admin running the sync.
                            $insert_product_stmt->execute(array_merge([$item_code], $params, [$syncing_admin_id]));
                            $product_id = $pdo->lastInsertId();
                            $stats['added']++;
                        }

                        // Sync categories for the product
                        $delete_prod_cats_stmt->execute([$product_id]);
                        if (!empty($sheet_product['category_id'])) {
                            $category_ids = explode(',', $sheet_product['category_id']);
                            foreach ($category_ids as $cat_id) {
                                if (is_numeric(trim($cat_id))) {
                                    $catLinkStmt->execute([$product_id, trim($cat_id)]);
                                }
                            }
                        }
                    }

                    $pdo->commit();

                    // Push any current local-only products back to the sheet so site additions are reflected in the sheet.
                    $localProductsStmt = $pdo->prepare("SELECT p.*, GROUP_CONCAT(pc.category_id SEPARATOR ',') AS category_ids FROM Products p LEFT JOIN Product_Categories pc ON p.id = pc.product_id WHERE p.created_by_admin_id = ? GROUP BY p.id");
                    $localProductsStmt->execute([$syncing_admin_id]);

                    foreach ($localProductsStmt as $db_product) {
                        $item_code = $db_product['item_code'];
                        if (!isset($sheet_products_map[$item_code])) {
                            $postData = [
                                'action' => 'add',
                                'data' => [
                                    'item_code' => $db_product['item_code'],
                                    'name' => $db_product['name'],
                                    'attribute' => $db_product['attribute'],
                                    'type' => $db_product['type'],
                                    'category_id' => $db_product['category_ids'] ?? '',
                                    'purchasing_price' => $db_product['purchasing_price'],
                                    'margin_percent' => $db_product['margin_percent'],
                                    'selling_price' => $db_product['selling_price'],
                                    'unit' => $db_product['unit'],
                                    'description' => $db_product['description'],
                                    'supplier_name' => $db_product['supplier_name']
                                ]
                            ];

                            $ch = curl_init($webhook_url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                            curl_exec($ch);
                            curl_close($ch);
                            $stats['exported']++;
                        }
                    }

                    $success = "Sync complete. Added: {$stats['added']}, Updated: {$stats['updated']}, Exported: {$stats['exported']}, Skipped: {$stats['skipped']}.";
                } catch (\Exception $e) {
                    $pdo->rollBack();
                    if ($e->getCode() == 23000) { // Integrity constraint violation (e.g., duplicate item_code)
                        $error = "Sync failed: An item code from your sheet already exists in the database under a different admin. No changes were made.";
                    } else {
                        $error = "An error occurred during sync: " . $e->getMessage();
                    }
                }
            } else {
                $error = "Failed to parse data from Google Sheets. Ensure the Webhook returns JSON.";
            }
        } else {
            $error = "Failed to fetch data from Webhook. HTTP Code: $http_code. Check your Webhook URL and script deployment permissions.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Google Sheets Integration</h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Setup Instructions -->
    <div class="bg-white dark:bg-gray-800 p-8 rounded-xl card-shadow">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">How to Setup</h2>
        <div class="prose dark:prose-invert text-sm text-gray-700 dark:text-gray-300 space-y-3">
            <p>To enable two-way sync (importing and automatic exporting), you need to deploy a Google Apps Script.</p>
            <ol class="list-decimal pl-5 space-y-1">
                <li>Create a new Google Sheet.</li>
                <li>Set the first row with headers like:
                    <code>item_code, name, attribute, type, category_id, purchasing_price, margin_percent, selling_price, unit, description, supplier_name</code>
                </li>
                <li>In Google Sheets, go to <strong>Extensions &gt; Apps Script</strong>.</li>
                <li>Paste the script provided in <code>google_apps_script.js</code> (you can download it).</li>
                <li>Click <strong>Deploy &gt; New deployment</strong>.</li>
                <li>Select Type: <strong>Web app</strong>. Execute as <strong>Me</strong>. Who has access:
                    <strong>Anyone</strong>.
                </li>
                <li>Copy the generated Web App URL and paste it in the form below.</li>
            </ol>
            <p class="mt-3">
                <a href="download_google_script.php"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-md shadow-sm">
                    <ion-icon name="download-outline"></ion-icon> Download google_apps_script.js
                </a>
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="space-y-8">
        <!-- Configuration Form -->
        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl card-shadow">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Configuration</h2>
            <form action="google_sync.php" method="POST" class="space-y-4 confirm-submit-form"
                data-confirm-title="Save Webhook URL"
                data-confirm-message="Are you sure you want to save or update this webhook URL?">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <input type="hidden" name="action" value="save_url">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Google Apps Script Webhook
                        URL</label>
                    <input type="url" name="webhook_url" value="<?= htmlspecialchars($webhook_url) ?>"
                        placeholder="https://script.google.com/macros/s/.../exec" required
                        class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                </div>
                <button type="submit" title="Save Webhook URL"
                    class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                    <ion-icon name="save-outline"></ion-icon> <span class="hidden sm:inline">Save Webhook
                        URL</span></button>
            </form>
        </div>

        <!-- Import Form -->
        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl card-shadow">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Sync from Google Sheet</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Merge product data between your site and Google
                Sheet. This will import new sheet products into the site, update existing ones, and export any new site
                products back to the sheet.</p>
            <form action="google_sync.php" method="POST" class="confirm-submit-form"
                data-confirm-title="Confirm Full Sync"
                data-confirm-message="This will overwrite your product data with the content from the Google Sheet. This action cannot be undone."
                data-confirm-button-text="Yes, run sync!">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <input type="hidden" name="action" value="sync_products">
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white p-2 sm:px-6 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2"
                    <?= empty($webhook_url) ? 'disabled title="Set Webhook URL first"' : '' ?>>
                    <ion-icon name="sync-outline"></ion-icon> <span class="hidden sm:inline">Run Full Sync</span>
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>