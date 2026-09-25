<?php require "api/config.php"; print_r($pdo->query("SELECT * FROM Supply_Requests ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC)); ?> 
