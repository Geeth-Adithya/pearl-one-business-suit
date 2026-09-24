<?php
$file = 'includes/auth.php';
$content = file_get_contents($file);

$search = "function requireLogin() {";
$replace = <<<PHP
// Global currency fetcher
\$shop_currency = 'Rs';
if (isset(\$_SESSION['user_id'])) {
    \$admin_id_to_use = \$_SESSION['assigned_admin_id'] ?? \$_SESSION['user_id'];
    \$stmt = \$pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
    \$stmt->execute(['shop_currency_' . \$admin_id_to_use]);
    \$val = \$stmt->fetchColumn();
    if (\$val) {
        \$shop_currency = \$val;
    }
}

function requireLogin() {
PHP;
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Added global \$shop_currency to auth.php\n";
?>

