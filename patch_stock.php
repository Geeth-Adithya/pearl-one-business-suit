<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

$search = <<<HTML
            <!-- Inventory & Supplier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                <input type="text" name="unit" placeholder="e.g., pcs, kg, box"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
HTML;

$replace = <<<HTML
            <!-- Inventory & Supplier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                <input type="text" name="unit" placeholder="e.g., pcs, kg, box"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Initial Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="0"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
HTML;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Added stock quantity to product_add.php\n";
?>

