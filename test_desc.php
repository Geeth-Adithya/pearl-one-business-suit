<?php
require "includes/db.php";
$stmt = $pdo->query("DESCRIBE Suppliers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
