<?php
$files = [
    'user/dashboard.php',
    'user/sales_manage.php',
    'user/invoice_history.php',
    'user/checkout.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Find the require_once '../includes/header.php';
    // Replace it with $full_width_layout = true; \n require_once '../includes/header.php';
    
    $content = str_replace(
        "require_once '../includes/header.php';", 
        "\$full_width_layout = true;\nrequire_once '../includes/header.php';", 
        $content
    );
    file_put_contents($file, $content);
    echo "Patched \$full_width_layout in $file\n";
}
?>

