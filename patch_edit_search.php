<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

// Add to UPDATE query
$search = "item_code = ?, name = ?, attribute = ?, type = ?, purchasing_price = ?,";
$replace = "item_code = ?, name = ?, search_keywords = ?, attribute = ?, type = ?, purchasing_price = ?,";
$content = str_replace($search, $replace, $content);

$search2 = "                    \$product['name'],\n";
$replace2 = "                    \$product['name'],\n                    \$_POST['search_keywords'] ?? '',\n";
$content = str_replace($search2, $replace2, $content);

// Add to INSERT (New Variant) query
$search4 = "name, attribute, type,";
$replace4 = "name, search_keywords, attribute, type,";
$content = str_replace($search4, $replace4, $content);

$search5 = "\$product['name'], \$new_variant['attribute']";
$replace5 = "\$product['name'], \$_POST['search_keywords'] ?? '', \$new_variant['attribute']";
$content = str_replace($search5, $replace5, $content);

// Add to UI (after Product Name)
$search3 = <<<HTML
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars(\$product['name']) ?>" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
HTML;
$replace3 = <<<HTML
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars(\$product['name']) ?>" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Keywords (For Singlish/Tags)</label>
                <input type="text" name="search_keywords" placeholder=""
                    value="<?= htmlspecialchars(\$product['search_keywords'] ?? '') ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">If the product name is in Sinhala, type Singlish words here so you can search them easily in the POS.</p>
            </div>
HTML;
$content = str_replace($search3, $replace3, $content);

file_put_contents($file, $content);
echo "Patched product_edit.php\n";
?>

