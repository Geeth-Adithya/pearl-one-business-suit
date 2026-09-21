<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_stock');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

// Fetch shop currency
$currStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
$currStmt->execute(['shop_currency_' . $_SESSION['user_id']]);
$shop_currency = $currStmt->fetchColumn() ?: 'Rs.';

// Handle deletion
if (isset($_GET['delete'])) {
    $product_id_to_delete = $_GET['delete'];

    // Get item_code for Google Sheet sync before deleting, only if the product belongs to this admin
    $itemCodeStmt = $pdo->prepare("SELECT item_code FROM Products WHERE id = ? AND created_by_admin_id = ?");
    $itemCodeStmt->execute([$product_id_to_delete, $_SESSION['user_id']]);
    $item_code = $itemCodeStmt->fetchColumn();

    if ($item_code) {
        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM Products WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$product_id_to_delete, $_SESSION['user_id']]);
    }

    // Send delete action to this admin's Google Sheet if configured
    if ($item_code) {
        $admin_webhook_key = 'google_sheet_webhook_admin_' . intval($_SESSION['user_id']);
        $webhookStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
        $webhookStmt->execute([$admin_webhook_key]);
        $webhook_url = $webhookStmt->fetchColumn();
        if ($webhook_url) {
            $postData = ['action' => 'delete', 'data' => ['item_code' => $item_code]];
            $ch = curl_init($webhook_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    header("Location: products.php?deleted=" . urlencode($item_code));
    exit;
}

if (isset($_GET['delete_all']) && $_GET['delete_all'] === '1') {
    // Delete all products for this admin
    $stmt = $pdo->prepare("DELETE FROM Products WHERE created_by_admin_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header("Location: products.php?deleted_all=1");
    exit;
}

$search_term = trim($_GET['search'] ?? '');
$category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;

$where_clauses = [];
$params = [];

if (!empty($search_term)) {
    $where_clauses[] = "(p.name LIKE ? OR p.item_code LIKE ? OR p.search_keywords LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if ($category_filter !== null) {
    $where_clauses[] = "p.id IN (SELECT product_id FROM Product_Categories WHERE category_id = ?)";
    $params[] = $category_filter;
}

// Only show products created by this admin
$where_clauses[] = "p.created_by_admin_id = ?";
$params[] = $_SESSION['user_id'];

$products_query = "
    SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names 
    FROM Products p 
    LEFT JOIN Product_Categories pc ON p.id = pc.product_id
    LEFT JOIN Categories c ON pc.category_id = c.id";

if (!empty($where_clauses)) {
    $products_query .= " WHERE " . implode(' AND ', $where_clauses);
}

$products_query .= " GROUP BY p.id ORDER BY p.created_at DESC";

// Pagination setup
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT p.id) FROM Products p";
if (isset($_GET['category']) && $_GET['category'] !== '') {
    $count_query .= " LEFT JOIN Product_Categories pc ON p.id = pc.product_id";
}
if (!empty($where_clauses)) {
    $count_query .= " WHERE " . implode(' AND ', $where_clauses);
}
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

$products_query .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($products_query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categoryQuery = "SELECT * FROM Categories";
$categoryParams = [];
if ($_SESSION['role'] !== 'superadmin') {
    $categoryQuery .= " WHERE admin_id = ? OR admin_id IS NULL";
    $categoryParams[] = $_SESSION['user_id'];
}
$categoryQuery .= " ORDER BY name";
$categoriesStmt = $pdo->prepare($categoryQuery);
$categoriesStmt->execute($categoryParams);
$categories = $categoriesStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Product Management</h1>
    </div>
    <div class="flex gap-2">
        <a href="manage_supplier_links.php" title="Share with Suppliers"
            class="bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="share-social-outline"></ion-icon> <span class="hidden sm:inline">Share</span>
        </a>
        <a href="google_sync.php" title="Google Sheets Sync"
            class="bg-green-600 hover:bg-green-700 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="sync-outline"></ion-icon> <span class="hidden sm:inline">Sync</span>
        </a>
        <a href="product_import.php" title="Bulk Import Products"
            class="bg-gray-600 hover:bg-gray-700 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="cloud-upload-outline"></ion-icon> <span class="hidden sm:inline">Import</span>
        </a>
        <a href="product_add.php" title="Add New Product"
            class="bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="add-circle-outline"></ion-icon> <span class="hidden sm:inline">Add Product</span>
        </a>
        <a href="products.php?delete_all=1" title="Delete All Products"
            class="bg-red-600 hover:bg-red-700 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2 confirm-delete-link"
            data-confirm-message="WARNING: Are you absolutely sure you want to delete ALL your products? This action cannot be undone!">
            <ion-icon name="trash-outline"></ion-icon> <span class="hidden sm:inline">Delete All</span>
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow mb-6">
    <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filter Products</h3>
        <form action="products.php" method="GET" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search_term ?? '') ?>"
                    placeholder="Name or Item Code"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label for="category"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                <select name="category" id="category"
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (isset($category_filter) && $category_filter == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" title="Filter"
                    class="bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm w-full md:w-auto flex items-center justify-center gap-2">
                    <ion-icon name="filter-outline"></ion-icon> <span class="hidden sm:inline">Filter</span></button>
                <a href="products.php"
                    class="text-center bg-gray-500 hover:bg-gray-600 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm w-full md:w-auto flex items-center justify-center gap-2"
                    title="Reset">
                    <ion-icon name="refresh-outline"></ion-icon> <span class="hidden sm:inline">Reset</span></a>
            </div>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Image</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Item Code</th>
                    
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Name</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Categories</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Purchasing Price</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Selling Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Quantity</th>
                    
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Supplier</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky right-0 bg-gray-50 dark:bg-gray-900 z-10 shadow-[-4px_0_10px_rgba(0,0,0,0.05)] dark:shadow-[-4px_0_10px_rgba(0,0,0,0.2)] border-l border-gray-200 dark:border-gray-700">
                        Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($p['image_url']): ?>
                                <?php
                                $img_src = $p['image_url'];
                                // Check if it's an external URL or a local file
                                if (!filter_var($img_src, FILTER_VALIDATE_URL)) {
                                    $img_src = BASE_URL . '/assets/uploads/' . htmlspecialchars($img_src);
                                }
                                ?>
                                <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                                    class="h-12 w-12 object-cover rounded-md cursor-pointer product-image-popup">
                            <?php else: ?>
                                <div
                                    class="h-12 w-12 bg-gray-200 dark:bg-gray-700 rounded-md flex items-center justify-center text-gray-400">
                                    <ion-icon name="image"></ion-icon>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($p['item_code']) ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            <?= htmlspecialchars($p['name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= htmlspecialchars($p['category_names'] ?? 'N/A') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($p['purchasing_price'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($p['selling_price'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($p['stock_quantity'] ?? 0) ?> <?= htmlspecialchars($p['unit'] ?? '') ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= htmlspecialchars($p['supplier_name'] ?: 'N/A') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium sticky right-0 bg-white dark:bg-gray-800 z-10 shadow-[-4px_0_10px_rgba(0,0,0,0.05)] dark:shadow-[-4px_0_10px_rgba(0,0,0,0.2)] border-l border-gray-200 dark:border-gray-700">
                            <a href="<?= BASE_URL ?>/admin/product_edit.php?id=<?= $p['id'] ?>"
                                class="text-brand-blue hover:text-brand-blueDark mr-3">Edit</a>
                            <a href="products.php?delete=<?= $p['id'] ?>"
                                class="text-red-600 hover:text-red-900 confirm-delete-link"
                                data-confirm-message="Are you sure you want to delete this product?">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($products) === 0): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No products
                            found. Add some!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-between">
        <div class="text-sm text-gray-700 dark:text-gray-300">
            Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
            <span class="font-medium"><?= min($offset + $limit, $total_products) ?></span> of 
            <span class="font-medium"><?= $total_products ?></span> products
        </div>
        <div class="flex gap-2">
            <?php 
                $queryString = '';
                if (isset($_GET['search'])) $queryString .= '&search=' . urlencode($_GET['search']);
                if (isset($_GET['category'])) $queryString .= '&category=' . urlencode($_GET['category']);
            ?>
            
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $queryString ?>" class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Previous</a>
            <?php endif; ?>
            
            <?php
            // Simple pagination showing a few pages around current
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a href="?page=<?= $i ?><?= $queryString ?>" class="px-3 py-1 <?= $i === $page ? 'bg-brand-blue text-white border-brand-blue' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?> border rounded-md text-sm">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= $queryString ?>" class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>