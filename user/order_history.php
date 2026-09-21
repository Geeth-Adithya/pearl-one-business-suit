<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Ensure only logged-in users can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    $_SESSION['error_message'] = "You must be logged in as a user to view your order history.";
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Fetch orders for the current user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, total_amount, status, created_at FROM Orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Order History</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-2">A list of all your past orders.</p>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Order ID
                    </th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Date
                    </th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total Amount
                    </th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            You have not placed any orders yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                #<?= htmlspecialchars($order['id']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= date('M d, Y', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($order['total_amount'], 2) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?= htmlspecialchars(ucfirst($order['status'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
