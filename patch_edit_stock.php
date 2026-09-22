<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search = <<<HTML
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea name="description" rows="3"
                class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"><?= htmlspecialchars(\$product['description']) ?></textarea>
        </div>
HTML;

$replace = <<<HTML
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                <input type="text" name="unit" placeholder=""
                    value="<?= htmlspecialchars(\$product['unit'] ?? '') ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="<?= htmlspecialchars(\$product['stock_quantity'] ?? 0) ?>"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea name="description" rows="3"
                class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2"><?= htmlspecialchars(\$product['description']) ?></textarea>
        </div>
HTML;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Added stock quantity and unit to product_edit.php\n";
?>

