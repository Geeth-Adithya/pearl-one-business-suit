<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'products') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products_export_' . date('Ymd') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Item Code', 'Name', 'Type', 'Purchasing Price', 'Selling Price', 'Margin %', 'Stock', 'Supplier']);

    $products = $pdo->prepare("SELECT id, item_code, name, type, purchasing_price, selling_price, margin_percent, stock_quantity, supplier_name FROM Products WHERE created_by_admin_id = ? ORDER BY id DESC");
    $products->execute([$_SESSION['user_id']]);
    while ($row = $products->fetch(PDO::FETCH_ASSOC)) {
        // Prevent CSV Injection
        foreach ($row as &$cell) {
            if (isset($cell[0]) && in_array($cell[0], ["=", "+", "-", "@"])) {
                $cell = "\t" . $cell;
            }
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'orders') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export_' . date('Ymd') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'User ID', 'Total Amount', 'Status', 'Date']);

    $orders = $pdo->prepare("SELECT o.id, o.user_id, o.total_amount, o.status, o.created_at FROM Orders o JOIN Users u ON o.user_id = u.id WHERE u.assigned_admin_id = ? OR u.id = ? ORDER BY o.created_at DESC");
    $orders->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    while ($row = $orders->fetch(PDO::FETCH_ASSOC)) {
        // Prevent CSV Injection
        foreach ($row as &$cell) {
            if (isset($cell[0]) && in_array($cell[0], ["=", "+", "-", "@"])) {
                $cell = "\t" . $cell;
            }
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Reports & Export</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Generate and download CSV reports for your store data.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow flex flex-col items-center text-center">
        <ion-icon name="cube" class="text-5xl text-brand-blue mb-4"></ion-icon>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Product Inventory</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Export all products including stock levels, pricing, and
            details.</p>
        <a href="reports.php?export=products"
            class="bg-brand-blue hover:bg-brand-blueDark text-white px-6 py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="download"></ion-icon> Download CSV
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow flex flex-col items-center text-center">
        <ion-icon name="cash" class="text-5xl text-brand-blue mb-4"></ion-icon>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Order History</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Export all customer orders, statuses, and transaction amounts.
        </p>
        <a href="reports.php?export=orders"
            class="bg-brand-blue hover:bg-brand-blueDark text-white px-6 py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="download"></ion-icon> Download CSV
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>