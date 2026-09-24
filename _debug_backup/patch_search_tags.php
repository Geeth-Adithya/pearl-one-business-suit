<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE Products ADD COLUMN search_keywords VARCHAR(255) NULL AFTER name");
    echo "Added search_keywords column.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>

