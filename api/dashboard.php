<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];

try {
    // =============================================
    // SUPERADMIN: Show shops overview
    // =============================================
    if ($role === 'superadmin') {
        // Get all admin shops with their sales totals
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.username as shop_name,
                u.email,
                u.is_active,
                u.created_at,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_sales
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id AND o.status = 'Completed'
            WHERE u.role = 'admin'
            GROUP BY u.id
            ORDER BY total_sales DESC
        ");
        $stmt->execute();
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Overall platform stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $totalShops = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role NOT IN ('admin','superadmin')");
        $totalUsers = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Completed'");
        $totalSales = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $totalOrders = $stmt->fetchColumn() ?: 0;

        echo json_encode([
            'success' => true,
            'role' => 'superadmin',
            'stats' => [
                'totalShops' => (int)$totalShops,
                'totalUsers' => (int)$totalUsers,
                'totalSales' => (float)$totalSales,
                'totalOrders' => (int)$totalOrders,
            ],
            'shops' => $shops
        ]);

    // =============================================
    // ADMIN: Show their own shop's data
    // =============================================
    } else {
        $admin_id = $_SESSION['user_id'];

        // Total Sales for this admin's orders
        $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status = 'Completed'");
        $stmt->execute([$admin_id]);
        $totalSales = $stmt->fetchColumn() ?: 0;

        // Total Orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
        $stmt->execute([$admin_id]);
        $totalOrders = $stmt->fetchColumn() ?: 0;

        // Inventory Value
        $stmt = $pdo->prepare("SELECT SUM(stock_quantity * selling_price) FROM products WHERE created_by_admin_id = ?");
        $stmt->execute([$admin_id]);
        $totalInventoryValue = $stmt->fetchColumn() ?: 0;

        // Net Profit
        $stmt = $pdo->prepare("
            SELECT SUM(oi.quantity * (oi.price_at_purchase - p.purchasing_price))
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND o.status = 'Completed'
        ");
        $stmt->execute([$admin_id]);
        $netProfit = $stmt->fetchColumn() ?: 0;

        // Top 5 Products
        $stmt = $pdo->prepare("
            SELECT p.name, p.image_url, SUM(oi.quantity) as sold, SUM(oi.quantity * oi.price_at_purchase) as revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND o.status = 'Completed'
            GROUP BY p.id
            ORDER BY sold DESC
            LIMIT 5
        ");
        $stmt->execute([$admin_id]);
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent Sales
        $stmt = $pdo->prepare("
            SELECT o.id, o.created_at, u.username as customer_name, o.total_amount, o.status
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$admin_id]);
        $recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Chart Data
        $range = $_GET['range'] ?? '7days';
        if ($range === '3months') $days = 90;
        elseif ($range === '30days') $days = 30;
        else $days = 7;

        $chartStmt = $pdo->prepare("
            SELECT DATE(created_at) as date, SUM(total_amount) as total
            FROM orders
            WHERE user_id = ? AND status = 'Completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) ASC
        ");
        $chartStmt->execute([$admin_id, $days]);
        $chartDataRaw = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate full date range to ensure zero-sales days are plotted
        $chartLabels = [];
        $chartValues = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $displayDate = date('M j', strtotime("-$i days"));
            $chartLabels[] = $displayDate;
            
            $found = false;
            foreach ($chartDataRaw as $row) {
                if ($row['date'] === $date) {
                    $chartValues[] = (float)$row['total'];
                    $found = true;
                    break;
                }
            }
            if (!$found) $chartValues[] = 0;
        }

        // Category Sales Data (Doughnut Chart)
        $catStmt = $pdo->prepare("
            SELECT c.name, SUM(oi.quantity * oi.price_at_purchase) as total
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            LEFT JOIN categories c ON pc.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND o.status = 'Completed'
            GROUP BY c.id
            ORDER BY total DESC
        ");
        $catStmt->execute([$admin_id]);
        $catRaw = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $catLabels = [];
        $catValues = [];
        foreach ($catRaw as $row) {
            $catLabels[] = $row['name'] ?: 'Uncategorized';
            $catValues[] = (float)$row['total'];
        }

        echo json_encode([
            'success' => true,
            'role' => 'admin',
            'stats' => [
                'totalSales' => (float)$totalSales,
                'totalOrders' => (int)$totalOrders,
                'totalInventoryValue' => (float)$totalInventoryValue,
                'netProfit' => (float)$netProfit
            ],
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartValues
            ],
            'categoryChart' => [
                'labels' => $catLabels,
                'data' => $catValues
            ]
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
