<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireRole('superadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        echo json_encode(['available' => false, 'message' => 'Username is required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        // Taken, suggest a new one
        $suggested = $username . mt_rand(100, 999);
        echo json_encode([
            'available' => false, 
            'message' => 'Username taken.', 
            'suggestion' => $suggested
        ]);
    } else {
        echo json_encode(['available' => true, 'message' => 'Username available.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>

