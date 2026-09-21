<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireUser();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

    if ($action === 'add' && $product_id > 0) {
        $qty = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $qty;
        } else {
            $_SESSION['cart'][$product_id] = $qty;
        }
        $return_to = $_POST['return_to'] ?? 'cart.php';
        if (!preg_match('/^(dashboard\.php|product\.php\?id=\d+)$/', $return_to)) {
            $return_to = 'cart.php';
        }
        header('Location: ' . $return_to);
        exit;
    }

    if ($action === 'update' && $product_id > 0) {
        $qty = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;
        $_SESSION['cart'][$product_id] = $qty;
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove' && $product_id > 0) {
        unset($_SESSION['cart'][$product_id]);
        header('Location: cart.php');
        exit;
    }
}

// Fetch cart items from DB
$cartItems = [];
$totalAmount = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $pdo->query("SELECT id, name, selling_price, image_url FROM Products WHERE id IN ($ids)");
    while ($row = $stmt->fetch()) {
        $row['cart_qty'] = $_SESSION['cart'][$row['id']];
        $row['subtotal'] = $row['selling_price'] * $row['cart_qty'];
        $totalAmount += $row['subtotal'];
        $cartItems[] = $row;
    }
}

require_once '../includes/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Shopping Cart</h1>
    <a href="dashboard.php" class="text-brand-blue dark:text-brand-lighter hover:underline font-medium">Continue
        Shopping</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Product</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Price</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Quantity</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="flex-shrink-0 h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden">
                                            <?php if ($item['image_url']):
                                                $img_src = $item['image_url'];
                                                if (!filter_var($img_src, FILTER_VALIDATE_URL)) {
                                                    $img_src = BASE_URL . '/assets/uploads/' . htmlspecialchars($img_src);
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($img_src) ?>" class="h-10 w-10 object-contain">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <ion-icon name="image"></ion-icon>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($item['selling_price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <form action="cart.php" method="POST" class="flex items-center gap-2">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['cart_qty'] ?>" min="1"
                                            class="w-16 border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-center text-sm">
                                        <button type="submit"
                                            class="text-brand-blue hover:text-brand-blueDark text-xs font-medium">Update</button>
                                    </form>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($item['subtotal'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form action="cart.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <button type="submit"
                                            class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            <ion-icon name="trash" class="text-lg"></ion-icon>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($cartItems)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <ion-icon name="cart-outline"
                                        class="text-6xl text-gray-300 dark:text-gray-600 mb-4"></ion-icon>
                                    <p class="text-lg text-gray-500 dark:text-gray-400">Your cart is empty.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow p-6 sticky top-6">
            <h2
                class="text-xl font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                Order Summary</h2>

            <div class="flex justify-between items-center mb-4 text-gray-600 dark:text-gray-400">
                <span>Subtotal</span>
                <span><?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($totalAmount, 2) ?></span>
            </div>
            <div
                class="flex justify-between items-center mb-6 text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-4">
                <span>Tax & Shipping</span>
                <span class="text-sm italic">Calculated at checkout</span>
            </div>

            <div class="flex justify-between items-center mb-8">
                <span class="text-lg font-bold text-gray-900 dark:text-white">Estimated Total</span>
                <span
                    class="text-2xl font-extrabold text-brand-blue dark:text-brand-lighter"><?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($totalAmount, 2) ?></span>
            </div>

            <?php if (!empty($cartItems)): ?>
                <a href="checkout.php"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                    Proceed to Checkout
                </a>
            <?php else: ?>
                <button disabled
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-bold text-white bg-gray-400 cursor-not-allowed">
                    Proceed to Checkout
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
