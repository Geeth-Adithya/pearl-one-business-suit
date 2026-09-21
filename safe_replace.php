<?php
$file = 'admin/manage_suppliers.php';
$content = file_get_contents($file);

$find = '<?php foreach ($suppliers as $supplier): ?>
                            <tr>';
$replace = '<?php foreach ($suppliers as $supplier): ?>
                            <tr onclick="if(!event.target.closest(\'a\') && !event.target.closest(\'form\')){ window.location=\'supplier_view.php?id=<?= $supplier[\'id\'] ?>\'; }" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">';

$content = str_replace($find, $replace, $content);

$pattern = '/<a href="supplier_view\.php\?id=<\?= \$supplier\[\'id\'\] \?>"[^>]*title="View Profile">\s*<ion-icon name="eye-outline"[^>]*><\/ion-icon>\s*<\/a>\s*/s';
$content = preg_replace($pattern, '', $content);

file_put_contents($file, $content);
echo "manage_suppliers.php updated";
?>
