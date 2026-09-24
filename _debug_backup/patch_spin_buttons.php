<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

$search = <<<HTML
<style>
/* Hide scrollbar for POS UI */
HTML;

$replace = <<<HTML
<style>
/* Hide number input spinners */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] {
    -moz-appearance: textfield;
}

/* Hide scrollbar for POS UI */
HTML;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Added CSS to hide spin buttons\n";
?>

