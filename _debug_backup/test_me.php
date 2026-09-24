<?php
require 'includes/db.php';
// Mock session
$_SESSION['user_id'] = 4; // Harshika123
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'Harshika123';
$_SESSION['email'] = 'test@test.com';

ob_start();
include 'api/me.php';
$out = ob_get_clean();
print_r($out);
