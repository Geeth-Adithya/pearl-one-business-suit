<?php
$content = file_get_contents('admin/index.php');
$content = str_replace(
    "<?php if (\$_SESSION['role'] === 'admin' && !empty(\$_SESSION['module_stock']) && count(\$low_stock_items) > 0): ?>",
    "<?php if (\$_SESSION['role'] === 'admin' && !empty(\$_SESSION['module_stock'])): ?>",
    $content
);
$content = str_replace(
    "<tbody class=\"bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700\">",
    "<tbody class=\"bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700\">\n                <?php if (empty(\$low_stock_items)): ?>\n                    <tr><td colspan=\"4\" class=\"px-6 py-8 text-center text-gray-500\">All products have sufficient stock.</td></tr>\n                <?php endif; ?>",
    $content
);
file_put_contents('admin/index.php', $content);
echo "Updated panel visibility!";
