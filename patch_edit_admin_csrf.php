<?php
$file = 'admin/edit_admin.php';
$content = file_get_contents($file);

// Replace '<form ...>' with '<form ...> <input type="hidden" name="csrf_token" ...>'
$content = preg_replace(
    '/(<form[^>]*>)/i',
    '$1' . "\n" . '        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\') ?>">' . "\n",
    $content
);

file_put_contents($file, $content);
echo "Added CSRF tokens to edit_admin.php forms.\n";
?>

