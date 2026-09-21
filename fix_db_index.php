<?php
require "includes/db.php";
try {
    $pdo->exec("ALTER TABLE Products ADD INDEX (created_by_admin_id)");
    echo "Index created_by_admin_id added\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE Products ADD INDEX (created_at)");
    echo "Index created_at added\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE Product_Categories ADD INDEX (category_id)");
    echo "Index Product_Categories(category_id) added\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

