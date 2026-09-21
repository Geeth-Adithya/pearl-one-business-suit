<?php
require_once 'includes/db.php';

// If a user is logged in, update their last_seen status to NULL to mark them as offline.
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE Users SET last_seen = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // If the database update fails, we should still log the user out.
    }
}

session_unset();
session_destroy();
header('Location: login.php');
exit;