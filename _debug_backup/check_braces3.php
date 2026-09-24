<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$tokens = token_get_all($content);
$brace_count = 0;
$line = 1;
foreach ($tokens as $token) {
    if (is_array($token)) {
        $line = $token[2];
    }
    if ($token === '{') {
        $brace_count++;
    } elseif ($token === '}') {
        $brace_count--;
    }
    if ($brace_count == 0 && ($token === '}' || $token === '{')) {
        echo "Count reached 0 at line $line\n";
    }
}
?>

