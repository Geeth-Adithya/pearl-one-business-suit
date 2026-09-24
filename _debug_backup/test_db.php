<?php try { $pdo = new PDO('mysql:host=127.0.0.1;dbname=store_app_db', 'root', ''); echo 'Connected!'; } catch (Exception $e) { echo $e->getMessage(); } ?>
