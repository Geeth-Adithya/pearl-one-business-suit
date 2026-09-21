<?php
require_once "includes/db.php";
$stmt = $pdo->query("SELECT email, role FROM Users WHERE role IN ('superadmin', 'admin')");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
