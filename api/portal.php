<?php
// api/portal.php
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$token = $_GET['token'] ?? $data['token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Invalid link']);
    exit;
}

try {
    // 1. Verify token
    $stmt = $pdo->prepare("SELECT s.id, s.name, s.admin_id, s.phone, s.email, sl.expires_at 
                           FROM Supplier_Links sl 
                           JOIN Suppliers s ON sl.supplier_id = s.id 
                           WHERE sl.token = ?");
    $stmt->execute([$token]);
    $supplier = $stmt->fetch();

    if (!$supplier) {
        echo json_encode(['success' => false, 'message' => 'Link is invalid or does not exist']);
        exit;
    }
    
    if (strtotime($supplier['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This link has expired']);
        exit;
    }

    $supplier_id = $supplier['id'];
    $admin_id = $supplier['admin_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $data['action'] ?? '';
        
        if ($action === 'update_request') {
            $req_id = $data['request_id'] ?? 0;
            $status = $data['status'] ?? ''; 
            $reply = $data['reply'] ?? '';
            
            if (!in_array($status, ['accepted', 'rejected', 'fulfilled'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            if ($status === 'fulfilled') {
                $stmt = $pdo->prepare("UPDATE Supply_Requests SET status = ? WHERE id = ? AND supplier_id = ?");
                $stmt->execute([$status, $req_id, $supplier_id]);
                $statusText = 'Delivered';
                $msg = "Supplier {$supplier['name']} has marked your supply request as Delivered.";
            } else {
                $stmt = $pdo->prepare("UPDATE Supply_Requests SET status = ?, supplier_reply = ? WHERE id = ? AND supplier_id = ?");
                $stmt->execute([$status, $reply, $req_id, $supplier_id]);
                $statusText = $status === 'accepted' ? 'Accepted' : 'Rejected';
                $msg = "Supplier {$supplier['name']} has $statusText your supply request. Reply: $reply";
            }
            
            $notifStmt = $pdo->prepare("INSERT INTO notifications (admin_id, title, message, type) VALUES (?, ?, ?, ?)");
            $notifStmt->execute([$admin_id, "Supply Request $statusText", $msg, 'supply_request']);
            
            // Fetch admin email to send email notification
            $adminStmt = $pdo->prepare("SELECT email FROM Users WHERE id = ?");
            $adminStmt->execute([$admin_id]);
            $adminEmail = $adminStmt->fetchColumn();
            
            if ($adminEmail) {
                // simple mail() function
                $subject = "Supply Request $statusText - {$supplier['name']}";
                $headers = "From: noreply@pearlone.com\r\n";
                // @mail($adminEmail, $subject, $msg, $headers); // Commented out to prevent UI hanging on localhost
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
    }

    // 2. Fetch Shop Name for branding
    $shopStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
    $shopStmt->execute(['shop_name_' . $admin_id]);
    $shopName = $shopStmt->fetchColumn() ?: 'PearlOne Business Suite';

    // 3. Fetch "Needs"
    $needsQuery = "
        SELECT 
            p.id as product_id, p.item_code, p.name, p.image_url, 
            SUM(oi.quantity) as total_needed
        FROM Order_Items oi
        JOIN Orders o ON oi.order_id = o.id
        JOIN Products p ON oi.product_id = p.id
        JOIN Product_Suppliers ps ON p.id = ps.product_id
        WHERE o.status = 'pending' 
          AND ps.supplier_id = ? 
          AND p.created_by_admin_id = ?
        GROUP BY p.id, p.item_code, p.name, p.image_url
        ORDER BY p.name ASC
    ";
    $needsStmt = $pdo->prepare($needsQuery);
    $needsStmt->execute([$supplier_id, $admin_id]);
    $needs = $needsStmt->fetchAll();

    // 4. Fetch Explicit Supply Requests
    $reqStmt = $pdo->prepare("
        SELECT sr.id, sr.quantity, sr.note, sr.created_at, sr.status,
               p.name, p.item_code, p.image_url
        FROM Supply_Requests sr
        JOIN Products p ON sr.product_id = p.id
        WHERE sr.supplier_id = ? AND sr.status IN ('pending', 'accepted')
        ORDER BY sr.status DESC, sr.created_at DESC
    ");
    $reqStmt->execute([$supplier_id]);
    $requests = $reqStmt->fetchAll();

    // 5. Fetch Low Stock Items
    $lowStockStmt = $pdo->prepare("
        SELECT p.id as product_id, p.item_code, p.name, p.image_url, p.stock_quantity, p.low_stock_threshold
        FROM Products p
        JOIN Product_Suppliers ps ON p.id = ps.product_id
        WHERE ps.supplier_id = ? 
          AND p.created_by_admin_id = ? 
          AND p.stock_quantity <= p.low_stock_threshold
        ORDER BY p.name ASC
    ");
    $lowStockStmt->execute([$supplier_id, $admin_id]);
    $low_stock = $lowStockStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'shopName' => $shopName,
        'supplier' => [
            'name' => $supplier['name'],
            'phone' => $supplier['phone'],
            'email' => $supplier['email']
        ],
        'needs' => $needs,
        'requests' => $requests,
        'low_stock' => $low_stock
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
