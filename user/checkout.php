<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_pos');

requireLogin();

if (empty($_SESSION['cart'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$checkout_success = false;
$customer_name = trim($_POST['customer_name'] ?? '');
$contact_no = trim($_POST['contact_no'] ?? '');
$order_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    try {
        $pdo->beginTransaction();

        // 1. Calculate Total & Validate Stock
        $totalAmount = 0;
        $orderItemsData = [];

        $ids = implode(',', array_keys($_SESSION['cart']));
        $stmt = $pdo->query("SELECT id, name, attribute, selling_price, stock_quantity FROM Products WHERE id IN ($ids)");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $productMap = [];
        foreach ($products as $p) {
            $productMap[$p['id']] = $p;
        }

        foreach ($_SESSION['cart'] as $prod_id => $qty) {
            if (!isset($productMap[$prod_id])) {
                throw new Exception("Product ID $prod_id no longer exists.");
            }
            $p = $productMap[$prod_id];
            
            // Note: If you want strict stock validation, uncomment this:
            // if ($p['stock_quantity'] < $qty) {
            //     throw new Exception("Not enough stock for " . $p['name'] . ". Available: " . $p['stock_quantity']);
            // }

            $price = $p['selling_price'];
            $totalAmount += ($price * $qty);
            $orderItemsData[] = [
                'product_id' => $prod_id,
                'quantity' => $qty,
                'price' => $price
            ];
        }

        // 2. Create Order (Completed status for POS)
        // We use shop_name column to store customer_name for POS orders
        $stmt = $pdo->prepare("INSERT INTO Orders (user_id, shop_name, contact_no, total_amount, status) VALUES (?, ?, ?, ?, 'completed')");
        $stmt->execute([$_SESSION['user_id'], $customer_name, $contact_no, $totalAmount]);
        $order_id = $pdo->lastInsertId();

        // 3. Create Order Items and Deduct Stock
        $stmtItem = $pdo->prepare("INSERT INTO Order_Items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
        $stmtStock = $pdo->prepare("UPDATE Products SET stock_quantity = stock_quantity - ? WHERE id = ?");

        foreach ($orderItemsData as $item) {
            $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            // Deduct inventory
            $stmtStock->execute([$item['quantity'], $item['product_id']]);
        }

        $pdo->commit();

        // 4. Clear Cart
        $_SESSION['cart'] = [];
        $checkout_success = true;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Checkout failed: " . $e->getMessage();
    }
}

// If not submitted, calculate totals for display
if (!$checkout_success) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $pdo->query("SELECT id, name, attribute, selling_price, image_url FROM Products WHERE id IN ($ids)");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productMap = [];
    foreach ($products as $p) {
        $productMap[$p['id']] = $p;
    }
    
    $totalAmount = 0;
}

$full_width_layout = true;
require_once '../includes/header.php';
?>

<!-- Main Layout with Sidebar -->
<div class="flex min-h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] lg:overflow-hidden relative max-w-full">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto p-4 lg:p-10 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-4xl mx-auto">
    <?php if ($checkout_success): ?>
        <div class="bg-white dark:bg-gray-800 p-10 rounded-3xl shadow-sm text-center">
            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <ion-icon name="checkmark-circle" class="text-6xl"></ion-icon>
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-4">Sale Completed Successfully!</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-8">The inventory has been updated and the sale has been recorded.</p>
            <div class="flex justify-center gap-4">
                <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-8 py-3 rounded-xl transition font-semibold">
                    New Sale
                </a>
                <button onclick="window.open('print_bill.php?id=<?= $order_id ?>', 'PrintBill', 'width=400,height=600')" class="bg-[#1E3A8A] hover:bg-[#1e3a8a] text-white px-8 py-3 rounded-xl shadow-sm transition font-semibold flex items-center gap-2">
                    <ion-icon name="print-outline"></ion-icon> Print Bill
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Left: Checkout Form -->
            <div class="flex-1 bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                    Complete Sale
                </h2>

                <?php if ($error): ?>
                    <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-600 dark:text-red-400"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="checkout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="space-y-5 mb-8">
                        <div>
                            <label for="customer_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Customer Name (Optional)</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($customer_name) ?>"
                                maxlength="255" placeholder="Walk-in Customer"
                                class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-[#1E3A8A] focus:ring-[#1E3A8A] py-3 px-4">
                        </div>
                        <div>
                            <label for="contact_no" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Contact Number (Optional)</label>
                            <input type="text" id="contact_no" name="contact_no" value="<?= htmlspecialchars($contact_no) ?>"
                                maxlength="50" placeholder=""
                                class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-[#1E3A8A] focus:ring-[#1E3A8A] py-3 px-4">
                        </div>
                    </div>

                    <div class="flex justify-between items-center mt-10">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-900 dark:hover:text-white font-medium flex items-center gap-1">
                            <ion-icon name="arrow-back"></ion-icon> Back to POS
                        </a>
                        <button type="submit" class="bg-[#1E3A8A] hover:bg-[#1e3a8a] text-white px-8 py-3 rounded-xl shadow-md transition font-bold text-lg">
                            Confirm Sale
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Right: Order Summary -->
            <div class="w-full md:w-96 bg-gray-50 dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 h-fit">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Order Summary</h3>
                <div class="space-y-4 mb-6">
                    <?php 
                    $totalAmount = 0;
                    foreach ($_SESSION['cart'] as $prod_id => $qty): 
                        if (!isset($productMap[$prod_id])) continue;
                        $p = $productMap[$prod_id];
                        $lineTotal = $p['selling_price'] * $qty;
                        $totalAmount += $lineTotal;
                    ?>
                    <div class="flex justify-between items-center">
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($p['name'] . ($p['attribute'] ? ' - ' . $p['attribute'] : '')) ?></h4>
                            <p class="text-xs text-gray-500"><?= $qty ?> x <?= htmlspecialchars($shop_currency) ?> <?= number_format($p['selling_price'], 2) ?></p>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($shop_currency) ?> <?= number_format($lineTotal, 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Total</span>
                    <span class="text-2xl font-extrabold text-[#1E3A8A] dark:text-brand-lighter"><?= htmlspecialchars($shop_currency) ?> <?= number_format($totalAmount, 2) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
