<?php
require 'includes/db.php';
$stmt = $pdo->query('SELECT * FROM settings');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

