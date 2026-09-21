<?php
$file = 'admin/products.php';
$content = file_get_contents($file);

$search = "    \$where_clauses[] = \"(p.name LIKE ? OR p.item_code LIKE ?)\";\n    \$params[] = \"%trim(\$search_term)%\";\n    \$params[] = \"%trim(\$search_term)%\";";
// Wait, in my output above, it was:
/*
if (!empty($search_term)) {
    $where_clauses[] = "(p.name LIKE ? OR p.item_code LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}
*/
$search1 = "    \$where_clauses[] = \"(p.name LIKE ? OR p.item_code LIKE ?)\";";
$replace1 = "    \$where_clauses[] = \"(p.name LIKE ? OR p.item_code LIKE ? OR p.search_keywords LIKE ?)\";";
$content = str_replace($search1, $replace1, $content);

$search2 = <<<PHP
    \$params[] = "%\$search_term%";
    \$params[] = "%\$search_term%";
PHP;
$replace2 = <<<PHP
    \$params[] = "%\$search_term%";
    \$params[] = "%\$search_term%";
    \$params[] = "%\$search_term%";
PHP;
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Patched admin/products.php backend search.\n";
?>

