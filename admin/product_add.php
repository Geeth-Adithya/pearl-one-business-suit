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

// Handle AJAX Add Supplier
if (isset($_POST['ajax_add_supplier'])) {
    header('Content-Type: application/json');
    $name = trim($_POST['sup_name'] ?? '');
    $phone = trim($_POST['sup_phone'] ?? '');
    $email = trim($_POST['sup_email'] ?? '');
    $admin_id = $_SESSION['role'] === 'superadmin' ? null : $_SESSION['user_id'];
    
    if (!$name) {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit;
    }
    
    $check = $pdo->prepare("SELECT id FROM Suppliers WHERE name = ? AND (admin_id = ? OR admin_id IS NULL)");
    $check->execute([$name, $admin_id]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Supplier already exists']);
        exit;
    }
    
    $stmt = $pdo->prepare('INSERT INTO Suppliers (name, admin_id, phone, email, address, product_types) VALUES (?, ?, ?, ?, ?, ?)');
    if ($stmt->execute([$name, $admin_id, $phone, $email, '', ''])) {
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'name' => htmlspecialchars($name)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add supplier']);
    }
    exit;
}


// Ensure at least one category exists
$catCount = $pdo->query("SELECT COUNT(*) FROM Categories")->fetchColumn();
if ($catCount == 0) {
    $stmt = $pdo->prepare("INSERT INTO Categories (name, admin_id) VALUES (?, ?)");
    $stmt->execute(['General', $_SESSION['role'] === 'superadmin' ? null : $_SESSION['user_id']]);
}
$categoryQuery = "SELECT * FROM Categories";
$categoryParams = [];
if ($_SESSION['role'] !== 'superadmin') {
    $categoryQuery .= " WHERE admin_id = ? OR admin_id IS NULL";
    $categoryParams[] = $_SESSION['user_id'];
}
$categoryQuery .= " ORDER BY name";
$categoriesStmt = $pdo->prepare($categoryQuery);
$categoriesStmt->execute($categoryParams);
$categories = $categoriesStmt->fetchAll();

$supplierQuery = "SELECT id, name FROM Suppliers WHERE admin_id = ? ORDER BY name";
$supplierParams = [$_SESSION['user_id']];
$supplierStmt = $pdo->prepare($supplierQuery);
$supplierStmt->execute($supplierParams);
$suppliers = $supplierStmt->fetchAll();

$supplier_name = '';
$supplier_ids = [];
$variant_rows = [['item_code' => '', 'attribute' => '', 'purchasing_price' => '', 'margin_percent' => '', 'selling_price' => '']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $category_ids = $_POST['category_ids'] ?? [];
    $variant_rows = [];
    $item_codes = $_POST['item_code'] ?? [];
    $attributes = $_POST['attribute'] ?? [];
    $purchasing_prices = $_POST['purchasing_price'] ?? [];
    $margin_percents = $_POST['margin_percent'] ?? [];
    $selling_prices = $_POST['selling_price'] ?? [];
    $variant_count = max(count($item_codes), count($attributes), count($purchasing_prices), count($margin_percents), count($selling_prices));
    for ($index = 0; $index < $variant_count; $index++) {
        $purchasing_price = $purchasing_prices[$index] ?? '';
        $margin_percent = $margin_percents[$index] ?? '';
        $variant_rows[] = [
            'item_code' => trim($item_codes[$index] ?? ''),
            'attribute' => trim($attributes[$index] ?? ''),
            'purchasing_price' => $purchasing_price,
            'margin_percent' => $margin_percent,
            'selling_price' => is_numeric($selling_prices[$index] ?? null) ? number_format((float)$selling_prices[$index], 2, ".", "") : ""
        ];
    }
    if (!$variant_rows) {
        $variant_rows = [['item_code' => '', 'attribute' => '', 'purchasing_price' => '', 'margin_percent' => '', 'selling_price' => '']];
    }
        // Auto-generate base code if missing
    $prefix = 'ITM';
    if (!empty($category_ids)) {
        $first_cat_id = $category_ids[0];
        $stmt = $pdo->prepare("SELECT name FROM Categories WHERE id = ?");
        $stmt->execute([$first_cat_id]);
        $cat_name = $stmt->fetchColumn();
        if ($cat_name) {
            $clean_name = preg_replace('/[^a-zA-Z0-9]/', '', $cat_name);
            if (strlen($clean_name) > 0) {
                $prefix = strtoupper(substr($clean_name, 0, 3));
                if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');
            }
        }
    }

        $base_code = $_POST['base_item_code'] ?? '';
    if (empty(trim($base_code))) {
        $stmt = $pdo->prepare("SELECT item_code FROM Products WHERE item_code LIKE ?");
        $stmt->execute([$prefix . '-%']);
        $existing_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $max_num = 0;
        foreach ($existing_codes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)(?:-\d+)?$/', $code, $matches)) {
                $max_num = max($max_num, (int)$matches[1]);
            }
        }
        $next_num = $max_num + 1;
        $base_code = $prefix . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    }

    $is_multiple = count($variant_rows) > 1;
    $used_codes = [];
    $variant_counter = 1;

    foreach ($variant_rows as &$variant) {
        if ($is_multiple) {
            do {
                $test_code = $base_code . '-' . $variant_counter;
                $check = $pdo->prepare("SELECT COUNT(*) FROM Products WHERE item_code = ?");
                $check->execute([$test_code]);
                $exists = $check->fetchColumn() > 0 || in_array($test_code, $used_codes);
                if ($exists) $variant_counter++;
            } while ($exists);
            $variant['item_code'] = $test_code;
            $variant_counter++;
        } else {
            $variant['item_code'] = $base_code;
        }
        $used_codes[] = $variant['item_code'];
    }
    unset($variant);

    foreach ($variant_rows as $variant) {
        if ($variant['purchasing_price'] === '' || $variant['margin_percent'] === '' || $variant['selling_price'] === '' || ($is_multiple && $variant['attribute'] === '')) {
            $error = 'Please complete every required field for each product variant.';
            break;
        }
    }
    $unit = trim($_POST['unit']);
    $description = trim($_POST['description']);
    $supplier_ids = array_values(array_unique(array_filter(array_map('intval', $_POST['supplier_ids'] ?? []))));
    if ($supplier_ids) {
        $placeholders = implode(',', array_fill(0, count($supplier_ids), '?'));
        $supplierNameStmt = $pdo->prepare("SELECT name FROM Suppliers WHERE admin_id = ? AND id IN ($placeholders) ORDER BY name");
        $supplierNameStmt->execute(array_merge([$_SESSION['user_id']], $supplier_ids));
        $supplier_names = $supplierNameStmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($supplier_names) !== count($supplier_ids)) {
            $error = 'Please select valid suppliers.';
        }
        $supplier_name = implode(', ', $supplier_names);
    }
    $image_link = trim($_POST['image_link']);
    $video_link = trim($_POST['video_link']);

    // Handle File Uploads and Links
    $image_url = '';
    $video_url = '';
    $upload_dir = '../assets/uploads/';

    // Prioritize link over upload for image
    if (!empty($image_link)) {
        $image_url = $image_link;
    } elseif (!empty($_FILES['image']['name'])) {
        $original_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $img_base_name = time() . '_img_' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $tmp_path = $_FILES['image']['tmp_name'];

        // Optimize image: resize to max 768px width, compress as WebP
        $max_width = 768; // Max width
        $quality = 80;    // WebP quality
        $img_info = getimagesize($tmp_path);

                if ($img_info) {
            if (extension_loaded('gd')) {
                $img_name = $img_base_name . '.webp'; // Save as WebP
                $save_path = $upload_dir . $img_name;
                $mime = $img_info['mime'];
                switch ($mime) {
                    case 'image/jpeg':
                        $src = imagecreatefromjpeg($tmp_path);
                        break;
                    case 'image/png':
                        $src = imagecreatefrompng($tmp_path);
                        break;
                    case 'image/gif':
                        $src = imagecreatefromgif($tmp_path);
                        break;
                    case 'image/webp':
                        $src = imagecreatefromwebp($tmp_path);
                        break;
                    default:
                        $src = false;
                }
                if ($src) {
                    $orig_w = imagesx($src);
                    $orig_h = imagesy($src);
                    if ($orig_w > $max_width) {
                        $new_w = $max_width;
                        $new_h = intval($orig_h * ($max_width / $orig_w));
                    } else {
                        $new_w = $orig_w;
                        $new_h = $orig_h;
                    }
                    
                    $dst = imagecreatetruecolor($new_w, $new_h);
                    
                    if ($mime == 'image/png' || $mime == 'image/webp' || $mime == 'image/gif') {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                        imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);
                    }
                    
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
                    
                    if (function_exists('imagewebp')) {
                        imagewebp($dst, $save_path, $quality);
                    } else {
                        imagejpeg($dst, $save_path, $quality);
                    }
                    
                    imagedestroy($dst);
                    imagedestroy($src);
                } else {
                    $error = "Failed to process image with GD.";
                    $img_name = null;
                }
            } else {
                // Fallback if GD is not loaded
                $allowed_img_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($original_ext, $allowed_img_extensions)) {
                    $img_name = $img_base_name . '.' . $original_ext;
                    $save_path = $upload_dir . $img_name;
                    move_uploaded_file($tmp_path, $save_path);
                } else {
                    $error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
                    $img_name = null;
                }
            }
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            $error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            $img_name = null;
        }
        $image_url = $img_name;
    }

    // Prioritize link over upload for video
    if (!empty($video_link)) {
        $video_url = $video_link;
    } elseif (!empty($_FILES['video']['name'])) {
        // --- Security Fix: File Upload Extension Validation ---
        $allowed_vid_extensions = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
        $vid_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        
        if (in_array($vid_ext, $allowed_vid_extensions)) {
            $vid_name = time() . '_vid_' . basename($_FILES['video']['name']);
            move_uploaded_file($_FILES['video']['tmp_name'], $upload_dir . $vid_name);
            $video_url = $vid_name;
        } else {
            $error = 'Invalid video format. Only MP4, WEBM, OGG, MOV, AVI are allowed.';
        }
    }

    if (empty($error))
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO Products
            (item_code, name, search_keywords, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, stock_quantity, low_stock_threshold, image_url, video_url, created_by_admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($variant_rows as $variant) {
                $stmt->execute([
                    $variant['item_code'],
                    $name,
                    $_POST['search_keywords'] ?? '',
                    $variant['attribute'],
                    $type,
                    $variant['purchasing_price'],
                    $variant['margin_percent'],
                    $variant['selling_price'],
                    $unit,
                    $description,
                    $supplier_name,
                    $_POST['stock_quantity'] ?? 0,
                    $_POST['low_stock_threshold'] ?? 10,
                    $image_url,
                    $video_url,
                    $_SESSION['user_id']
                ]);
                $product_id = $pdo->lastInsertId();

                if (!empty($category_ids)) {
                    $cat_stmt = $pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");
                    foreach ($category_ids as $cat_id) {
                        $cat_stmt->execute([$product_id, $cat_id]);
                    }
                }
            }

            $pdo->commit();
            $success = "Product added successfully!";

            // Send to this admin's Google Sheet if configured
            $admin_webhook_key = 'google_sheet_webhook_admin_' . intval($_SESSION['user_id']);
            $webhookStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
            $webhookStmt->execute([$admin_webhook_key]);
            $webhook_url = $webhookStmt->fetchColumn();
            if ($webhook_url) {
                foreach ($variant_rows as $variant) {
                    $postData = [
                        'action' => 'add',
                        'data' => [
                            'item_code' => $variant['item_code'],
                            'name' => $name,
                            'attribute' => $variant['attribute'],
                            'type' => $type,
                            'category_id' => implode(',', $category_ids),
                            'purchasing_price' => $variant['purchasing_price'],
                            'margin_percent' => $variant['margin_percent'],
                            'selling_price' => $variant['selling_price'],
                            'unit' => $unit,
                            'description' => $description,
                            'supplier_name' => $supplier_name
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
                }
            }
        } catch (\PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Item code already exists.";
            } else {
                $error = "Error adding product: " . $e->getMessage();
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
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Add New Product</h1>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 p-8 rounded-xl card-shadow">
    <form action="product_add.php" method="POST" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">


        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Info & Restructured Layout -->
            <div>
                <div class="flex justify-between items-center">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                        <a href="manage_categories.php" class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-500/40 px-2 py-1 rounded flex items-center gap-1 transition font-medium border border-blue-200 dark:border-blue-500/30" target="_blank">
                            <ion-icon name="add-circle-outline"></ion-icon> Add New Category
                        </a>
                    </div>
                    <select name="category_ids[]" id="category_select" class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                        <option value="" data-name="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Item Code (Base)</label>
                <div class="flex gap-2 mt-1">
                    <input type="text" id="base_item_code" name="base_item_code" placeholder="Auto-generated on category select"
                        class="block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                    <button type="button" id="btn_regen_code" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md border border-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600" title="Regenerate Code">
                        <ion-icon name="refresh-outline"></ion-icon>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                <input type="text" name="name" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Keywords (For Singlish/Tags)</label>
                <input type="text" name="search_keywords" placeholder=""
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">If the product name is in Sinhala, type Singlish words here so you can search them easily in the POS.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type (Optional)</label>
                <input type="text" name="type" placeholder=""
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <!-- Product Variants -->
            <div class="md:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Product Variants (Size, Color, etc.)</label>
                    <label class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox" id="enable_variants_toggle" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-blue/30 dark:peer-focus:ring-brand-blue/80 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-blue"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white" id="variants_title">Pricing & Inventory</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="variants_subtitle">Enter pricing details.</p>
                    </div>
                    <button type="button" id="add-variant"
                        class="hidden rounded-md bg-brand-blue px-3 py-2 text-sm font-medium text-white hover:bg-brand-blueDark">
                        <ion-icon name="add-outline"></ion-icon> Add Variant
                    </button>
                </div>
                <div id="variant-rows" class="space-y-4">
                    <?php foreach ($variant_rows as $index => $variant): ?>
                        <div
                            class="variant-row grid grid-cols-1 md:grid-cols-5 gap-3 rounded-md border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-700">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Item Code
                                    *</label>
                                <input type="text" name="item_code[]" value="<?= htmlspecialchars($variant['item_code']) ?>" readonly class="variant-input mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white px-3 py-2">
                            </div>
                            <div class="attr-col hidden"><label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Attribute</label>
                                <input type="text" name="attribute[]" value="<?= htmlspecialchars($variant['attribute']) ?>"
                                    placeholder=""
                                    class="variant-input mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purchasing Price
                                    *</label>
                                <input type="number" step="0.01" min="0" name="purchasing_price[]"
                                    value="<?= htmlspecialchars($variant['purchasing_price']) ?>" required
                                    class="variant-purchase variant-input mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Margin % *</label>
                                <input type="number" step="0.01" min="0" name="margin_percent[]"
                                    value="<?= htmlspecialchars($variant['margin_percent']) ?>" required
                                    class="variant-margin variant-input mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Selling Price
                                        *</label>
                                    <input type="number" step="0.01" min="0" name="selling_price[]"
                                        value="<?= htmlspecialchars($variant['selling_price']) ?>" required
                                        class="variant-selling variant-input mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                                </div>
                                <button type="button" class="remove-variant remove-col hidden mb-1 p-2 text-red-600 hover:text-red-800"
                                    title="Remove variant">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Inventory & Supplier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                <input type="text" name="unit" placeholder=""
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Initial Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="0"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <?php if (!empty($_SESSION['module_supply'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Low Stock Alert Threshold</label>
                <input type="number" name="low_stock_threshold" min="0" value="10"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Alerts supplier when stock falls below this number.</p>
            </div>
            <?php endif; ?>
            <div class="md:col-span-2">
                <div class="relative" x-data="{ open: false }">
                    <div class="flex justify-between items-center">
                        <label for="supplier_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers</label>
                        <?php if (!empty($_SESSION['module_supply'])): ?>
                        <button type="button" onclick="document.getElementById('addSupplierModal').classList.remove('hidden'); document.getElementById('addSupplierModal').classList.add('flex');" class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-500/40 px-2 py-1 rounded flex items-center gap-1 transition font-medium border border-blue-200 dark:border-blue-500/30">
                            <ion-icon name="add-circle-outline"></ion-icon> Add New Supplier
                        </button>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="supplier_ids" @click="open = !open" :aria-expanded="open"
                        class="mt-2 w-full flex justify-between items-center text-left rounded-md border border-black dark:border-gray-500 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue">
                        <span><?= $supplier_ids ? count($supplier_ids) . ' supplier(s) selected' : 'Select Suppliers' ?></span>
                        <ion-icon name="chevron-down-outline" class="text-gray-500"
                            :class="{ 'rotate-180': open }"></ion-icon>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute left-0 top-full mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg z-20">
                        <div class="max-h-52 overflow-y-auto p-3 space-y-2">
                            <?php foreach ($suppliers as $supplier): ?>
                                <label
                                    class="flex items-center gap-2 rounded px-2 py-2 text-sm text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <input type="checkbox" name="supplier_ids[]" value="<?= $supplier['id'] ?>"
                                        <?= in_array((int) $supplier['id'], $supplier_ids, true) ? 'checked' : '' ?>
                                        class="h-4 w-4 rounded border-gray-400 text-brand-blue focus:ring-brand-blue">
                                    <span><?= htmlspecialchars($supplier['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (!$suppliers): ?>
                                <span class="block px-2 py-2 text-sm text-gray-500 dark:text-gray-400">Add suppliers from
                                    Manage Suppliers first.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea name="description" rows="3"
                class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"></textarea>
        </div>

        <!-- Media Uploads -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Image
                        (Upload)</label>
                    <input type="file" name="image" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-brand-blue file:text-white hover:file:bg-brand-blueDark">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Or Image URL</label>
                    <input type="url" name="image_link" placeholder=""
                        class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Video
                        (Upload)</label>
                    <input type="file" name="video" accept="video/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-brand-blue file:text-white hover:file:bg-brand-blueDark">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Or Video URL</label>
                    <input type="url" name="video_link" placeholder=""
                        class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <button type="submit"
                class="w-full md:w-auto flex justify-center py-2 px-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                Save Product
            </button>
        </div>
    </form>
</div>

<script>
    const variantRows = document.getElementById('variant-rows');
    const addVariantButton = document.getElementById('add-variant');
    const baseItemCodeInput = document.getElementById('base_item_code');
    const regenBtn = document.getElementById('btn_regen_code');
        const categorySelect = document.getElementById('category_select');

    function updateSellingPrice(row) {
        const purchasingPrice = parseFloat(row.querySelector('.variant-purchase').value);
        const marginPercent = parseFloat(row.querySelector('.variant-margin').value);
        const sellingPrice = row.querySelector('.variant-selling');
        if (Number.isFinite(purchasingPrice) && Number.isFinite(marginPercent)) {
            sellingPrice.value = (purchasingPrice * (1 + marginPercent / 100)).toFixed(2);
        }
    }

    function updateMargin(row) {
        const purchasingPrice = parseFloat(row.querySelector('.variant-purchase').value);
        const sellingPrice = parseFloat(row.querySelector('.variant-selling').value);
        const marginPercent = row.querySelector('.variant-margin');
        if (Number.isFinite(purchasingPrice) && Number.isFinite(sellingPrice) && purchasingPrice > 0) {
            marginPercent.value = (((sellingPrice - purchasingPrice) / purchasingPrice) * 100).toFixed(2);
        }
    }

    function bindVariantRow(row) {
        row.querySelector('.variant-purchase').addEventListener('input', () => updateSellingPrice(row));
        row.querySelector('.variant-margin').addEventListener('input', () => updateSellingPrice(row));
        row.querySelector('.variant-selling').addEventListener('input', () => updateMargin(row));
        
        row.querySelector('.remove-variant').addEventListener('click', () => {
            if (variantRows.querySelectorAll('.variant-row').length > 1) {
                row.remove();
                syncVariantItemCodes();
            }
        });
    }

    function syncVariantItemCodes() {
        const base = baseItemCodeInput.value.trim();
        const rows = variantRows.querySelectorAll('.variant-row');
        const isMultiple = document.getElementById('enable_variants_toggle') && document.getElementById('enable_variants_toggle').checked;
        
        rows.forEach((row, index) => {
            const input = row.querySelector('input[name="item_code[]"]');
            if (input) {
                if (base === '') {
                    input.value = '';
                } else if (isMultiple && rows.length > 1) {
                    input.value = base + '-' + (index + 1);
                } else {
                    input.value = base;
                }
            }
        });
    }

    async function forceRegenBaseCode() {
        let prefix = 'ITM';
        if (categorySelect && categorySelect.selectedIndex > 0) {
            let name = categorySelect.options[categorySelect.selectedIndex].getAttribute('data-name').replace(/[^a-zA-Z0-9]/g, '');
            if (name.length > 0) {
                prefix = name.substring(0, 3).toUpperCase();
                if (prefix.length < 3) prefix = prefix.padEnd(3, 'X');
            }
        }
        
        try {
            const res = await fetch('ajax_get_next_code.php?prefix=' + prefix);
            const data = await res.json();
            baseItemCodeInput.value = data.next_code;
        } catch (e) {
            baseItemCodeInput.value = prefix + '-0001';
        }
        syncVariantItemCodes();
    }

    function autoGenBaseCode() {
        if (baseItemCodeInput.value.trim() !== '') return; 
        forceRegenBaseCode();
    }

    if (regenBtn) regenBtn.addEventListener('click', forceRegenBaseCode);
    if (categorySelect) categorySelect.addEventListener('change', forceRegenBaseCode);
    baseItemCodeInput.addEventListener('input', syncVariantItemCodes);

    function updateAttributeRequirement() {
        const toggle = document.getElementById('enable_variants_toggle');
        const isEnabled = toggle ? toggle.checked : false;
        
        variantRows.querySelectorAll('input[name="attribute[]"]').forEach(input => {
            input.required = isEnabled;
        });
        
        const attrCols = document.querySelectorAll('.attr-col');
        const removeCols = document.querySelectorAll('.remove-col');
        const addVariantBtn = document.getElementById('add-variant');
        
        if (isEnabled) {
            attrCols.forEach(col => col.classList.remove('hidden'));
            removeCols.forEach(col => col.classList.remove('hidden'));
            if (addVariantBtn) addVariantBtn.classList.remove('hidden');
            const t = document.getElementById('variants_title');
            if (t) t.innerText = 'Product Variants';
            const s = document.getElementById('variants_subtitle');
            if (s) s.innerText = 'Add one row for each attribute and price.';
        } else {
            attrCols.forEach(col => col.classList.add('hidden'));
            removeCols.forEach(col => col.classList.add('hidden'));
            if (addVariantBtn) addVariantBtn.classList.add('hidden');
            const t = document.getElementById('variants_title');
            if (t) t.innerText = 'Pricing & Inventory';
            const s = document.getElementById('variants_subtitle');
            if (s) s.innerText = 'Enter pricing details.';
            
            const rows = variantRows.querySelectorAll('.variant-row');
            for(let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
        }
        syncVariantItemCodes();
    }
    
    const enableToggle = document.getElementById('enable_variants_toggle');
    if (enableToggle) enableToggle.addEventListener('change', updateAttributeRequirement);

    variantRows.querySelectorAll('.variant-row').forEach(bindVariantRow);
    
    if (variantRows.querySelectorAll('.variant-row').length > 1 || (variantRows.querySelector('input[name="attribute[]"]') && variantRows.querySelector('input[name="attribute[]"]').value !== '')) {
        if(enableToggle) enableToggle.checked = true;
    }
    updateAttributeRequirement();
    
    if (addVariantButton) {
        addVariantButton.addEventListener('click', () => {
            if (variantRows.querySelectorAll('.variant-row').length >= 5) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit Reached',
                        text: 'You can only add up to 5 variants at a time.',
                        confirmButtonColor: '#3085d6'
                    });
                } else {
                    alert('Maximum 5 variants allowed.');
                }
                return;
            }
            const row = variantRows.querySelector('.variant-row').cloneNode(true);
            row.querySelectorAll('input').forEach(input => { if(input.name !== 'item_code[]') input.value = ''; });
            variantRows.appendChild(row);
            bindVariantRow(row);
            updateAttributeRequirement();
        });
    }
</script>


<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-[100]">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow w-full max-w-md transform transition-all">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <ion-icon name="person-add" class="text-brand-blue"></ion-icon> Add Supplier
            </h3>
            <button type="button" onclick="document.getElementById('addSupplierModal').classList.add('hidden'); document.getElementById('addSupplierModal').classList.remove('flex');" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div id="sup_error" class="hidden bg-red-100 text-red-700 p-2 rounded text-sm mb-3"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier Name *</label>
                <input type="text" id="sup_name" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone (Optional)</label>
                <input type="text" id="sup_phone" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email (Optional)</label>
                <input type="email" id="sup_email" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="document.getElementById('addSupplierModal').classList.add('hidden'); document.getElementById('addSupplierModal').classList.remove('flex');" class="px-4 py-2 bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 transition">Cancel</button>
                <button type="button" id="btn_save_supplier" class="px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-blue-700 shadow transition flex items-center gap-2">
                    Save Supplier
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById("btn_save_supplier")?.addEventListener("click", function() {
    const name = document.getElementById("sup_name").value.trim();
    const phone = document.getElementById("sup_phone").value.trim();
    const email = document.getElementById("sup_email").value.trim();
    const errorDiv = document.getElementById("sup_error");
    
    if(!name) {
        errorDiv.textContent = "Supplier Name is required!";
        errorDiv.classList.remove("hidden");
        return;
    }
    
    this.disabled = true;
    this.innerHTML = "Saving...";
    
    const formData = new FormData();
    formData.append("ajax_add_supplier", "1");
    formData.append("sup_name", name);
    formData.append("sup_phone", phone);
    formData.append("sup_email", email);
    
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Append to checkboxes
            const container = document.querySelector(".max-h-52.overflow-y-auto");
            const newLabel = document.createElement("label");
            newLabel.className = "flex items-center gap-2 rounded px-2 py-2 text-sm text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700";
            newLabel.innerHTML = `<input type="checkbox" name="supplier_ids[]" value="${data.id}" checked class="h-4 w-4 rounded border-gray-400 text-brand-blue focus:ring-brand-blue"> <span>${data.name}</span>`;
            
            // Remove "Add suppliers first" msg if present
            const emptyMsg = container.querySelector("span.text-gray-500");
            if (emptyMsg && emptyMsg.innerText.includes("first")) {
                emptyMsg.remove();
            }
            
            container.appendChild(newLabel);
            
            // Reset and close modal
            document.getElementById("sup_name").value = "";
            document.getElementById("sup_phone").value = "";
            document.getElementById("sup_email").value = "";
            errorDiv.classList.add("hidden");
            document.getElementById("addSupplierModal").classList.add("hidden");
            document.getElementById("addSupplierModal").classList.remove("flex");
        } else {
            errorDiv.textContent = data.error || "An error occurred.";
            errorDiv.classList.remove("hidden");
        }
    })
    .catch(err => {
        errorDiv.textContent = "Network error. Try again.";
        errorDiv.classList.remove("hidden");
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = "Save Supplier";
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>