<?php
$file = 'user/print_bill.php';
$content = file_get_contents($file);

$search = "\$admin_id_to_use = (\$_SESSION['role'] === 'superadmin' || \$_SESSION['role'] === 'admin') ? \$_SESSION['user_id'] : \$_SESSION['admin_id'];";
$replace = "\$admin_id_to_use = (\$_SESSION['role'] === 'superadmin' || \$_SESSION['role'] === 'admin') ? \$_SESSION['user_id'] : (\$_SESSION['assigned_admin_id'] ?? 0);";

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Fixed assigned_admin_id in print_bill.php\n";
?>

