<?php
$file = 'includes/footer.php';
$content = file_get_contents($file);

// Replace the old number with the new one
$content = str_replace('+94 77 718 2632', '+94 77 576 8610', $content);
$content = str_replace('94777182632', '94775768610', $content);

file_put_contents($file, $content);
echo "Updated contact number in footer.\n";
?>

