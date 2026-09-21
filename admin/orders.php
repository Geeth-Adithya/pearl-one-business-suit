<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_pos');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    if (isset($_POST['order_id'], $_POST['status'])) {
        $stmt = $pdo->prepare("UPDATE Orders o JOIN Users u ON o.user_id = u.id SET o.status = ? WHERE o.id = ? AND u.assigned_admin_id = ?");
        $stmt->execute([$_POST['status'], (int) $_POST['order_id'], $_SESSION['user_id']]);
        $_SESSION['success_message'] = "Order status updated.";
        header('Location: orders.php');
        exit;
    }

    if (isset($_POST['delete_order_id'])) {
        $stmt = $pdo->prepare("DELETE o FROM Orders o JOIN Users u ON o.user_id = u.id WHERE o.id = ? AND u.assigned_admin_id = ?");
        $stmt->execute([(int) $_POST['delete_order_id'], $_SESSION['user_id']]);
        $_SESSION['success_message'] = "Order deleted successfully.";
        header('Location: orders.php');
        exit;
    }

    if (isset($_POST['clear_orders'])) {
        $stmt = $pdo->prepare("DELETE o FROM Orders o JOIN Users u ON o.user_id = u.id WHERE u.assigned_admin_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['success_message'] = "All orders cleared successfully.";
        header('Location: orders.php');
        exit;
    }
}

// --- Date Filtering Logic ---
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$filter_month = $_GET['month'] ?? '';

$date_conditions = [];
$date_params = [];

if (!empty($filter_start) && !empty($filter_end)) {
    $date_conditions[] = "DATE(o.created_at) BETWEEN ? AND ?";
    $date_params[] = $filter_start;
    $date_params[] = $filter_end;
} elseif (!empty($filter_month)) {
    $date_conditions[] = "DATE_FORMAT(o.created_at, '%Y-%m') = ?";
    $date_params[] = $filter_month;
}
// ----------------------------

$query = "
    SELECT o.*, u.username 
    FROM Orders o 
    JOIN Users u ON o.user_id = u.id 
";
$params = [];

// If the user is an admin (not superadmin), they should only see orders from their assigned users.
if ($_SESSION['role'] === 'admin') {
    $query .= " WHERE u.assigned_admin_id = ?";
    $params[] = $_SESSION['user_id'];
    
    if (!empty($date_conditions)) {
        $query .= " AND " . implode(" AND ", $date_conditions);
        $params = array_merge($params, $date_params);
    }
} else {
    if (!empty($date_conditions)) {
        $query .= " WHERE " . implode(" AND ", $date_conditions);
        $params = array_merge($params, $date_params);
    }
}

$query .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Calculate Sales Overview
$total_sales_query = "SELECT SUM(o.total_amount) FROM Orders o JOIN Users u ON o.user_id = u.id WHERE u.assigned_admin_id = ? AND o.status = 'completed'";
$total_sales_params = [$_SESSION['user_id']];
if (!empty($date_conditions)) {
    $total_sales_query .= " AND " . implode(" AND ", $date_conditions);
    $total_sales_params = array_merge($total_sales_params, $date_params);
}
$total_sales_stmt = $pdo->prepare($total_sales_query);
$total_sales_stmt->execute($total_sales_params);
$total_sales = $total_sales_stmt->fetchColumn() ?: 0;

$user_sales_query = "SELECT u.username, SUM(o.total_amount) as sales FROM Orders o JOIN Users u ON o.user_id = u.id WHERE u.assigned_admin_id = ? AND o.status = 'completed'";
if (!empty($date_conditions)) {
    $user_sales_query .= " AND " . implode(" AND ", $date_conditions);
}
$user_sales_query .= " GROUP BY u.id";
$user_sales_stmt = $pdo->prepare($user_sales_query);
$user_sales_stmt->execute($total_sales_params);
$user_sales = $user_sales_stmt->fetchAll();

require_once '../includes/header.php';
?>

<!-- Sales Overview Section -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-r from-blue-500 to-brand-blue p-6 rounded-xl shadow-lg text-white">
        <h3 class="text-sm font-medium uppercase tracking-wider opacity-80 mb-1">Total Shop Sales</h3>
        <p class="text-4xl font-bold"><?= htmlspecialchars($shop_currency) ?> <?= number_format($total_sales, 2) ?></p>
    </div>
    
    <?php foreach ($user_sales as $stat): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow border border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Sales by <?= htmlspecialchars($stat['username']) ?></h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($shop_currency) ?> <?= number_format($stat['sales'], 2) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow mb-8">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Filter Sales</h3>
    <form action="orders.php" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">By Month</label>
            <input type="month" name="month" value="<?= htmlspecialchars($_GET['month'] ?? '') ?>" 
                   class="rounded-md border-black dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-blue focus:ring focus:ring-brand-blue focus:ring-opacity-50">
        </div>
        <div class="flex items-end gap-2">
            <span class="text-gray-500 pb-2">OR</span>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>"
                   class="rounded-md border-black dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-blue focus:ring focus:ring-brand-blue focus:ring-opacity-50">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>"
                   class="rounded-md border-black dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-blue focus:ring focus:ring-brand-blue focus:ring-opacity-50">
        </div>
        <div>
            <button type="submit" class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition">
                Apply Filter
            </button>
            <a href="orders.php" class="ml-2 text-gray-600 dark:text-gray-400 hover:underline">Clear</a>
        </div>
    </form>
</div>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Order Management</h1>
    <div class="flex items-center gap-2">
        <form action="orders.php" method="POST"
            onsubmit="return confirm('Clear all orders? This action cannot be undone.');">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <input type="hidden" name="clear_orders" value="1">
            <button type="submit" title="Clear All Orders"
                class="bg-red-600 hover:bg-red-700 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="trash-outline"></ion-icon> <span class="hidden sm:inline">Clear All</span>
            </button>
        </form>
        <a href="reports.php" title="Export Orders"
            class="bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="download-outline"></ion-icon> <span class="hidden sm:inline">Export Orders</span>
        </a>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Order ID</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Customer</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Shop Details</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total Amount</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Date</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Action</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">#<?= $o['id'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($o['username']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            <div class="font-medium"><?= htmlspecialchars($o['shop_name'] ?? 'Not provided') ?></div>
                            <div class="text-gray-500 dark:text-gray-400">
                                <?= nl2br(htmlspecialchars($o['shop_address'] ?? '')) ?>
                            </div>
                            <div class="text-gray-500 dark:text-gray-400"><?= htmlspecialchars($o['contact_no'] ?? '') ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($o['total_amount'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= date('M j, Y H:i', strtotime($o['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            <?= $o['status'] === 'completed' ? 'bg-green-100 text-green-800' :
                                ($o['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    ($o['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) ?>">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <form action="orders.php" method="POST" class="flex items-center gap-2">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status"
                                        class="text-sm border-black dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white py-1">
                                        <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending
                                        </option>
                                        <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>
                                            Processing</option>
                                        <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>
                                            Completed
                                        </option>
                                        <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>
                                            Cancelled
                                        </option>
                                    </select>
                                    <button type="submit" class="text-brand-blue hover:text-brand-blueDark p-2 rounded-md"
                                        title="Update Status">
                                        <ion-icon name="save-outline" class="text-lg"></ion-icon>
                                    </button>
                                </form>
                                <form action="orders.php" method="POST"
                                    onsubmit="return confirm('Delete this order? This action cannot be undone.');">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                    <input type="hidden" name="delete_order_id" value="<?= $o['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 p-2 rounded-md"
                                        title="Delete Order">
                                        <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($orders) === 0): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No orders
                            found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

