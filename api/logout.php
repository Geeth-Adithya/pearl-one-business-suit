<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    try {
        // Set last_seen to a past date so they immediately appear offline
        $stmt = $pdo->prepare("UPDATE Users SET last_seen = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {}
}

session_destroy();
echo json_encode(['success' => true]);
?>
