<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_stock');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id === 0) {
    header('Location: products.php');
    exit;
}

$error = '';
$success = '';

// Fetch product data
$stmt = $pdo->prepare("SELECT * FROM Products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error_message'] = "Product not found.";
    header('Location: products.php');
    exit;
}

// Authorization Check: Admins can only edit their own products. Superadmins can edit any.
if ($_SESSION['role'] === 'admin' && $product['created_by_admin_id'] != $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You are not authorized to edit this product.";
    header('Location: products.php');
    exit;
}

// Fetch current categories for the product
$product_cat_stmt = $pdo->prepare("SELECT category_id FROM Product_Categories WHERE product_id = ?");
$product_cat_stmt->execute([$product_id]);
$product_category_ids = $product_cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all categories for the form
$categoriesStmt = $pdo->prepare("SELECT * FROM Categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name");
$categoriesStmt->execute([$_SESSION['user_id']]);
$categories = $categoriesStmt->fetchAll();

$suppliersStmt = $pdo->prepare("SELECT id, name FROM Suppliers WHERE admin_id = ? ORDER BY name");
$suppliersStmt->execute([$_SESSION['user_id']]);
$suppliers = $suppliersStmt->fetchAll();
$supplier_ids = [];
foreach ($suppliers as $supplier) {
    if (in_array($supplier['name'], array_map('trim', explode(',', $product['supplier_name'] ?? '')), true)) {
        $supplier_ids[] = (int) $supplier['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $add_variant = isset($_POST['add_variant']);
    $new_variant = [
        'item_code' => trim($_POST['new_item_code'] ?? ''),
        'attribute' => trim($_POST['new_attribute'] ?? ''),
        'purchasing_price' => $_POST['new_purchasing_price'] ?? '',
        'margin_percent' => $_POST['new_margin_percent'] ?? '',
        'selling_price' => ''
    ];
    $new_variant['selling_price'] = is_numeric($_POST['new_selling_price'] ?? null) ? number_format((float)$_POST['new_selling_price'], 2, '.', '') : '';

    // Repopulate product object with POST data for sticky form
    $product['item_code'] = trim($_POST['item_code']);
    $product['name'] = trim($_POST['name']);
    $product['attribute'] = trim($_POST['attribute'] ?? '');
    $product['type'] = trim($_POST['type']);
    $product['purchasing_price'] = $_POST['purchasing_price'] ?? 0;
    $product['margin_percent'] = $_POST['margin_percent'] ?? 0;
    $product['selling_price'] = $_POST['selling_price'] ?? 0;
    $product['unit'] = trim($_POST['unit'] ?? '');
    $product['description'] = trim($_POST['description']);
    $supplier_ids = array_values(array_unique(array_filter(array_map('intval', $_POST['supplier_ids'] ?? []))));
    $product['supplier_name'] = '';
    if ($supplier_ids) {
        $placeholders = implode(',', array_fill(0, count($supplier_ids), '?'));
        $supplierNameStmt = $pdo->prepare("SELECT name FROM Suppliers WHERE admin_id = ? AND id IN ($placeholders) ORDER BY name");
        $supplierNameStmt->execute(array_merge([$_SESSION['user_id']], $supplier_ids));
        $supplier_names = $supplierNameStmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($supplier_names) !== count($supplier_ids)) {
            $error = 'Please select valid suppliers.';
        }
        $product['supplier_name'] = implode(', ', $supplier_names);
    }
    $image_link = trim($_POST['image_link']);
    $video_link = trim($_POST['video_link']);
    $category_ids = $_POST['category_ids'] ?? [];
    $product_category_ids = $category_ids; // Update for sticky form

    // Handle File Uploads and Links
    $image_url = $product['image_url']; // Keep old image by default
    $video_url = $product['video_url']; // Keep old video by default
    $upload_dir = '../assets/uploads/';

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
        $uploaded = false;
        
                if ($img_info) {
            if (extension_loaded('gd')) {
                $mime = $img_info['mime'];
                switch ($mime) {
                    case 'image/jpeg': $src = imagecreatefromjpeg($tmp_path); break;
                    case 'image/png': $src = imagecreatefrompng($tmp_path); break;
                    case 'image/gif': $src = imagecreatefromgif($tmp_path); break;
                    case 'image/webp': $src = imagecreatefromwebp($tmp_path); break;
                    default: $src = false;
                }
                if ($src) {
                    $orig_w = imagesx($src); $orig_h = imagesy($src);
                    if ($orig_w > $max_width) {
                        $new_w = $max_width;
                        $new_h = intval($orig_h * ($max_width / $orig_w));
                    } else {
                        $new_w = $orig_w; $new_h = $orig_h;
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
                    $uploaded = false;
                }
            } else {
                $allowed_img_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($original_ext, $allowed_img_extensions)) {
                    move_uploaded_file($tmp_path, $save_path);
                    $uploaded = true;
                } else {
                    $error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
                    $uploaded = false;
                }
            }
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            $error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            $uploaded = false;
        }

        if ($uploaded) {
            $image_url = $img_name;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if (!empty($video_link)) {
        $video_url = $video_link;
    } elseif (!empty($_FILES['video']['name'])) {
        // --- Security Fix: File Upload Extension Validation ---
        $allowed_vid_extensions = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
        $vid_ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        
        if (in_array($vid_ext, $allowed_vid_extensions)) {
            $vid_name = time() . '_vid_' . basename($_FILES['video']['name']);
            if (move_uploaded_file($_FILES['video']['tmp_name'], $upload_dir . $vid_name)) {
                $video_url = $vid_name;
            } else {
                $error = "Failed to upload video.";
            }
        } else {
            $error = 'Invalid video format. Only MP4, WEBM, OGG, MOV, AVI are allowed.';
        }
    }

        if (empty($error)) {
        if ($add_variant) {
            if ($new_variant['item_code'] === '') {
                // Auto-generate based on existing product's item code
                $base_code = preg_replace('/-\d+$/', '', trim($product['item_code']));
                if (empty($base_code)) $base_code = 'ITM-' . mt_rand(1000, 9999);
                
                $stmt = $pdo->prepare("SELECT item_code FROM Products WHERE item_code LIKE ?");
                $stmt->execute([$base_code . '%']);
                $existing_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $max_suffix = 0;
                foreach ($existing_codes as $code) {
                    if ($code === $base_code) continue;
                    if (preg_match('/^' . preg_quote($base_code, '/') . '-(\d+)$/', $code, $matches)) {
                        $max_suffix = max($max_suffix, (int)$matches[1]);
                    }
                }
                
                $new_suffix = $max_suffix + 1;
                $new_variant['item_code'] = $base_code . '-' . $new_suffix;
            }
            
            if ($new_variant['item_code'] === '' || $new_variant['attribute'] === '' || $new_variant['purchasing_price'] === '' || $new_variant['selling_price'] === '') {
                $error = 'Please complete the new variant Item Code, Attribute, Purchasing Price, and Selling Price.';
            }
        }
    }
    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE Products SET 
                        item_code = ?, name = ?, search_keywords = ?, attribute = ?, type = ?, purchasing_price = ?,
                        margin_percent = ?, selling_price = ?, unit = ?, 
                        description = ?, supplier_name = ?, image_url = ?, video_url = ?, stock_quantity = ?, low_stock_threshold = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $product['item_code'],
                $product['name'],
                $_POST['search_keywords'] ?? '',
                $product['attribute'],
                $product['type'],
                $product['purchasing_price'],
                $product['margin_percent'],
                $product['selling_price'],
                $product['unit'],
                $product['description'],
                $product['supplier_name'],
                $image_url,
                $video_url,
                $_POST['stock_quantity'] ?? 0,
                $_POST['low_stock_threshold'] ?? 10,
                $product_id
            ]);

            $del_stmt = $pdo->prepare("DELETE FROM Product_Categories WHERE product_id = ?");
            $del_stmt->execute([$product_id]);

            if (!empty($category_ids)) {
                $cat_stmt = $pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");
                foreach ($category_ids as $cat_id) {
                    $cat_stmt->execute([$product_id, $cat_id]);
                }
            }

            if ($add_variant) {
                $newStmt = $pdo->prepare("INSERT INTO Products (item_code, name, search_keywords, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, stock_quantity, low_stock_threshold, image_url, video_url, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $newStmt->execute([
                    $new_variant['item_code'], $product['name'], $_POST['search_keywords'] ?? '', $new_variant['attribute'], $product['type'],
                    $new_variant['purchasing_price'], $new_variant['margin_percent'], $new_variant['selling_price'],
                    $product['unit'], $product['description'], $product['supplier_name'], $_POST['stock_quantity'] ?? 0, $_POST['low_stock_threshold'] ?? 10, $image_url, $video_url,
                    $_SESSION['user_id']
                ]);
                $new_product_id = $pdo->lastInsertId();
                if (!empty($category_ids)) {
                    foreach ($category_ids as $cat_id) {
                        $cat_stmt->execute([$new_product_id, $cat_id]);
                    }
                }
            }

            $pdo->commit();

            // Send update to this admin's Google Sheet if configured
            $admin_webhook_key = 'google_sheet_webhook_admin_' . intval($_SESSION['user_id']);
            $webhookStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
            $webhookStmt->execute([$admin_webhook_key]);
            $webhook_url = $webhookStmt->fetchColumn();
            if ($webhook_url) {
                $postData = [
                    'action' => 'update',
                    'data' => [
                        'item_code' => $product['item_code'],
                        'name' => $product['name'],
                        'attribute' => $product['attribute'],
                        'type' => $product['type'],
                        'category_id' => implode(',', $category_ids),
                        'purchasing_price' => $product['purchasing_price'],
                        'margin_percent' => $product['margin_percent'],
                        'selling_price' => $product['selling_price'],
                        'unit' => $product['unit'],
                        'description' => $product['description'],
                        'supplier_name' => $product['supplier_name']
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

                if ($add_variant) {
                    $postData = ['action' => 'add', 'data' => [
                        'item_code' => $new_variant['item_code'], 'name' => $product['name'],
                        'attribute' => $new_variant['attribute'], 'type' => $product['type'],
                        'category_id' => implode(',', $category_ids), 'purchasing_price' => $new_variant['purchasing_price'],
                        'margin_percent' => $new_variant['margin_percent'], 'selling_price' => $new_variant['selling_price'],
                        'unit' => $product['unit'], 'description' => $product['description'],
                        'supplier_name' => $product['supplier_name']
                    ]];
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

            $_SESSION['success_message'] = $add_variant ? "Product updated and new variant added successfully!" : "Product updated successfully!";
            header('Location: products.php');
            exit;

        } catch (\PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Item code is already in use by another product.";
            } else {
                $error = "Error updating product: " . $e->getMessage();
            }
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
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Product</h1>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 p-8 rounded-xl card-shadow">
    <form action="product_edit.php?id=<?= $product_id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="flex justify-between items-center">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <a href="manage_categories.php" class="text-xs text-brand-blue hover:underline flex items-center gap-1" target="_blank">
                        <ion-icon name="add-circle-outline"></ion-icon> Add New Category
                    </a>
                </div>
                <select name="category_ids[]" id="category_select" class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                    <option value="" data-name="">Select a Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" <?= in_array($cat['id'], $product_category_ids) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Item Code *</label>
                <input type="text" name="item_code" value="<?= htmlspecialchars($product['item_code']) ?>" required readonly
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type (Optional)</label>
                <input type="text" name="type" value="<?= htmlspecialchars($product['type']) ?>" placeholder=""
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attribute</label>
                <input type="text" name="attribute" value="<?= htmlspecialchars($product['attribute'] ?? '') ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers</span>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-md border border-gray-300 dark:border-gray-600 p-3 bg-gray-50 dark:bg-gray-700">
                    <?php foreach ($suppliers as $supplier): ?>
                        <label class="flex items-center gap-2 text-sm text-gray-900 dark:text-gray-200">
                            <input type="checkbox" name="supplier_ids[]" value="<?= $supplier['id'] ?>"
                                                        <?= in_array((int) $supplier['id'], $supplier_ids, true) ? 'checked' : '' ?>
                                class="h-4 w-4 rounded border-black text-brand-blue focus:ring-brand-blue">
                            <?= htmlspecialchars($supplier['name']) ?>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!$suppliers): ?>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Add suppliers from Manage Suppliers first.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purchasing Price</label>
                <input type="number" step="0.01" name="purchasing_price"
                    value="<?= htmlspecialchars($product['purchasing_price']) ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selling Price *</label>
                <input type="number" step="0.01" name="selling_price"
                    value="<?= htmlspecialchars($product['selling_price']) ?>" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
        </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                <input type="text" name="unit" placeholder=""
                    value="<?= htmlspecialchars($product['unit'] ?? '') ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="<?= htmlspecialchars($product['stock_quantity'] ?? 0) ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <?php if (!empty($_SESSION['module_supply'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Low Stock Alert Threshold</label>
                <input type="number" name="low_stock_threshold" min="0" value="<?= htmlspecialchars($product['low_stock_threshold'] ?? 10) ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Alerts supplier when stock falls below this number.</p>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea name="description" rows="3"
                class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Image (Upload
                        New)</label>
                    <input type="file" name="image" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-brand-blue file:text-white hover:file:bg-brand-blueDark">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Or Image URL</label>
                    <input type="url" name="image_link"
                        value="<?= filter_var($product['image_url'], FILTER_VALIDATE_URL) ? htmlspecialchars($product['image_url']) : '' ?>"
                        placeholder=""
                        class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Video (Upload
                        New)</label>
                    <input type="file" name="video" accept="video/*"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-brand-blue file:text-white hover:file:bg-brand-blueDark">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Or Video URL</label>
                    <input type="url" name="video_link"
                        value="<?= filter_var($product['video_url'], FILTER_VALIDATE_URL) ? htmlspecialchars($product['video_url']) : '' ?>"
                        placeholder=""
                        class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                </div>
            </div>
            <?php if (!empty($product['video_url'])): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">A video is already attached. Upload a new video or provide a URL to replace it.</p>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Add New Variant</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-4">Update this product and add another attribute with its own price.</p>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 rounded-md border border-gray-300 dark:border-gray-600 p-4 bg-gray-50 dark:bg-gray-700">
                <div>
                    <label for="new_item_code" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Item Code</label>
                    <input type="text" id="new_item_code" name="new_item_code" value="<?= htmlspecialchars($_POST['new_item_code'] ?? '') ?>" placeholder="Auto-generated if empty"
                        class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                </div>
                <div>
                    <label for="new_attribute" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Attribute</label>
                    <input type="text" id="new_attribute" name="new_attribute" value="<?= htmlspecialchars($_POST['new_attribute'] ?? '') ?>" placeholder=""
                        class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                </div>
                <div>
                    <label for="new_purchasing_price" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Purchasing Price</label>
                    <input type="number" step="0.01" min="0" id="new_purchasing_price" name="new_purchasing_price" value="<?= htmlspecialchars($_POST['new_purchasing_price'] ?? '') ?>"
                        class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                </div>
                <div>
                    <label for="new_margin_percent" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Margin %</label>
                    <input type="number" step="0.01" min="0" id="new_margin_percent" name="new_margin_percent" value="<?= htmlspecialchars($_POST['new_margin_percent'] ?? '') ?>"
                        class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                </div>
                <div>
                    <label for="new_selling_price" class="block text-xs font-medium text-gray-700 dark:text-gray-300">Selling Price</label>
                    <input type="number" step="0.01" min="0" id="new_selling_price" name="new_selling_price" value="<?= htmlspecialchars($_POST['new_selling_price'] ?? '') ?>"
                        class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <a href="products.php"
                class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Cancel</a>
            <button type="submit"
                class="w-full md:w-auto flex justify-center py-2 px-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                Save Changes
            </button>
            <button type="submit" name="add_variant" value="1"
                class="w-full md:w-auto flex justify-center py-2 px-8 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                Update & Add Variant
            </button>
        </div>
    </form>
</div>

<script>
    const newPurchasingPrice = document.getElementById('new_purchasing_price');
    const newMarginPercent = document.getElementById('new_margin_percent');
    const newSellingPrice = document.getElementById('new_selling_price');

    function calculateNewVariantPriceFromMargin() {
        if (!newPurchasingPrice || !newMarginPercent || !newSellingPrice) return;
        const purchase = parseFloat(newPurchasingPrice.value);
        const margin = parseFloat(newMarginPercent.value);
        if (Number.isFinite(purchase) && Number.isFinite(margin)) {
            newSellingPrice.value = (purchase * (1 + margin / 100)).toFixed(2);
        }
    }

    function calculateMarginFromSellingPrice() {
        if (!newPurchasingPrice || !newMarginPercent || !newSellingPrice) return;
        const purchase = parseFloat(newPurchasingPrice.value);
        const selling = parseFloat(newSellingPrice.value);
        if (Number.isFinite(purchase) && Number.isFinite(selling) && purchase > 0) {
            newMarginPercent.value = (((selling - purchase) / purchase) * 100).toFixed(2);
        }
    }

    if (newPurchasingPrice && newMarginPercent && newSellingPrice) {
        newPurchasingPrice.addEventListener('input', calculateNewVariantPriceFromMargin);
        newMarginPercent.addEventListener('input', calculateNewVariantPriceFromMargin);
        newSellingPrice.addEventListener('input', calculateMarginFromSellingPrice);
    }
</script>

<?php require_once '../includes/footer.php'; ?>