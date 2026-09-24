<?php
require_once "includes/db.php";
// simulate session
$admin_id = 1; // Assuming created_by_admin_id is 1
$supplier_name = "Vidusha";

$unassigned_stmt = $pdo->prepare("SELECT id, name, item_code, supplier_name FROM Products WHERE created_by_admin_id = ? AND NOT FIND_IN_SET(?, REPLACE(supplier_name, ', ', ',')) ORDER BY name");
$unassigned_stmt->execute([$admin_id, $supplier_name]);
$res = $unassigned_stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($res) . "\n";
if(count($res) == 0) {
    // try to find created_by_admin_id
    $stmt = $pdo->query("SELECT DISTINCT created_by_admin_id FROM Products");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
