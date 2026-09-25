<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list') {
            $stmt = $pdo->prepare("SELECT s.*, 
                (SELECT token FROM Supplier_Links sl WHERE sl.supplier_id = s.id AND sl.expires_at > NOW() ORDER BY sl.created_at DESC LIMIT 1) as active_link 
                FROM Suppliers s WHERE s.admin_id = ? ORDER BY s.id DESC");
            $stmt->execute([$admin_id]);
            $suppliers = $stmt->fetchAll();
            echo json_encode(['success' => true, 'suppliers' => $suppliers]);
        } elseif ($action === 'get' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM Suppliers WHERE id = ? AND admin_id = ?");
            $stmt->execute([$_GET['id'], $admin_id]);
            $supplier = $stmt->fetch();
            if ($supplier) {
                echo json_encode(['success' => true, 'supplier' => $supplier]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Supplier not found']);
            }
        } elseif ($action === 'requests_history') {
            $stmt = $pdo->prepare("
                SELECT sr.*, s.name as supplier_name, p.name as product_name
                FROM Supply_Requests sr
                JOIN Suppliers s ON sr.supplier_id = s.id
                JOIN Products p ON sr.product_id = p.id
                WHERE sr.admin_id = ?
                ORDER BY sr.created_at DESC
            ");
            $stmt->execute([$admin_id]);
            echo json_encode(['success' => true, 'requests' => $stmt->fetchAll()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $data['action'] ?? '';

        if ($action === 'add') {
            $name = trim($data['name'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');
            $product_types = trim($data['product_types'] ?? '');

            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO Suppliers (name, phone, email, address, product_types, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $email, $address, $product_types, $admin_id]);
            echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
        
        } elseif ($action === 'update') {
            $id = $data['id'] ?? 0;
            $name = trim($data['name'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');
            $product_types = trim($data['product_types'] ?? '');

            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE Suppliers SET name = ?, phone = ?, email = ?, address = ?, product_types = ? WHERE id = ? AND admin_id = ?");
            $stmt->execute([$name, $phone, $email, $address, $product_types, $id, $admin_id]);
            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
            
        } elseif ($action === 'delete') {
            $id = $data['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM Suppliers WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $admin_id]);
            echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
            
        } elseif ($action === 'generate_link') {
            $id = $data['id'] ?? 0;
            
            // Verify supplier belongs to admin
            $stmt = $pdo->prepare("SELECT id FROM Suppliers WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $admin_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Supplier not found']);
                exit;
            }

            // Generate a token
            $token = bin2hex(random_bytes(16));
            // Set expiration to 7 days from now
            $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

            $stmt = $pdo->prepare("INSERT INTO Supplier_Links (supplier_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$id, $token, $expires_at]);
            
            echo json_encode(['success' => true, 'token' => $token, 'message' => 'Link generated successfully']);
        } elseif ($action === 'get_assigned_products') {
            $supplier_id = $data['supplier_id'] ?? 0;
            // Get all products and whether they are assigned
            $stmt = $pdo->prepare("SELECT p.id, p.name, p.item_code, 
                                   IF(ps.supplier_id IS NOT NULL, 1, 0) as is_assigned
                                   FROM Products p 
                                   LEFT JOIN Product_Suppliers ps ON p.id = ps.product_id AND ps.supplier_id = ?
                                   WHERE p.created_by_admin_id = ?");
            $stmt->execute([$supplier_id, $admin_id]);
            echo json_encode(['success' => true, 'products' => $stmt->fetchAll()]);

        } elseif ($action === 'toggle_product_assignment') {
            $supplier_id = $data['supplier_id'] ?? 0;
            $product_id = $data['product_id'] ?? 0;
            $is_assigned = $data['is_assigned'] ?? false; // What it should become

            // Verify supplier
            $check = $pdo->prepare("SELECT id FROM Suppliers WHERE id = ? AND admin_id = ?");
            $check->execute([$supplier_id, $admin_id]);
            if (!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Supplier not found']);
                exit;
            }

            if ($is_assigned) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO Product_Suppliers (product_id, supplier_id) VALUES (?, ?)");
                $stmt->execute([$product_id, $supplier_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM Product_Suppliers WHERE product_id = ? AND supplier_id = ?");
                $stmt->execute([$product_id, $supplier_id]);
            }
            echo json_encode(['success' => true]);

        } elseif ($action === 'create_request') {
            $supplier_id = $data['supplier_id'] ?? 0;
            $note = trim($data['note'] ?? '');
            
            $items = $data['items'] ?? [];
            if (empty($items) && isset($data['product_id'])) {
                $items = [['product_id' => $data['product_id'], 'quantity' => $data['quantity']]];
            }

            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'No items provided']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO Supply_Requests (supplier_id, admin_id, product_id, quantity, note) VALUES (?, ?, ?, ?, ?)");
                foreach ($items as $item) {
                    $pid = $item['product_id'] ?? 0;
                    $qty = $item['quantity'] ?? 0;
                    if ($qty > 0 && $pid > 0) {
                        $stmt->execute([$supplier_id, $admin_id, $pid, $qty, $note]);
                    }
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
            // Email to supplier
            $supStmt = $pdo->prepare("SELECT email FROM Suppliers WHERE id = ?");
            $supStmt->execute([$supplier_id]);
            $supplierEmail = $supStmt->fetchColumn();
            
            if ($supplierEmail) {
                $subject = "New Supply Request - PearlOne Business Suite";
                $msg = "You have received a new supply request.\nNote: $note\n\nPlease check your supplier portal for more details.";
                $headers = "From: noreply@pearlone.com\r\n";
                // @mail($supplierEmail, $subject, $msg, $headers); // Commented out to prevent UI hanging on localhost
            }

            echo json_encode(['success' => true, 'message' => 'Requests sent to supplier']);

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

