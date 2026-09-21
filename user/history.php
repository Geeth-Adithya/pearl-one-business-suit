<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireUser();

$stmt = $pdo->prepare("
    SELECT * FROM Orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Order History</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-2">Track the status of your past orders.</p>
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
                        Date</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total Amount</th>
                    <th
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Status</th>
                    <th
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Details</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            #<?= $o['id'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?= date('M j, Y H:i', strtotime($o['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                            <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($o['total_amount'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?= $o['status'] === 'completed' ? 'bg-green-100 text-green-800' :
                                ($o['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    ($o['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) ?>">
                                <?= ucfirst($o['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <!-- In a real app, this would link to an order detail page -->
                            <span class="text-gray-400 dark:text-gray-500 italic">View (WIP)</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($orders) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            You have not placed any orders yet. <br>
                            <a href="dashboard.php" class="text-brand-blue hover:underline mt-2 inline-block">Start
                                Shopping</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
