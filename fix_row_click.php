<?php
$file = 'admin/manage_suppliers.php';
$content = file_get_contents($file);

// Replace <tr> with clickable row ONLY FOR DATA ROWS
// Wait, the easiest way is to find <?php foreach ($suppliers as $supplier): ?>\n<tr>
$content = preg_replace(
    '/(<\?php foreach \(\$suppliers as \$supplier\): \?>\s*)<tr>/', 
    '$1<tr onclick="if(!event.target.closest(\'a\') && !event.target.closest(\'button\')){ window.location=\'supplier_view.php?id=<?= $supplier[\'id\'] ?>\'; }" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">',
    $content
);

// Remove the eye icon link
$content = preg_replace(
    '/<a href="supplier_view\.php\?id=<\?= \$supplier\[\'id\'\] \?>"[^>]*title="View Profile">\s*<ion-icon name="eye-outline" class="text-lg"><\/ion-icon>\s*<\/a>/s',
    '',
    $content
);

file_put_contents($file, $content);
echo "manage_suppliers.php updated with clickable rows.";
?>
