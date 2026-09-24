<?php
require "includes/db.php";
$stmt = $pdo->query("SELECT id, name, stock_quantity, low_stock_threshold FROM Products LIMIT 10");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($items);
