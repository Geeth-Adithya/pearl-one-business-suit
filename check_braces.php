<?php
$file = 'admin/product_edit.php';
$content = file_get_contents($file);

$braces = 0;
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, '<?php') !== false || strpos($line, '?>') !== false) {
        // ignore HTML outside PHP?
    }
    // Very naive, let's just use token_get_all
}

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
        if ($brace_count < 0) {
            echo "Unmatched } at line $line\n";
            exit;
        }
    }
}

if ($brace_count > 0) {
    echo "Missing } at end of file\n";
} else {
    echo "Braces match!\n";
}
?>

