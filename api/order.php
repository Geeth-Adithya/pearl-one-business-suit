<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    // Get Order Details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $admin_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Get Order Items
    $stmtItems = $pdo->prepare("
        SELECT oi.*, p.name, p.attribute 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmtItems->execute([$id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Get Shop Settings
    $stmtSettings = $pdo->prepare("
        SELECT setting_key, setting_value 
        FROM settings 
        WHERE setting_key IN (?, ?, ?)
    ");
    $stmtSettings->execute([
        "shop_name_$admin_id", 
        "shop_address_$admin_id", 
        "shop_contact_$admin_id"
    ]);
    
    $settings = [];
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $shopData = [
        'name' => $settings["shop_name_$admin_id"] ?? 'My Store',
        'address' => $settings["shop_address_$admin_id"] ?? '',
        'contact' => $settings["shop_contact_$admin_id"] ?? ''
    ];

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items,
        'shop' => $shopData
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
