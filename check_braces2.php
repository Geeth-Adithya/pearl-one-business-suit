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
        // echo "L$line: +1 -> $brace_count\n";
    } elseif ($token === '}') {
        $brace_count--;
        // echo "L$line: -1 -> $brace_count\n";
        if ($brace_count < 0) {
            echo "Unmatched } at line $line\n";
            exit;
        }
    }
    if ($line > 340 && ($token === '{' || $token === '}')) {
        echo "L$line: " . ($token === '{' ? '+' : '-') . " -> $brace_count\n";
    }
}
echo "Final: $brace_count\n";
?>

