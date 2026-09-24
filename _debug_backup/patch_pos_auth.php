<?php
$files = ['user/dashboard.php', 'user/checkout.php', 'user/print_bill.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $pattern = "/if \(empty\(\\\$_SESSION\['module_pos'\]\) && \\\$_SESSION\['role'\] !== 'superadmin' && \\\$_SESSION\['role'\] !== 'admin'\) \{.*?exit;\n\}/s";
        
        $replace = <<<PHP
if (\$_SESSION['role'] === 'superadmin') {
    \$_SESSION['error_message'] = 'Superadmins do not have access to the POS system.';
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}
if (empty(\$_SESSION['module_pos'])) {
    \$_SESSION['error_message'] = 'You do not have access to this module.';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
PHP;
        
        $content = preg_replace($pattern, $replace, $content);
        file_put_contents($file, $content);
        echo "Fixed POS auth for $file\n";
    }
}
?>

