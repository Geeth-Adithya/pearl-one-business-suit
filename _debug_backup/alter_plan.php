<?php
require 'includes/db.php';
$pdo->exec("ALTER TABLE Users MODIFY COLUMN subscription_plan ENUM('7 Days', 'Monthly', 'Yearly')");
echo "Altered Users table successfully\n";
?>

