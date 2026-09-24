<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search = <<<PHP
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$uploaded = false;
        }
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$uploaded = false;
        }
        if (\$uploaded) {
PHP;

$replace = <<<PHP
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$uploaded = false;
        }
        if (\$uploaded) {
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Fixed duplicate else in product_edit.php\n";
?>

