<?php
$file = 'user/sales_manage.php';
$content = file_get_contents($file);

// Replace query 1
$search1 = "WHERE user_id IN (SELECT id FROM Users WHERE assigned_admin_id = ?)";
$replace1 = "WHERE (user_id = ? OR user_id IN (SELECT id FROM Users WHERE assigned_admin_id = ?))";
$content = str_replace($search1, $replace1, $content);

// Replace execute 1
$search2 = "\$sales_stmt->execute([\$admin_id_to_use, \$start_date, \$end_date]);";
$replace2 = "\$sales_stmt->execute([\$admin_id_to_use, \$admin_id_to_use, \$start_date, \$end_date]);";
$content = str_replace($search2, $replace2, $content);

// Replace query 2
$search3 = "WHERE o.user_id IN (SELECT id FROM Users WHERE assigned_admin_id = ?)";
$replace3 = "WHERE (o.user_id = ? OR o.user_id IN (SELECT id FROM Users WHERE assigned_admin_id = ?))";
$content = str_replace($search3, $replace3, $content);

// Replace execute 2
$search4 = "\$items_stmt->execute([\$admin_id_to_use, \$start_date, \$end_date]);";
$replace4 = "\$items_stmt->execute([\$admin_id_to_use, \$admin_id_to_use, \$start_date, \$end_date]);";
$content = str_replace($search4, $replace4, $content);

file_put_contents($file, $content);
echo "Patched sales queries to include admin's own sales.\n";
?>

