<?php
require_once "includes/db.php";
$stmt = $pdo->query("SELECT id, supplier_name FROM Products LIMIT 10");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
