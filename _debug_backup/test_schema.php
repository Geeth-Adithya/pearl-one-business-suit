<?php
require 'includes/db.php';
$_SESSION['user_id'] = 4;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'Harshika123';
$_SESSION['email'] = 'harshikageethanjali05@gmail.com';

ob_start();
include 'api/me.php';
$out = ob_get_clean();
echo $out;
