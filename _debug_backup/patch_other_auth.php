<?php
$files = ['user/checkout.php', 'user/print_bill.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace("requireUser();", "requireLogin();", $content);
        file_put_contents($file, $content);
        echo "Fixed auth in $file\n";
    }
}
?>

