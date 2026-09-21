<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
requireModule('module_pos');

$admin_id_to_use = $_SESSION['assigned_admin_id'] ?? $_SESSION['user_id'];

// Handle Delete Invoice
if (isset($_POST['delete_invoice']) && $_SESSION['role'] === 'admin') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF Token validation failed.');
    }
    $delete_id = (int)$_POST['delete_invoice'];
    
    // Verify invoice belongs to this shop
    $verify_stmt = $pdo->prepare("SELECT o.id FROM Orders o JOIN Users u ON o.user_id = u.id WHERE o.id = ? AND (u.assigned_admin_id = ? OR u.id = ?)");
    $verify_stmt->execute([$delete_id, $admin_id_to_use, $admin_id_to_use]);
    if ($verify_stmt->fetch()) {
        $pdo->prepare("DELETE FROM Orders WHERE id = ?")->execute([$delete_id]);
        $success = "Invoice #$delete_id deleted successfully.";
    } else {
        $error = "Invoice not found or unauthorized.";
    }
}

// Fetch Orders for the whole shop
$stmt = $pdo->prepare("
    SELECT o.*, u.username as cashier_name
    FROM Orders o
    JOIN Users u ON o.user_id = u.id
    WHERE u.assigned_admin_id = ? OR u.id = ?
    ORDER BY o.created_at DESC
    LIMIT 100
");
$stmt->execute([$admin_id_to_use, $admin_id_to_use]);
$invoices = $stmt->fetchAll();

// Fetch items for all invoices to show in modal
// Or we can load via AJAX, but for <100 invoices, pre-fetching is fine.
$order_ids = array_column($invoices, 'id');
$order_items = [];
if (!empty($order_ids)) {
    $in_clause = implode(',', array_fill(0, count($order_ids), '?'));
    $items_stmt = $pdo->prepare("
        SELECT oi.order_id, oi.quantity, p.name, oi.price_at_purchase as price 
        FROM Order_Items oi 
        JOIN Products p ON oi.product_id = p.id 
        WHERE oi.order_id IN ($in_clause)
    ");
    $items_stmt->execute($order_ids);
    foreach ($items_stmt->fetchAll() as $item) {
        $order_items[$item['order_id']][] = $item;
    }
}

$full_width_layout = true;
require_once '../includes/header.php';
?>

<!-- Main Layout with Sidebar -->
<div class="flex h-[calc(100vh-4rem)] overflow-hidden" x-data="{ viewModalOpen: false, currentInvoice: null, currentItems: [] }">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900 relative">
        <div class="mb-6 flex items-center gap-4">
            <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition" title="Go Back">
                <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Invoice History</h1>
                <p class="text-gray-500 mt-1">Recent bills from all cashiers in the shop.</p>
            </div>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="mb-4 rounded-md bg-green-100 px-4 py-3 text-sm text-green-800"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="mb-4 rounded-md bg-red-100 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No invoices found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): 
                                $items_json = htmlspecialchars(json_encode($order_items[$inv['id']] ?? []));
                                $inv_json = htmlspecialchars(json_encode($inv));
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white cursor-pointer hover:text-brand-blue" 
                                        @click="currentInvoice = <?= $inv_json ?>; currentItems = <?= $items_json ?>; viewModalOpen = true;">
                                        #<?= htmlspecialchars($inv['id']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-200"><?= date('M d, Y g:i A', strtotime($inv['created_at'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-200"><?= htmlspecialchars($inv['cashier_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-200"><?= htmlspecialchars($inv['shop_name'] ?: 'Guest') ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($shop_currency) ?> <?= number_format($inv['total_amount'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end gap-3">
                                        <button @click="currentInvoice = <?= $inv_json ?>; currentItems = <?= $items_json ?>; viewModalOpen = true;" class="text-gray-500 hover:text-brand-blue flex items-center justify-end gap-1">
                                            <ion-icon name="eye-outline"></ion-icon> View
                                        </button>
                                        <a href="print_bill.php?id=<?= $inv['id'] ?>" target="_blank" class="text-brand-blue hover:text-brand-blueDark flex items-center justify-end gap-1">
                                            <ion-icon name="print-outline"></ion-icon> Print
                                        </a>
                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this invoice?');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <input type="hidden" name="delete_invoice" value="<?= $inv['id'] ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 flex items-center gap-1">
                                                <ion-icon name="trash-outline"></ion-icon> Delete
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Details Modal -->
        <div x-show="viewModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="viewModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="viewModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="viewModalOpen" x-transition class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-xl leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                                    Invoice Details <span x-text="'#' + currentInvoice?.id"></span>
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-200 mb-4">
                                        Cashier: <strong x-text="currentInvoice?.cashier_name"></strong><br>
                                        Customer: <strong x-text="currentInvoice?.shop_name || 'Guest'"></strong><br>
                                        Date: <strong x-text="currentInvoice?.created_at"></strong>
                                    </p>
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th class="text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                                <th class="text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                                <th class="text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                                <th class="text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            <template x-for="item in currentItems" :key="item.name">
                                                <tr>
                                                    <td class="py-2 text-sm text-gray-900 dark:text-white" x-text="item.name"></td>
                                                    <td class="py-2 text-right text-sm text-gray-500" x-text="item.quantity"></td>
                                                    <td class="py-2 text-right text-sm text-gray-500" x-text="parseFloat(item.price).toFixed(2)"></td>
                                                    <td class="py-2 text-right text-sm font-medium text-gray-900 dark:text-white" x-text="(parseFloat(item.price) * item.quantity).toFixed(2)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    <div class="mt-4 text-right">
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">Total: <?= htmlspecialchars($shop_currency) ?> <span x-text="parseFloat(currentInvoice?.total_amount || 0).toFixed(2)"></span></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="viewModalOpen = false" class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
