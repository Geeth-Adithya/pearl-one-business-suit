<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

// 1. Remove old failed injection if present (it wasn't)
// 2. Properly replace the label with Regex to handle whitespace
$pattern = '/<label\s+for="supplier_ids"\s+class="block\s+text-sm\s+font-medium\s+text-gray-700\s+dark:text-gray-300">Suppliers<\/label>/s';

$newLabel = '<div class="flex justify-between items-center">
                        <label for="supplier_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers</label>
                        <?php if (!empty($_SESSION[\'module_supply\'])): ?>
                        <button type="button" onclick="document.getElementById(\'addSupplierModal\').classList.remove(\'hidden\'); document.getElementById(\'addSupplierModal\').classList.add(\'flex\');" class="text-xs text-brand-blue hover:underline flex items-center gap-1">
                            <ion-icon name="add-circle-outline"></ion-icon> Add New Supplier
                        </button>
                        <?php endif; ?>
                    </div>';

if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $newLabel, $content);
    file_put_contents($file, $content);
    echo "Label replaced successfully!\n";
} else {
    echo "Could not match the label with regex.\n";
}
?>
