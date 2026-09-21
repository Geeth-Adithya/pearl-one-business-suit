<?php
$files = ['user/print_bill.php', 'user/checkout.php', 'user/dashboard.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    // Replace >$ with >Rs. 
    $content = str_replace('>$', '>Rs. ', $content);
    // Replace x $ with x Rs. 
    $content = str_replace('x $', 'x Rs. ', $content);
    // For dashboard JS: '$' +  -> 'Rs. ' + 
    $content = str_replace("'$' +", "'Rs. ' +", $content);
    file_put_contents($file, $content);
}
echo "Replaced currency symbol successfully.\n";
?>

