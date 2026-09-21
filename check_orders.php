<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, user_id, status, created_at, total_amount FROM Orders ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

