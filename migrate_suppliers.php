<?php
require 'includes/db.php';

$columns = [
    'phone' => 'VARCHAR(50)',
    'email' => 'VARCHAR(255)',
    'address' => 'TEXT',
    'product_types' => 'VARCHAR(255)'
];

foreach ($columns as $col => $type) {
    try {
        $pdo->exec("ALTER TABLE Suppliers ADD COLUMN $col $type NULL");
        echo "Added column $col.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column $col already exists.\n";
        } else {
            echo "Error adding $col: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration complete.\n";
?>

