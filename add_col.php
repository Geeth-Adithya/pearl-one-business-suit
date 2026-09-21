<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE `Products` ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 0 AFTER `type`");
    echo "Added successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

