<?php
require_once 'config.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $_GET['action'] ?? $data['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE admin_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$admin_id]);
        $notifications = $stmt->fetchAll();
        
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE admin_id = ? AND is_read = 0");
        $unreadStmt->execute([$admin_id]);
        $unread_count = $unreadStmt->fetchColumn();

        echo json_encode(['success' => true, 'notifications' => $notifications, 'unread_count' => $unread_count]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_read') {
        if (isset($data['id'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND admin_id = ?");
            $stmt->execute([$data['id'], $admin_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE admin_id = ?");
            $stmt->execute([$admin_id]);
        }
        echo json_encode(['success' => true]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'clear_all') {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error']);
}
