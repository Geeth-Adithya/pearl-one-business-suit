<?php
$file = 'admin/manage_suppliers.php';
$content = file_get_contents($file);

// Replace the entire form tag start
$pattern = '/<form method="POST" action="manage_suppliers\.php<\?= \$edit_supplier \? \'\?edit=\' \. \$edit_supplier\[\'id\'\] : \'\' \?>[\s\S]*?"\s*class="space-y-4">/';

$replace = <<<HTML
        <form method="POST" action="manage_suppliers.php<?= \$edit_supplier ? '?edit=' . \$edit_supplier['id'] : '' ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '') ?>">
HTML;

$content = preg_replace($pattern, $replace, $content);
file_put_contents($file, $content);
echo "Regex patched manage_suppliers.php\n";
?>

