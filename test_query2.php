<?php
require_once "includes/db.php";
$admin_id = 3;
$supplier_name = "Vidusha";

$unassigned_stmt = $pdo->prepare("SELECT id, name, item_code, supplier_name FROM Products WHERE created_by_admin_id = ? AND (supplier_name IS NULL OR supplier_name = '' OR NOT FIND_IN_SET(?, REPLACE(supplier_name, ', ', ','))) ORDER BY name LIMIT 10");
$unassigned_stmt->execute([$admin_id, $supplier_name]);
$res = $unassigned_stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($res) . "\n";
print_r($res);
