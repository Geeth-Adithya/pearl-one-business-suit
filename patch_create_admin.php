<?php
$file = 'admin/create_admin.php';
$content = file_get_contents($file);

// Update allowed plans array
$content = str_replace(
    "if (!in_array(\$subscription_plan, ['Monthly', 'Yearly'])) {",
    "if (!in_array(\$subscription_plan, ['7 Days', 'Monthly', 'Yearly'])) {",
    $content
);

// Update date logic
$old_date = "\$end_date = (\$subscription_plan === 'Monthly') ? date('Y-m-d H:i:s', strtotime('+1 month')) : date('Y-m-d H:i:s', strtotime('+1 year'));";
$new_date = <<<PHP
    if (\$subscription_plan === '7 Days') {
        \$end_date = date('Y-m-d H:i:s', strtotime('+7 days'));
    } elseif (\$subscription_plan === 'Monthly') {
        \$end_date = date('Y-m-d H:i:s', strtotime('+1 month'));
    } else {
        \$end_date = date('Y-m-d H:i:s', strtotime('+1 year'));
    }
PHP;
$content = str_replace($old_date, $new_date, $content);

file_put_contents($file, $content);
echo "Updated create_admin.php backend logic\n";
?>

