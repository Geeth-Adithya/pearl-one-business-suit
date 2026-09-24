<?php
$file = 'admin/manage_categories.php';
$content = file_get_contents($file);

$search = <<<PHP
    \$countStmt = \$pdo->prepare(\$countQuery);
    \$countStmt->execute(\$countParams);
    if (\$countStmt->fetchColumn() > 1) {
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
    } else {
        \$_SESSION['error_message'] = "You cannot delete the last category.";
    }
PHP;

$replace = <<<PHP
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
PHP;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Removed the 'cannot delete last category' restriction.\n";
?>

