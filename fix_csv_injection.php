<?php
$file = 'admin/reports.php';
$content = file_get_contents($file);

$content = preg_replace(
    '/while \(\$row = \$products->fetch\(PDO::FETCH_ASSOC\)\) \{/',
    'while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
        // Prevent CSV Injection
        foreach ($row as &$cell) {
            if (isset($cell[0]) && in_array($cell[0], ["=", "+", "-", "@"])) {
                $cell = "\t" . $cell;
            }
        }',
    $content
);

$content = preg_replace(
    '/while \(\$row = \$orders->fetch\(PDO::FETCH_ASSOC\)\) \{/',
    'while ($row = $orders->fetch(PDO::FETCH_ASSOC)) {
        // Prevent CSV Injection
        foreach ($row as &$cell) {
            if (isset($cell[0]) && in_array($cell[0], ["=", "+", "-", "@"])) {
                $cell = "\t" . $cell;
            }
        }',
    $content
);

file_put_contents($file, $content);
echo "Added CSV Injection protection to reports.php";
?>
