<?php
// 1. Update HTML files
$files = ['admin/manage_admins.php', 'admin/index.php', 'admin/edit_admin.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Ensure we don't duplicate
        if (strpos($content, '<option value="7 Days">7 Days</option>') === false) {
            $content = str_replace(
                '<option value="Monthly">',
                "<option value=\"7 Days\">7 Days</option>\n                      <option value=\"Monthly\">",
                $content
            );
            
            // For edit_admin.php where it might have a PHP ternary for selection
            $content = str_replace(
                '<option value="Monthly" <?= $admin[\'subscription_plan\'] === \'Monthly\' ? \'selected\' : \'\' ?>>Monthly</option>',
                "<option value=\"7 Days\" <?= \$admin['subscription_plan'] === '7 Days' ? 'selected' : '' ?>>7 Days</option>\n                    <option value=\"Monthly\" <?= \$admin['subscription_plan'] === 'Monthly' ? 'selected' : '' ?>>Monthly</option>",
                $content
            );

            file_put_contents($file, $content);
            echo "Updated HTML in $file\n";
        }
    }
}

// 2. Update edit_admin.php logic
if (file_exists('admin/edit_admin.php')) {
    $content = file_get_contents('admin/edit_admin.php');
    
    $content = str_replace(
        "if (!in_array(\$subscription_plan, ['Monthly', 'Yearly'])) {",
        "if (!in_array(\$subscription_plan, ['7 Days', 'Monthly', 'Yearly'])) {",
        $content
    );
    
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
    
    file_put_contents('admin/edit_admin.php', $content);
    echo "Updated backend logic in admin/edit_admin.php\n";
}
?>

