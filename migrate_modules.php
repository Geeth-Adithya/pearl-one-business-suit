<?php
require 'includes/db.php';

$columns = [
    'module_pos' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `subscription_end_date`",
    'module_stock' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `module_pos`",
    'module_supply' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `module_stock`"
];

foreach ($columns as $column => $definition) {
    $checkColumn = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Users' AND COLUMN_NAME = ?");
    $checkColumn->execute([$column]);
    if ($checkColumn->fetchColumn() === false) {
        $pdo->exec("ALTER TABLE `Users` ADD COLUMN `$column` $definition");
        echo "Added $column.\n";
    } else {
        echo "$column already exists.\n";
    }
}
?>
