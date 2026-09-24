<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

// 1. GD fallback (Fixing the GD bug)
$gd_search = '/if \(\$img_info && extension_loaded\(\'gd\'\)\) \{.*?imagedestroy\(\$src\);\s*\} else \{\s*\/\/ --- Security Fix: Do not allow unknown files to be uploaded as images ---\s*\$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";\s*\$uploaded = false;\s*\}/s';
$gd_replace = <<<PHP
        if (\$img_info) {
            if (extension_loaded('gd')) {
                \$mime = \$img_info['mime'];
                switch (\$mime) {
                    case 'image/jpeg': \$src = imagecreatefromjpeg(\$tmp_path); break;
                    case 'image/png': \$src = imagecreatefrompng(\$tmp_path); break;
                    case 'image/gif': \$src = imagecreatefromgif(\$tmp_path); break;
                    case 'image/webp': \$src = imagecreatefromwebp(\$tmp_path); break;
                    default: \$src = false;
                }
                if (\$src) {
                    \$orig_w = imagesx(\$src); \$orig_h = imagesy(\$src);
                    if (\$orig_w > \$max_width) {
                        \$new_w = \$max_width;
                        \$new_h = intval(\$orig_h * (\$max_width / \$orig_w));
                    } else {
                        \$new_w = \$orig_w; \$new_h = \$orig_h;
                    }
                    \$dst = imagecreatetruecolor(\$new_w, \$new_h);
                    if (\$mime == 'image/png' || \$mime == 'image/webp' || \$mime == 'image/gif') {
                        imagealphablending(\$dst, false);
                        imagesavealpha(\$dst, true);
                        \$transparent = imagecolorallocatealpha(\$dst, 255, 255, 255, 127);
                        imagefilledrectangle(\$dst, 0, 0, \$new_w, \$new_h, \$transparent);
                    }
                    imagecopyresampled(\$dst, \$src, 0, 0, 0, 0, \$new_w, \$new_h, \$orig_w, \$orig_h);
                    if (function_exists('imagewebp')) {
                        imagewebp(\$dst, \$save_path, \$quality);
                    } else {
                        imagejpeg(\$dst, \$save_path, \$quality);
                    }
                    imagedestroy(\$dst);
                    imagedestroy(\$src);
                } else {
                    \$error = "Failed to process image with GD.";
                    \$uploaded = false;
                }
            } else {
                \$allowed_img_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array(\$original_ext, \$allowed_img_extensions)) {
                    move_uploaded_file(\$tmp_path, \$save_path);
                    \$uploaded = true;
                } else {
                    \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
                    \$uploaded = false;
                }
            }
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$uploaded = false;
        }
PHP;
$content = preg_replace($gd_search, $gd_replace, $content);

// 2. Layout & Category Dropdown
$grid_start = strpos($content, '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">');
$cat_end_search = '</div>
            </div>
            <div class="md:col-span-2">';
$cat_end = strpos($content, $cat_end_search, $grid_start) + strlen('</div>
            </div>');

if ($grid_start !== false && $cat_end !== false) {
    $search_regex = '/<div class="grid grid-cols-1 md:grid-cols-2 gap-6">.*?<div class="md:col-span-2">\s*<span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers<\/span>/s';
    
    $replacement = <<<HTML
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
                    <?php foreach (\$categories as \$cat): ?>
                        <option value="<?= \$cat['id'] ?>" data-name="<?= htmlspecialchars(\$cat['name']) ?>" <?= in_array(\$cat['id'], \$product_category_ids) ? 'selected' : '' ?>><?= htmlspecialchars(\$cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Item Code *</label>
                <input type="text" name="item_code" value="<?= htmlspecialchars(\$product['item_code']) ?>" required readonly
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars(\$product['name']) ?>" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type (Optional)</label>
                <input type="text" name="type" value="<?= htmlspecialchars(\$product['type']) ?>" placeholder=""
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attribute</label>
                <input type="text" name="attribute" value="<?= htmlspecialchars(\$product['attribute'] ?? '') ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>

            <div class="md:col-span-2">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers</span>
HTML;
    $content = preg_replace($search_regex, $replacement, $content, 1);
}

// 3. New Variant Selling Price Editable Fix
// Wait, I ALSO had `patch_edit_safe.php`! That was what I ran earlier to make the new variant selling price editable!
// Oh boy, `patch_edit_safe.php` is lost too! Let me include it here.

// 3.a. Remove readonly from new_selling_price
$content = preg_replace('/<input type="number" step="0\.01" min="0" id="new_selling_price" name="new_selling_price" value="([^"]*)" readonly/', '<input type="number" step="0.01" min="0" id="new_selling_price" name="new_selling_price" value="$1"', $content);
$content = str_replace('class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-white px-3 py-2"', 'class="mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2"', $content);

// 3.b. Fix backend calculation for new_selling_price
$content = preg_replace('/if \(is_numeric\(\$new_variant\[\'purchasing_price\'\]\) && is_numeric\(\$new_variant\[\'margin_percent\'\]\)\) \{\s*\$new_variant\[\'selling_price\'\] = .*?;\s*\}/s', '$new_variant[\'selling_price\'] = is_numeric($_POST[\'new_selling_price\'] ?? null) ? number_format((float)$_POST[\'new_selling_price\'], 2, \'.\', \'\') : \'\';', $content);

// 3.c. Add item code suffix generation
$validation_block = <<<PHP
    if (empty(\$error)) {
        if (\$add_variant) {
            if (\$new_variant['item_code'] === '') {
                // Auto-generate based on existing product's item code
                \$base_code = preg_replace('/-\d+$/', '', trim(\$product['item_code']));
                if (empty(\$base_code)) \$base_code = 'ITM-' . mt_rand(1000, 9999);
                
                \$stmt = \$pdo->prepare("SELECT item_code FROM Products WHERE item_code LIKE ?");
                \$stmt->execute([\$base_code . '%']);
                \$existing_codes = \$stmt->fetchAll(PDO::FETCH_COLUMN);
                
                \$max_suffix = 0;
                foreach (\$existing_codes as \$code) {
                    if (\$code === \$base_code) continue;
                    if (preg_match('/^' . preg_quote(\$base_code, '/') . '-(\d+)$/', \$code, \$matches)) {
                        \$max_suffix = max(\$max_suffix, (int)\$matches[1]);
                    }
                }
                
                \$new_suffix = \$max_suffix + 1;
                \$new_variant['item_code'] = \$base_code . '-' . \$new_suffix;
            }
            
            if (\$new_variant['item_code'] === '' || \$new_variant['attribute'] === '' || \$new_variant['purchasing_price'] === '' || \$new_variant['margin_percent'] === '' || \$new_variant['selling_price'] === '') {
                \$error = 'Please complete the new variant Item Code, Attribute, Purchasing Price, Margin %, and Selling Price.';
            }
        }
    }
PHP;
$content = preg_replace('/if \(empty\(\$error\)\) \{\s*if \(\$add_variant && \(\$new_variant\[\'item_code\'\] === \'\' \|\| \$new_variant\[\'attribute\'\] === \'\' \|\| \$new_variant\[\'purchasing_price\'\] === \'\' \|\| \$new_variant\[\'margin_percent\'\] === \'\'\)\) \{\s*\$error = \'Please complete the new variant Item Code, Attribute, Purchasing Price, and Margin %.\';\s*\}\s*\}/s', $validation_block, $content);

// 3.d. Update new_item_code HTML
$content = preg_replace('/name="new_item_code" value="<\?= htmlspecialchars\(\$_POST\[\'new_item_code\'\] \?\? \'\'\) \?>"/s', 'name="new_item_code" value="<?= htmlspecialchars($_POST[\'new_item_code\'] ?? \'\') ?>" placeholder="Auto-generated if empty"', $content);

// 3.e. Update JS safely by targeting the specific script block at the end
$newJs = <<<JS
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
JS;

$content = preg_replace('/<script>\s*const newPurchasingPrice = document\.getElementById\(\'new_purchasing_price\'\);.*?<\/script>/s', $newJs, $content);


file_put_contents($file, $content);
echo "Restored and fixed product_edit.php\n";

?>

