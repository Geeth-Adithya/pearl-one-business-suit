<?php
require "includes/db.php";
try {
    $pdo->exec("ALTER TABLE Products ADD COLUMN low_stock_threshold INT DEFAULT 10");
    echo "Column added successfully";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists";
    } else {
        echo "Error: " . $e->getMessage();
    }
}

