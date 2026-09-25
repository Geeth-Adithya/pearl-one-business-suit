<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireUser();

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names 
    FROM Products p 
    LEFT JOIN Product_Categories pc ON p.id = pc.product_id
    LEFT JOIN Categories c ON pc.category_id = c.id 
    WHERE p.id = ? AND p.created_by_admin_id = ?
    GROUP BY p.id");
$stmt->execute([$_GET['id'], $_SESSION['assigned_admin_id']]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: dashboard.php');
    exit;
}

$variants_stmt = $pdo->prepare("SELECT id, item_code, attribute, selling_price FROM Products WHERE name = ? AND created_by_admin_id = ? ORDER BY id");
$variants_stmt->execute([$product['name'], $_SESSION['assigned_admin_id']]);
$variants = $variants_stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-6">
    <a href="dashboard.php"
        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white flex items-center gap-1 w-max">
        <ion-icon name="arrow-back"></ion-icon> Back to Store
    </a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">

        <!-- Media Section -->
        <div class="space-y-4">
            <div
                class="aspect-w-1 aspect-h-1 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden flex items-center justify-center relative">
                <?php if ($product['image_url']): ?>
                    <?php
                    $img_src = $product['image_url'];
                    if (!filter_var($img_src, FILTER_VALIDATE_URL)) {
                        $img_src = BASE_URL . '/assets/uploads/' . htmlspecialchars($img_src);
                    }
                    ?>
                    <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                        class="object-contain w-full h-full">
                <?php else: ?>
                    <ion-icon name="image" class="text-6xl text-gray-400"></ion-icon>
                <?php endif; ?>
            </div>

            <?php if ($product['video_url']): ?>
                <?php
                $vid_src = $product['video_url'];
                if (!filter_var($vid_src, FILTER_VALIDATE_URL)) {
                    $vid_src = BASE_URL . '/assets/uploads/' . htmlspecialchars($vid_src);
                }
                ?>
                <div class="mt-4 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <video controls class="w-full">
                        <source src="<?= htmlspecialchars($vid_src) ?>">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details Section -->
        <div class="flex flex-col">
            <div class="mb-4">
                <span
                    class="inline-block bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs px-2 py-1 rounded mb-2">
                    <?= htmlspecialchars($product['category_names'] ?? 'General') ?>
                </span>
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Item Code: <span id="product-item-code">
                        <?= htmlspecialchars($product['item_code']) ?></span>
                </p>
            </div>

            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700 flex items-end gap-4">
                <span id="product-price" class="text-4xl font-extrabold text-brand-blue dark:text-brand-lighter">
                    <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($product['selling_price'], 2) ?>
                </span>
                <?php if ($product['unit']): ?>
                    <span class="text-gray-500 dark:text-gray-400 text-lg mb-1">per
                        <?= htmlspecialchars($product['unit']) ?></span>
                <?php endif; ?>
            </div>

            <div class="prose prose-sm sm:prose dark:prose-invert mb-8 max-w-none text-gray-600 dark:text-gray-300">
                <p><?= nl2br(htmlspecialchars($product['description'] ?? 'No description provided.')) ?></p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                <?php if ($product['type']): ?>
                    <div>
                        <span class="block text-gray-500 dark:text-gray-400 font-medium">Type</span>
                        <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($product['type']) ?></span>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="block text-gray-500 dark:text-gray-400 font-medium">Availability</span>
                    <span class="text-green-600 font-bold">Available</span>
                </div>
            </div>

            <!-- Add to Cart Form -->
            <form action="cart.php" method="POST" class="mt-auto flex gap-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="return_to" value="product.php?id=<?= $product['id'] ?>">

                <?php if (count($variants) > 1): ?>
                    <div class="flex-1">
                        <label for="product-attribute"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Attribute</label>
                        <select id="product-attribute"
                            class="mt-1 block w-full rounded-md border-black dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-3">
                            <?php foreach ($variants as $variant): ?>
                                <option value="<?= $variant['id'] ?>"
                                    data-price="<?= htmlspecialchars($variant['selling_price']) ?>"
                                    data-code="<?= htmlspecialchars($variant['item_code']) ?>" <?= (int) $variant['id'] === (int) $product['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($variant['attribute'] ?: $variant['item_code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="w-32">
                    <label for="qty" class="sr-only">Quantity</label>
                    <input type="number" id="qty" name="quantity" value="1" min="1"
                        class="block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-4 py-3 text-center">
                </div>

                <button type="submit"
                    class="flex-1 flex items-center justify-center gap-2 py-3 px-8 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition">
                    <ion-icon name="cart"></ion-icon> Add to Cart
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (count($variants) > 1): ?>
    <script>
        document.getElementById('product-attribute').addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            document.querySelector('input[name="product_id"]').value = selected.value;
            document.getElementById('product-price').textContent = '$' + Number(selected.dataset.price).toFixed(2);
            document.getElementById('product-item-code').textContent = selected.dataset.code;
        });
    </script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
