<?php
$content = file_get_contents('admin/index.php');

// Insert PHP Logic for fetching low stock items
$phpInsert = <<<'PHP'
    $assigned_users_stmt->execute([$_SESSION['user_id']]);
    $assigned_users = $assigned_users_stmt->fetchAll();

    // Fetch Low Stock Items
    $low_stock_items = [];
    if (!empty($_SESSION['module_stock'])) {
        $lowStockStmt = $pdo->prepare("
            SELECT id, name, attribute, stock_quantity, low_stock_threshold, supplier_name 
            FROM Products 
            WHERE created_by_admin_id = ? 
            AND stock_quantity <= COALESCE(low_stock_threshold, 10)
            ORDER BY stock_quantity ASC
        ");
        $lowStockStmt->execute([$_SESSION['user_id']]);
        $low_stock_items = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch supplier phones if module_supply is active
        if (!empty($_SESSION['module_supply'])) {
            $supStmt = $pdo->prepare("SELECT name, phone FROM Suppliers WHERE admin_id = ?");
            $supStmt->execute([$_SESSION['user_id']]);
            $suppliers = $supStmt->fetchAll(PDO::FETCH_ASSOC);
            $supplierMap = [];
            foreach ($suppliers as $s) {
                $supplierMap[trim(strtolower($s['name']))] = $s['phone'];
            }
        }
    }
PHP;

$content = str_replace('$assigned_users = $assigned_users_stmt->fetchAll();', $phpInsert, $content);

// Insert HTML Section
$htmlInsert = <<<'HTML'
<!-- Quick Actions -->
HTML;

$htmlContent = <<<'HTML'
<!-- Low Stock Alerts -->
<?php if ($_SESSION['role'] === 'admin' && !empty($_SESSION['module_stock']) && count($low_stock_items) > 0): ?>
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <ion-icon name="warning" class="text-red-500"></ion-icon> Low Stock Alerts
    </h2>
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                    <?php if (!empty($_SESSION['module_supply'])): ?>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($low_stock_items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                            <?= htmlspecialchars($item['name'] . ($item['attribute'] ? ' - ' . $item['attribute'] : '')) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 dark:text-red-400">
                            <?= $item['stock_quantity'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            Below <?= $item['low_stock_threshold'] ?? 10 ?>
                        </td>
                        <?php if (!empty($_SESSION['module_supply'])): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <?php 
                            $supplier_phone = '';
                            $supplier_name = trim(explode(',', $item['supplier_name'] ?? '')[0]);
                            if ($supplier_name && isset($supplierMap[strtolower($supplier_name)])) {
                                $phone = preg_replace('/[^0-9]/', '', $supplierMap[strtolower($supplier_name)]);
                                if (strpos($phone, '0') === 0 && strlen($phone) === 10) {
                                    $supplier_phone = '94' . substr($phone, 1);
                                } elseif (strlen($phone) > 10) {
                                    $supplier_phone = $phone; // Assuming it already has country code
                                }
                            }
                            if ($supplier_phone):
                                $shop_name = $_SESSION['username'] ?? 'Shop';
                                $msg = "Hello $supplier_name,\n\nWe need a restock of:\n- " . $item['name'] . ($item['attribute'] ? ' - ' . $item['attribute'] : '') . "\n\nPlease arrange delivery.\n\nThank you,\n$shop_name";
                                $wa_link = "https://wa.me/{$supplier_phone}?text=" . urlencode($msg);
                            ?>
                                <a href="<?= $wa_link ?>" target="_blank" class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs font-bold transition">
                                    <ion-icon name="logo-whatsapp"></ion-icon> Request Restock
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-gray-400 italic">No phone/supplier</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
HTML;

$content = str_replace($htmlInsert, $htmlContent, $content);
file_put_contents('admin/index.php', $content);
echo "index.php updated!";
?>
