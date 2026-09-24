<?php
// api/portal.php
require_once 'config.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Invalid link']);
    exit;
}

try {
    // 1. Verify token and get supplier details
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

    // 2. Fetch Shop Name for branding
    $shopStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
    $shopStmt->execute(['shop_name_' . $admin_id]);
    $shopName = $shopStmt->fetchColumn() ?: 'PearlOne Business Suite';

    // 3. Fetch "Needs" (Pending orders containing assigned products)
    // We group by product to show total quantity needed
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
        SELECT sr.id, sr.quantity, sr.note, sr.created_at,
               p.name, p.item_code, p.image_url
        FROM Supply_Requests sr
        JOIN Products p ON sr.product_id = p.id
        WHERE sr.supplier_id = ? AND sr.status = 'pending'
        ORDER BY sr.created_at DESC
    ");
    $reqStmt->execute([$supplier_id]);
    $requests = $reqStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'shopName' => $shopName,
        'supplier' => [
            'name' => $supplier['name'],
            'phone' => $supplier['phone'],
            'email' => $supplier['email']
        ],
        'needs' => $needs,
        'requests' => $requests
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

