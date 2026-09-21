<?php
$file = 'includes/db.php';
$content = file_get_contents($file);

// Replace dropping stock with adding stock
$search = <<<PHP
    // Migration to remove stock tracking
    \$checkStockColumn = \$pdo->prepare("SHOW COLUMNS FROM `Products` LIKE 'stock_quantity'");
    \$checkStockColumn->execute();
    if (\$checkStockColumn->rowCount() > 0) {
        \$pdo->exec("ALTER TABLE `Products` DROP COLUMN `stock_quantity`");
        // Also drop low_stock_threshold if it exists
        \$pdo->exec("ALTER TABLE `Products` DROP COLUMN IF EXISTS `low_stock_threshold`");
    }
PHP;

$replace = <<<PHP
    // Migration to add stock tracking
    \$checkStockColumn = \$pdo->prepare("SHOW COLUMNS FROM `Products` LIKE 'stock_quantity'");
    \$checkStockColumn->execute();
    if (\$checkStockColumn->rowCount() == 0) {
        \$pdo->exec("ALTER TABLE `Products` ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 0 AFTER `type`");
    }
PHP;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Replaced stock migration\n";
?>

