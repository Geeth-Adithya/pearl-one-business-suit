<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'get_maintenance') {
            // Anyone can view the maintenance message
            $stmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = 'global_maintenance_alert'");
            $stmt->execute();
            $val = $stmt->fetchColumn() ?: '';
            
            $msg = '';
            $time = '';
            $target_date = '';
            if ($val) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message'])) {
                    $msg = $decoded['message'];
                    $time = $decoded['updated_at'] ?? '';
                    $target_date = $decoded['target_date'] ?? '';
                } else {
                    $msg = $val; // fallback for plain text
                }
            }
            
            echo json_encode(['success' => true, 'message' => $msg, 'updated_at' => $time, 'target_date' => $target_date]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $data['action'] ?? '';

        // Only superadmin can set maintenance
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($action === 'set_maintenance') {
            $msg = trim($data['message'] ?? '');
            $target_date = $data['target_date'] ?? '';
            
            // Store as JSON with timestamp
            $value = json_encode([
                'message' => $msg,
                'target_date' => $target_date,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Check if key exists
            $stmt = $pdo->prepare("SELECT 1 FROM Settings WHERE setting_key = 'global_maintenance_alert'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $update = $pdo->prepare("UPDATE Settings SET setting_value = ? WHERE setting_key = 'global_maintenance_alert'");
                $update->execute([$value]);
            } else {
                $insert = $pdo->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES ('global_maintenance_alert', ?)");
                $insert->execute([$value]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Maintenance alert updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

