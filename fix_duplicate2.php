<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$search = '/\s*\}\s*else\s*\{\s*\/\/\s*---\s*Security Fix: Do not allow unknown files to be uploaded as images ---\s*\$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";\s*\$uploaded = false;\s*\}/s';

$matches = [];
preg_match_all($search, $content, $matches, PREG_OFFSET_CAPTURE);

if (count($matches[0]) >= 2) {
    // The last two are the duplicates!
    // Wait, let's just replace the exact double occurrence:
    $double_search = '/\s*\}\s*else\s*\{\s*\/\/\s*---\s*Security Fix: Do not allow unknown files to be uploaded as images ---\s*\$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";\s*\$uploaded = false;\s*\}\s*\}\s*else\s*\{\s*\/\/\s*---\s*Security Fix: Do not allow unknown files to be uploaded as images ---\s*\$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";\s*\$uploaded = false;\s*\}/s';
    
    $single = <<<PHP
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$uploaded = false;
        }
PHP;

    $content = preg_replace($double_search, $single, $content);
    file_put_contents($file, $content);
    echo "Removed double duplicate\n";
} else {
    echo "Could not find double duplicate\n";
}

?>

