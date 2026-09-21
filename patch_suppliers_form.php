<?php
$file = 'admin/manage_suppliers.php';
$content = file_get_contents($file);

$search = <<<HTML
        <form method="POST" action="manage_suppliers.php<?= \$edit_supplier ? '?edit=' . \$edit_supplier['id'] : '' ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '') ?>">
"
            class="space-y-4">
HTML;

$replace = <<<HTML
        <form method="POST" action="manage_suppliers.php<?= \$edit_supplier ? '?edit=' . \$edit_supplier['id'] : '' ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '') ?>">
HTML;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed form action in manage_suppliers.php\n";
?>

