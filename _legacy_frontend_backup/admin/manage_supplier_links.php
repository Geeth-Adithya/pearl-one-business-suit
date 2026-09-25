<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

// Handle link creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_link'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $supplier_name = trim($_POST['supplier_name']);
    $admin_id = $_SESSION['user_id'];

    if (empty($supplier_name)) {
        $_SESSION['error_message'] = 'Please select a supplier.';
    } else {
        // Check if a link for this supplier already exists for this admin
        $checkStmt = $pdo->prepare("SELECT id FROM Public_Links WHERE supplier_name = ? AND admin_id = ?");
        $checkStmt->execute([$supplier_name, $admin_id]);
        if ($checkStmt->fetch()) {
            $_SESSION['error_message'] = 'A shareable link for this supplier already exists.';
        } else {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO Public_Links (token, supplier_name, admin_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$token, $supplier_name, $admin_id])) {
                $_SESSION['success_message'] = 'Shareable link created successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to create link.';
            }
        }
    }
    header('Location: manage_supplier_links.php');
    exit;
}

// Handle link deletion
if (isset($_GET['delete'])) {
    $link_id_to_delete = (int) $_GET['delete'];
    $admin_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM Public_Links WHERE id = ? AND admin_id = ?");
    if ($stmt->execute([$link_id_to_delete, $admin_id])) {
        $_SESSION['success_message'] = 'Link deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to delete link.';
    }
    header('Location: manage_supplier_links.php');
    exit;
}

// Fetch distinct suppliers for the current admin
$supplierQuery = "SELECT DISTINCT supplier_name FROM Products WHERE created_by_admin_id = ? AND supplier_name IS NOT NULL AND supplier_name != '' ORDER BY supplier_name";
$supplierStmt = $pdo->prepare($supplierQuery);
$supplierStmt->execute([$_SESSION['user_id']]);
$suppliers = $supplierStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch existing links for the current admin
$linksQuery = "SELECT * FROM Public_Links WHERE admin_id = ? ORDER BY created_at DESC";
$linksStmt = $pdo->prepare($linksQuery);
$linksStmt->execute([$_SESSION['user_id']]);
$links = $linksStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Share Products with Suppliers</h1>
    </div>
</div>

<!-- Create New Link Form -->
<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Generate New Supplier Link</h3>
    </div>
    <form action="manage_supplier_links.php" method="POST" class="p-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="supplier_name"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supplier</label>
                <select name="supplier_name" id="supplier_name" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                    <option value="">-- Select a Supplier --</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= htmlspecialchars($supplier) ?>">
                            <?= htmlspecialchars($supplier) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2 flex justify-start">
                <button type="submit" name="create_link" title="Generate Link"
                    class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                    <ion-icon name="link-outline"></ion-icon> <span>Generate Link</span>
                </button>
            </div>
        </div>
        <?php if (empty($suppliers)): ?>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">You have no products with an assigned supplier. Please
                <a href="products.php" class="text-brand-blue hover:underline">edit a product</a> to add a supplier name
                first.
            </p>
        <?php endif; ?>
    </form>
</div>

<!-- Existing Links List -->
<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Shared Links</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Supplier</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Shareable Link</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($links)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No links
                            generated yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <?php $url = BASE_URL . '/public_needs.php?token=' . $link['token']; ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($link['supplier_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly value="<?= htmlspecialchars($url) ?>"
                                        class="w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md text-sm">
                                    <button onclick="copyToClipboard('<?= htmlspecialchars($url) ?>', this)"
                                        class="p-2 rounded-md bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500"
                                        title="Copy link">
                                        <ion-icon name="copy-outline"></ion-icon>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank"
                                    class="text-brand-blue hover:text-brand-blueDark mr-3">View</a>
                                <a href="manage_supplier_links.php?delete=<?= $link['id'] ?>"
                                    class="text-red-600 hover:text-red-900 confirm-delete-link"
                                    data-confirm-message="Are you sure you want to delete this link? The supplier will no longer be able to access it.">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(function () {
            const originalIcon = button.innerHTML;
            button.innerHTML = '<ion-icon name="checkmark-outline" class="text-green-500"></ion-icon>';
            setTimeout(() => {
                button.innerHTML = originalIcon;
            }, 2000);
        }, function (err) {
            alert('Could not copy text: ', err);
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>