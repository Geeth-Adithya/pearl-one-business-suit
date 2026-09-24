<?php
$fp = fsockopen("smtp.gmail.com", 587, $errno, $errstr, 10);
if (!$fp) {
    echo "ERROR: $errstr ($errno)<br>\n";
} else {
    stream_set_timeout($fp, 5);
    echo "Connected.\n";
    $res = fread($fp, 1024);
    echo "Banner: " . $res . "\n";
    fclose($fp);
}
?>
