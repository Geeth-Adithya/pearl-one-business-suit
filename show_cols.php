<?php
require 'includes/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM Products');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

