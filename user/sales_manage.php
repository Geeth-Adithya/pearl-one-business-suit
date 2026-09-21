<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireModule('module_pos');

$admin_id_to_use = $_SESSION['role'] === 'admin' ? $_SESSION['user_id'] : $_SESSION['assigned_admin_id'];

$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Calculate Total Sales in date range
$sales_stmt = $pdo->prepare("SELECT SUM(total_amount) FROM Orders WHERE (user_id = ? OR user_id IN (SELECT id FROM Users WHERE assigned_admin_id = ?)) AND DATE(created_at) >= ? AND DATE(created_at) <= ?");
$sales_stmt->execute([$admin_id_to_use, $admin_id_to_use, $start_date, $end_date]);
$total_sales = $sales_stmt->fetchColumn() ?: 0;

// Fetch Items Sold in date range
$items_stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.price_at_purchase * oi.quantity) as total_revenue
    FROM Order_Items oi
    JOIN Orders o ON oi.order_id = o.id
    JOIN Products p ON oi.product_id = p.id
    WHERE (o.user_id = ? OR o.user_id IN (SELECT id FROM Users WHERE assigned_admin_id = ?)) 
      AND DATE(o.created_at) >= ? 
      AND DATE(o.created_at) <= ?
    GROUP BY p.id, p.name
    ORDER BY total_qty DESC
");
$items_stmt->execute([$admin_id_to_use, $admin_id_to_use, $start_date, $end_date]);
$items_sold = $items_stmt->fetchAll();

$full_width_layout = true;
require_once '../includes/header.php';
?>

<!-- Main Layout with Sidebar -->
<div class="flex h-[calc(100vh-4rem)] overflow-hidden">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Sales Manage</h1>
            <form method="GET" class="flex gap-2">
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm dark:text-white px-3 py-2">
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm dark:text-white px-3 py-2">
                <button type="submit" class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition text-sm">Filter</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm mb-8 border border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-500 dark:text-gray-200">Total Sales (<?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?>)</h2>
            <p class="text-4xl font-bold text-brand-blue dark:text-brand-lighter mt-2"><?= htmlspecialchars($shop_currency) ?> <?= number_format($total_sales, 2) ?></p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Items Sold</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Sold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($items_sold)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No items sold in this period.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items_sold as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-200"><?= htmlspecialchars($item['total_qty']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-blue dark:text-brand-lighter font-semibold"><?= htmlspecialchars($shop_currency) ?> <?= number_format($item['total_revenue'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

