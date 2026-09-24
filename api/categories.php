<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$admin_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name");
        $stmt->execute([$admin_id]);
        echo json_encode(['success' => true, 'categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'create') {
        $name = trim($input['name'] ?? '');
        if (empty($name)) { echo json_encode(['success' => false, 'message' => 'Name required']); exit; }
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND (admin_id = ? OR admin_id IS NULL)");
        $check->execute([$name, $admin_id]);
        if ($check->fetch()) { echo json_encode(['success' => false, 'message' => 'Category already exists']); exit; }
        $stmt = $pdo->prepare("INSERT INTO categories (name, admin_id) VALUES (?, ?)");
        $stmt->execute([$name, $admin_id]);
        echo json_encode(['success' => true, 'message' => 'Category created', 'id' => $pdo->lastInsertId(), 'name' => $name]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        if (empty($name)) { echo json_encode(['success' => false, 'message' => 'Name required']); exit; }
        $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND admin_id = ?")->execute([$name, $id, $admin_id]);
        echo json_encode(['success' => true, 'message' => 'Category updated']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $pdo->prepare("DELETE FROM categories WHERE id = ? AND admin_id = ?")->execute([$id, $admin_id]);
        echo json_encode(['success' => true, 'message' => 'Category deleted']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

