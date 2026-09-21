<?php
require "includes/db.php";
try {
    $pdo->exec("ALTER TABLE Suppliers 
        ADD COLUMN phone VARCHAR(20) NULL,
        ADD COLUMN email VARCHAR(100) NULL,
        ADD COLUMN address TEXT NULL,
        ADD COLUMN product_types TEXT NULL
    ");
    echo "Columns added to Suppliers.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
