<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = null;
if ($_SESSION['role'] === 'admin') {
    $admin_id = $_SESSION['user_id'];
} else if ($_SESSION['role'] === 'user') {
    $stmt = $pdo->prepare("SELECT assigned_admin_id FROM Users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_id = $stmt->fetchColumn();
}

if (!$admin_id) {
    echo json_encode(['success' => false, 'message' => 'Admin ID not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE admin_id = ? ORDER BY expense_date DESC, id DESC");
        $stmt->execute([$admin_id]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'expenses' => $expenses]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Prevent generic staff from editing expenses unless specifically authorized, 
        // but for now, anyone in the shop can manage expenses.
        
        if ($action === 'create') {
            $category = trim($input['category'] ?? '');
            $amount = (float)($input['amount'] ?? 0);
            $expense_date = trim($input['expense_date'] ?? date('Y-m-d'));
            $description = trim($input['description'] ?? '');

            if (empty($category) || $amount <= 0 || empty($expense_date)) {
                echo json_encode(['success' => false, 'message' => 'Category, amount, and date are required.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO expenses (admin_id, category, amount, expense_date, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$admin_id, $category, $amount, $expense_date, $description]);
            
            echo json_encode(['success' => true, 'message' => 'Expense added successfully.']);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? 0);
            $category = trim($input['category'] ?? '');
            $amount = (float)($input['amount'] ?? 0);
            $expense_date = trim($input['expense_date'] ?? '');
            $description = trim($input['description'] ?? '');

            if (!$id || empty($category) || $amount <= 0 || empty($expense_date)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE expenses SET category = ?, amount = ?, expense_date = ?, description = ? WHERE id = ? AND admin_id = ?");
            $stmt->execute([$category, $amount, $expense_date, $description, $id, $admin_id]);
            
            echo json_encode(['success' => true, 'message' => 'Expense updated successfully.']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $admin_id]);
            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully.']);
            exit;
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
