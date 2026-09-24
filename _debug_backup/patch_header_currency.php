<?php
$file = 'includes/header.php';
$content = file_get_contents($file);

// Ensure $shop_currency is defined in header.php so it's available in all views
$inject = <<<PHP
<?php
// Initialize global shop currency if not already set
if (!isset(\$shop_currency)) {
    \$shop_currency = 'Rs';
    if (isset(\$_SESSION['user_id']) && isset(\$pdo)) {
        \$admin_id_to_use = \$_SESSION['assigned_admin_id'] ?? \$_SESSION['user_id'];
        \$stmt = \$pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
        \$stmt->execute(['shop_currency_' . \$admin_id_to_use]);
        \$val = \$stmt->fetchColumn();
        if (\$val) {
            \$shop_currency = \$val;
        }
    }
}
?>

PHP;

// Inject it right after the opening <?php tag or before <!DOCTYPE html>
$content = $inject . $content;

file_put_contents($file, $content);
echo "Injected shop_currency to header.php\n";
?>

