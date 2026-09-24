<?php
require_once "includes/db.php";
$stmt = $pdo->query("SELECT id, name, supplier_name FROM Products LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
