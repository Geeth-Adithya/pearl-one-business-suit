<?php
// api/config.php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the legacy db.php which handles session_start and PDO
require_once dirname(__DIR__) . '/includes/db.php';

// Track online status (last_seen)
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        // Prevent writing to DB on every single millisecond request to optimize, 
        // but for a small system, a simple UPDATE is perfectly fine.
        $stmt = $pdo->prepare("UPDATE Users SET last_seen = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (\Throwable $th) {}
}
?>
