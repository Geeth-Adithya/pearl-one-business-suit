<?php
$file = 'user/print_bill.php';
$content = file_get_contents($file);

$search = "requireLogin();";
$replace = <<<PHP
requireLogin();

// Fetch shop currency
\$admin_id_to_use = (\$_SESSION['role'] === 'superadmin' || \$_SESSION['role'] === 'admin') ? \$_SESSION['user_id'] : \$_SESSION['admin_id'];
\$currStmt = \$pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
\$currStmt->execute(['shop_currency_' . \$admin_id_to_use]);
\$shop_currency = \$currStmt->fetchColumn() ?: 'Rs.';
PHP;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Injected shop currency into print_bill.php\n";
?>

