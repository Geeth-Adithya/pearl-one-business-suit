<?php
require 'includes/db.php';
$stmt = $pdo->query('SELECT * FROM Categories');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

