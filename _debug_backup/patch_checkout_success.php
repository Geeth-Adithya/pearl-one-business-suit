<?php
$file = 'user/checkout.php';
$content = file_get_contents($file);

// Replace $success with $checkout_success
$content = str_replace('$success = false;', '$checkout_success = false;', $content);
$content = str_replace('$success = true;', '$checkout_success = true;', $content);
$content = str_replace('if (!$success)', 'if (!$checkout_success)', $content);
$content = str_replace('if ($success):', 'if ($checkout_success):', $content);

file_put_contents($file, $content);
echo "Replaced success variable in checkout.php\n";
?>

