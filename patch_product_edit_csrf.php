<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search = <<<HTML
    <form action="product_edit.php?id=<?= \$product_id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
HTML;

$replace = <<<HTML
    <form action="product_edit.php?id=<?= \$product_id ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '') ?>">
HTML;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Patched product_edit.php\n";
?>

