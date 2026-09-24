<?php
require_once 'api/config.php';
$stmt = $pdo->query("SELECT email, username FROM Users LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
