<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    // ---- LIST ----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
        $search = trim($_GET['search'] ?? '');
        $category_id = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $where = ['p.created_by_admin_id = ?'];
        $params = [$admin_id];

        if ($search) {
            $where[] = '(p.name LIKE ? OR p.item_code LIKE ? OR p.search_keywords LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($category_id) {
            $where[] = 'p.id IN (SELECT product_id FROM product_categories WHERE category_id = ?)';
            $params[] = $category_id;
        }

        $whereStr = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM products p WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT p.*, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as category_names
            FROM products p
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            LEFT JOIN categories c ON pc.category_id = c.id
            WHERE $whereStr
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Categories for filter
        $cats = $pdo->prepare("SELECT id, name FROM categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name");
        $cats->execute([$admin_id]);
        $categories = $cats->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'products' => $products,
            'categories' => $categories,
            'total' => $total,
            'page' => $page,
            'total_pages' => (int)ceil($total / $limit)
        ]);
        exit;
    }

    // ---- GET FORM DATA (categories + suppliers) ----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'form_data') {
        $cats = $pdo->prepare("SELECT id, name FROM categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name");
        $cats->execute([$admin_id]);
        $categories = $cats->fetchAll(PDO::FETCH_ASSOC);

        $sups = $pdo->prepare("SELECT id, name FROM suppliers WHERE admin_id = ? ORDER BY name");
        $sups->execute([$admin_id]);
        $suppliers = $sups->fetchAll(PDO::FETCH_ASSOC);

        // Next item code prefix
        $stmt = $pdo->prepare("SELECT item_code FROM products WHERE item_code LIKE 'ITM-%' AND created_by_admin_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$admin_id]);
        $last = $stmt->fetchColumn();
        $next_num = 1;
        if ($last && preg_match('/ITM-(\d+)/', $last, $m)) $next_num = (int)$m[1] + 1;
        $next_code = 'ITM-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

        echo json_encode(['success' => true, 'categories' => $categories, 'suppliers' => $suppliers, 'next_code' => $next_code]);
        exit;
    }

    // ---- GET SINGLE PRODUCT ----
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT p.*, GROUP_CONCAT(DISTINCT pc.category_id) as category_ids
            FROM products p
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            WHERE p.id = ? AND p.created_by_admin_id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$id, $admin_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) { echo json_encode(['success' => false, 'message' => 'Product not found']); exit; }
        $product['category_ids'] = $product['category_ids'] ? explode(',', $product['category_ids']) : [];
        echo json_encode(['success' => true, 'product' => $product]);
        exit;
    }

    // ---- CREATE ----
    if ($action === 'create') {
        $name = trim($input['name'] ?? '');
        $type = trim($input['type'] ?? 'Single');
        $attribute = trim($input['attribute'] ?? '');
        $item_code = trim($input['item_code'] ?? '');
        $search_keywords = trim($input['search_keywords'] ?? '');
        $purchasing_price = (float)($input['purchasing_price'] ?? 0);
        $margin_percent = (float)($input['margin_percent'] ?? 0);
        $selling_price = (float)($input['selling_price'] ?? 0);
        $stock_quantity = max(0, (int)($input['stock_quantity'] ?? 0));
        $low_stock_threshold = (int)($input['low_stock_threshold'] ?? 5);
        $unit = trim($input['unit'] ?? 'pcs');
        $description = trim($input['description'] ?? '');
        $supplier_name = trim($input['supplier_name'] ?? '');
        $image_url = trim($input['image_url'] ?? '');
        $category_ids = $input['category_ids'] ?? [];

        if (empty($name)) { echo json_encode(['success' => false, 'message' => 'Product name is required']); exit; }

        // Auto-generate item code if empty
        if (empty($item_code)) {
            $prefix = 'ITM';
            if (!empty($category_ids)) {
                $cat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $cat->execute([$category_ids[0]]);
                $cname = $cat->fetchColumn();
                if ($cname) $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cname), 0, 3));
                if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');
            }
            $stmt = $pdo->prepare("SELECT item_code FROM products WHERE item_code LIKE ? AND created_by_admin_id = ?");
            $stmt->execute([$prefix . '-%', $admin_id]);
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $max = 0;
            foreach ($existing as $c) {
                if (preg_match('/^' . preg_quote($prefix) . '-(\d+)/', $c, $m)) $max = max($max, (int)$m[1]);
            }
            $item_code = $prefix . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
        }

        // Check duplicate item code
        $check = $pdo->prepare("SELECT id FROM products WHERE item_code = ? AND created_by_admin_id = ?");
        $check->execute([$item_code, $admin_id]);
        if ($check->fetch()) { echo json_encode(['success' => false, 'message' => "Item code '$item_code' already exists"]); exit; }

        $stmt = $pdo->prepare("INSERT INTO products (item_code, name, search_keywords, attribute, type, stock_quantity, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, image_url, low_stock_threshold, created_by_admin_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$item_code, $name, $search_keywords, $attribute, $type, $stock_quantity, $purchasing_price, $margin_percent, $selling_price, $unit, $description, $supplier_name, $image_url, $low_stock_threshold, $admin_id]);
        $new_id = $pdo->lastInsertId();

        // Insert categories
        if (!empty($category_ids)) {
            $catStmt = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?,?)");
            foreach ($category_ids as $cid) $catStmt->execute([$new_id, (int)$cid]);
        }

        echo json_encode(['success' => true, 'message' => 'Product created!', 'id' => $new_id, 'item_code' => $item_code]);
        exit;
    }

    // ---- UPDATE ----
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $type = trim($input['type'] ?? 'Single');
        $attribute = trim($input['attribute'] ?? '');
        $search_keywords = trim($input['search_keywords'] ?? '');
        $purchasing_price = (float)($input['purchasing_price'] ?? 0);
        $margin_percent = (float)($input['margin_percent'] ?? 0);
        $selling_price = (float)($input['selling_price'] ?? 0);
        $stock_quantity = max(0, (int)($input['stock_quantity'] ?? 0));
        $low_stock_threshold = (int)($input['low_stock_threshold'] ?? 5);
        $unit = trim($input['unit'] ?? 'pcs');
        $description = trim($input['description'] ?? '');
        $supplier_name = trim($input['supplier_name'] ?? '');
        $image_url = trim($input['image_url'] ?? '');
        $category_ids = $input['category_ids'] ?? [];

        if (empty($name)) { echo json_encode(['success' => false, 'message' => 'Product name is required']); exit; }

        $stmt = $pdo->prepare("UPDATE products SET name=?, search_keywords=?, attribute=?, type=?, stock_quantity=?, purchasing_price=?, margin_percent=?, selling_price=?, unit=?, description=?, supplier_name=?, image_url=?, low_stock_threshold=? WHERE id=? AND created_by_admin_id=?");
        $stmt->execute([$name, $search_keywords, $attribute, $type, $stock_quantity, $purchasing_price, $margin_percent, $selling_price, $unit, $description, $supplier_name, $image_url, $low_stock_threshold, $id, $admin_id]);

        // Re-link categories
        $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?")->execute([$id]);
        if (!empty($category_ids)) {
            $catStmt = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?,?)");
            foreach ($category_ids as $cid) $catStmt->execute([$id, (int)$cid]);
        }

        echo json_encode(['success' => true, 'message' => 'Product updated!']);
        exit;
    }

    // ---- STOCK ADJUST ----
    if ($action === 'stock') {
        $id = (int)($input['id'] ?? 0);
        $change = (float)($input['change'] ?? 0); // positive = add, negative = reduce
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity + ?) WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$change, $id, $admin_id]);
        $newQty = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $newQty->execute([$id]);
        echo json_encode(['success' => true, 'new_qty' => (float)$newQty->fetchColumn()]);
        exit;
    }

    // ---- DELETE ----
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $pdo->prepare("DELETE FROM products WHERE id = ? AND created_by_admin_id = ?")->execute([$id, $admin_id]);
        echo json_encode(['success' => true, 'message' => 'Product deleted']);
        exit;
    }

    // ---- DELETE ALL ----
    if ($action === 'delete_all') {
        $pdo->prepare("DELETE FROM products WHERE created_by_admin_id = ?")->execute([$admin_id]);
        echo json_encode(['success' => true, 'message' => 'All products deleted']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

