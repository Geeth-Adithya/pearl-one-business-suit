<?php
$content = file_get_contents('user/dashboard.php');
$content = str_replace("['module_stock']", "\$_SESSION['module_stock']", $content);
file_put_contents('user/dashboard.php', $content);

$checkout = file_get_contents('user/checkout.php');
$checkout = str_replace("['module_stock']", "\$_SESSION['module_stock']", $checkout);
file_put_contents('user/checkout.php', $checkout);
echo "Fixed session interpolation!";
