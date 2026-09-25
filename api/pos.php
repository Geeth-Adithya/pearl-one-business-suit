<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'get_products');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_products') {
        $search = trim($_GET['search'] ?? '');
        $category_id = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;

        $where = ['p.created_by_admin_id = ?'];
        $params = [$admin_id];

        if ($search) {
            $where[] = '(p.name LIKE ? OR p.item_code LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($category_id) {
            $where[] = 'p.id IN (SELECT product_id FROM product_categories WHERE category_id = ?)';
            $params[] = $category_id;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $pdo->prepare("
            SELECT p.id, p.item_code, p.name, p.attribute, p.type, p.selling_price, p.stock_quantity, p.image_url, 
                   GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as category_name
            FROM products p
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            LEFT JOIN categories c ON pc.category_id = c.id
            WHERE $whereStr
            GROUP BY p.id
            ORDER BY p.name ASC
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch categories for POS filter
        $cats = $pdo->prepare("SELECT id, name FROM categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name");
        $cats->execute([$admin_id]);
        $categories = $cats->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'products' => $products, 'categories' => $categories]);
        exit;
    }

    if ($action === 'checkout') {
        $customer_name = trim($input['customer_name'] ?? '');
        $contact_no = trim($input['contact_no'] ?? '');
        $payment_method = trim($input['payment_method'] ?? 'Cash');
        $card_type = trim($input['card_type'] ?? '');
        $card_last_four = trim($input['card_last_four'] ?? '');
        $items = $input['items'] ?? [];

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
            exit;
        }

        $pdo->beginTransaction();

        $totalAmount = 0;
        foreach ($items as $item) {
            $id = (int)$item['id'];
            $qty = (float)$item['qty'];

            $stmt = $pdo->prepare("SELECT selling_price, stock_quantity FROM products WHERE id = ? AND created_by_admin_id = ? FOR UPDATE");
            $stmt->execute([$id, $admin_id]);
            $p = $stmt->fetch();

            if (!$p) throw new Exception("Product ID $id not found.");
            
            // Strict stock check:
            if ($p['stock_quantity'] < $qty) {
                throw new Exception("Not enough stock for product ID $id. Only {$p['stock_quantity']} left.");
            }

            $price = (float)$p['selling_price'];
            $totalAmount += ($price * $qty);
        }

        // Note: old schema used shop_name column for customer name in POS
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, shop_name, contact_no, total_amount, payment_method, card_type, card_last_four, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Completed')");
        $stmt->execute([$admin_id, $customer_name, $contact_no, $totalAmount, $payment_method, $card_type ?: null, $card_last_four ?: null]);
        $order_id = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
        $stmtStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

        foreach ($items as $item) {
            $id = (int)$item['id'];
            $qty = (float)$item['qty'];
            
            // Get current price again to save accurately
            $priceStmt = $pdo->prepare("SELECT selling_price FROM products WHERE id = ?");
            $priceStmt->execute([$id]);
            $price = (float)$priceStmt->fetchColumn();

            $stmtItem->execute([$order_id, $id, $qty, $price]);
            $stmtStock->execute([$qty, $id]);
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Checkout successful!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

