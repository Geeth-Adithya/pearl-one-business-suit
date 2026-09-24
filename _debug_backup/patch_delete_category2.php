<?php
$file = 'admin/manage_categories.php';
$content = file_get_contents($file);

// Use a regex to replace the entire delete block
$pattern = '/\/\/ Handle Delete Category[\s\S]*?(?=\n\n\n|\$categoryQuery =)/m';

$replacement = <<<PHP
// Handle Delete Category
if (isset(\$_GET['delete'])) {
    \$id = \$_GET['delete'];
    
    \$deleteSql = "DELETE FROM Categories WHERE id = ?";
    \$deleteParams = [\$id];

    if (!\$isSuperAdmin) {
        \$deleteSql .= " AND admin_id = ?"; // Cannot delete global categories
        \$deleteParams[] = \$_SESSION['user_id'];
    }

    \$stmt = \$pdo->prepare(\$deleteSql);
    if (\$stmt->execute(\$deleteParams)) {
        \$_SESSION['success_message'] = "Category deleted.";
    } else {
        \$_SESSION['error_message'] = "Error deleting category.";
    }
    
    header('Location: manage_categories.php');
    exit;
}
PHP;

$content = preg_replace($pattern, $replacement, $content);

file_put_contents($file, $content);
echo "Replaced delete block completely.\n";
?>

