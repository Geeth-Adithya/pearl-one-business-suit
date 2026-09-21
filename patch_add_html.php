<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

// 1. Inject Search Keywords after Product Name
$pattern1 = '/(<div>\s*<label[^>]*>Product Name \*<\/label>\s*<input type="text" name="name"[^>]*>\s*<\/div>)/is';
$replacement1 = '$1' . "\n" . <<<HTML
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search Keywords (For Singlish/Tags)</label>
                <input type="text" name="search_keywords" placeholder="e.g. kiri, milk, anchor"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">If the product name is in Sinhala, type Singlish words here so you can search them easily in the POS.</p>
            </div>
HTML;

$content = preg_replace($pattern1, $replacement1, $content);

// 2. Inject Stock Quantity after Unit
$pattern2 = '/(<div>\s*<label[^>]*>Unit<\/label>\s*<input type="text" name="unit"[^>]*>\s*<\/div>)/is';
$replacement2 = '$1' . "\n" . <<<HTML
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Initial Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="0"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
HTML;

$content = preg_replace($pattern2, $replacement2, $content);

file_put_contents($file, $content);
echo "Injected HTML elements to product_add.php via regex.\n";
?>

