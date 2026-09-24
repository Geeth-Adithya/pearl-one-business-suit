<?php
$file = 'includes/db.php';
$content = file_get_contents($file);

$lines = explode("\n", $content);
$new_lines = [];
$skip = false;
foreach ($lines as $line) {
    if (strpos($line, '// Migration to remove stock tracking') !== false) {
        $skip = true;
        // Instead of removing, add it
        $new_lines[] = "    // Migration to add stock tracking";
        $new_lines[] = "    \$checkStockColumn = \$pdo->prepare(\"SHOW COLUMNS FROM `Products` LIKE 'stock_quantity'\");";
        $new_lines[] = "    \$checkStockColumn->execute();";
        $new_lines[] = "    if (\$checkStockColumn->rowCount() == 0) {";
        $new_lines[] = "        \$pdo->exec(\"ALTER TABLE `Products` ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 0 AFTER `type`\");";
        $new_lines[] = "    }";
        continue;
    }
    if ($skip && strpos($line, '}') !== false && strpos($line, '    }') === 0) {
        $skip = false;
        continue;
    }
    if (!$skip) {
        $new_lines[] = $line;
    }
}
file_put_contents($file, implode("\n", $new_lines));
echo "Fixed DB migration!\n";
?>

