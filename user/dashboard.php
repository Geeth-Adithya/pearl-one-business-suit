<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_pos');

requireLogin();
if (empty($_SESSION['module_pos']) && $_SESSION['role'] !== 'superadmin' && $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = 'You do not have access to this module.';
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Handle AJAX Cart Sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_cart') {
    $cart_data = json_decode($_POST['cart_data'], true);
    $_SESSION['cart'] = [];
    if (is_array($cart_data)) {
        foreach ($cart_data as $item) {
            $_SESSION['cart'][(int)$item['id']] = (int)$item['qty'];
        }
    }
    echo json_encode(['status' => 'success']);
    exit;
}

$admin_id_to_use = $_SESSION['assigned_admin_id'] ?? $_SESSION['user_id'];

// Fetch Categories
$categoriesStmt = $pdo->prepare("SELECT * FROM Categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name");
$categoriesStmt->execute([$admin_id_to_use]);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Products
$stmt = $pdo->prepare("SELECT * FROM Products WHERE created_by_admin_id = ? ORDER BY created_at DESC");
$stmt->execute([$admin_id_to_use]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format Image URLs for JS
foreach ($products as &$p) {
    if ($p['image_url']) {
        if (!filter_var($p['image_url'], FILTER_VALIDATE_URL)) {
            $p['image_url'] = BASE_URL . '/assets/uploads/' . htmlspecialchars($p['image_url']);
        }
    }
}
unset($p);

// Fetch Product Categories Mapping
$catMapStmt = $pdo->query("SELECT product_id, category_id FROM Product_Categories");
$product_categories_map = [];
while ($row = $catMapStmt->fetch(PDO::FETCH_ASSOC)) {
    $pid = $row['product_id'];
    if (!isset($product_categories_map[$pid])) {
        $product_categories_map[$pid] = [];
    }
    $product_categories_map[$pid][] = (int)$row['category_id'];
}

$full_width_layout = true;
require_once '../includes/header.php';
?>

<style>
/* Hide number input spinners */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] {
    -moz-appearance: textfield;
}

/* Hide scrollbar for POS UI */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<!-- Main Layout with Sidebar -->
<div class="flex min-h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] lg:overflow-hidden relative max-w-full">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- POS Content Area -->
    <div class="flex-1 lg:overflow-hidden p-3 lg:p-4 w-full max-w-full" x-data="posSystem()">
        <div class="flex flex-col lg:flex-row lg:h-full gap-4 max-w-full">
    <!-- Left Side: Items & Categories -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Header: Search & Filter -->
        <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0 mb-6 max-w-full">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Items</h1>
            <div class="flex gap-4 items-center">
                <div class="relative w-full sm:max-w-xs">
                    <input type="text" x-model="searchQuery" placeholder="Search..."
                        class="w-full rounded-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm pl-10 px-4 py-2 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] dark:text-white">
                    <ion-icon name="search-outline" class="absolute left-3 top-2.5 text-gray-400"></ion-icon>
                </div>
            </div>
        </div>

        <!-- Categories Pills -->
        <div class="flex overflow-x-auto gap-3 pb-2 mb-6 no-scrollbar">
            <button @click="selectedCategory = 0" 
                :class="selectedCategory === 0 ? 'bg-gray-900 text-white dark:bg-[#1E3A8A]' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                class="px-6 py-2 rounded-full text-sm font-semibold whitespace-nowrap shadow-sm transition">
                All Items
            </button>
            <?php foreach ($categories as $cat): ?>
                <button @click="selectedCategory = <?= $cat['id'] ?>"
                    :class="selectedCategory === <?= $cat['id'] ?> ? 'bg-gray-900 text-white dark:bg-[#1E3A8A]' : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                    class="px-6 py-2 rounded-full text-sm font-semibold whitespace-nowrap shadow-sm transition">
                    <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Products Grid -->
        <div class="flex-1 lg:overflow-y-auto lg:pr-2 no-scrollbar pb-10 max-w-full">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-4">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="bg-white dark:bg-gray-800 rounded-[1.5rem] p-3 lg:p-5 flex flex-col items-center justify-between shadow-sm hover:shadow-md transition cursor-pointer"
                         @click="addToCart(product)">
                        <div class="w-24 h-24 lg:w-32 lg:h-32 rounded-2xl bg-gray-50 dark:bg-gray-700 mb-3 lg:mb-4 overflow-hidden flex items-center justify-center">
                            <template x-if="product.image_url">
                                <img :src="product.image_url" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!product.image_url">
                                <ion-icon name="fast-food-outline" class="text-4xl text-gray-300"></ion-icon>
                            </template>
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-center text-sm lg:text-[15px] leading-tight mb-2" x-text="product.name + (product.attribute ? ' - ' + product.attribute : '')"></h3>
                        <?php if (!empty($_SESSION['module_stock'])): ?>
                          <div class="text-xs text-center mb-2 font-medium" :class="product.stock_quantity <= (product.low_stock_threshold || 10) ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'" x-text="'Stock: ' + product.stock_quantity"></div>
                        <?php endif; ?>
                        <div class="w-full flex justify-between items-center mt-auto">
                            <span class="font-bold text-gray-900 dark:text-white text-base whitespace-nowrap tracking-tight" x-text="'<?= htmlspecialchars($shop_currency) ?> ' + parseFloat(product.selling_price).toFixed(2)"></span>
                            <button class="bg-gray-900 dark:bg-[#1E3A8A] text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-700 transition shadow-sm" @click.stop="addToCart(product)">
                                <ion-icon name="add" class="text-lg"></ion-icon>
                            </button>
                        </div>
                    </div>
                </template>
                <template x-if="filteredProducts.length === 0">
                    <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-300">
                        <p>No items found.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Right Side: Current Order (Cart) -->
    <div class="w-full lg:w-[350px] bg-white dark:bg-gray-800 rounded-[2rem] p-6 shadow-sm flex flex-col lg:h-full shrink-0 mt-4 lg:mt-0 max-w-full">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Current Order</h2>
                <p class="text-sm text-gray-500 dark:text-gray-300"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
            </div>
        </div>
        
        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto no-scrollbar space-y-5 mb-6">
            <template x-if="cart.length === 0">
                <div class="text-center text-gray-400 mt-10">
                    <ion-icon name="cart-outline" class="text-6xl mb-2 opacity-50"></ion-icon>
                    <p class="text-sm">Your order is empty</p>
                </div>
            </template>
            
            <template x-for="item in cart" :key="item.id">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-gray-700 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        <template x-if="item.image_url">
                            <img :src="item.image_url" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!item.image_url">
                            <ion-icon name="fast-food-outline" class="text-xl text-gray-300"></ion-icon>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm truncate" x-text="item.name + (item.attribute ? ' - ' + item.attribute : '')"></h4>
                        <span class="text-gray-500 dark:text-gray-300 font-medium text-sm" x-text="'<?= htmlspecialchars($shop_currency) ?> ' + parseFloat(item.selling_price).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition" @click="decreaseQty(item)">
                            <ion-icon name="remove"></ion-icon>
                        </button>
                        <span class="font-semibold w-4 text-center text-sm dark:text-white" x-text="item.qty"></span>
                        <button class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition" @click="increaseQty(item)">
                            <ion-icon name="add"></ion-icon>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Totals -->
        <div class="pt-4 space-y-3 mb-6">
            <div class="flex justify-between text-gray-500 dark:text-gray-300 text-sm font-medium">
                <span>Subtotal</span>
                <span x-text="'<?= htmlspecialchars($shop_currency) ?> ' + subtotal.toFixed(2)"></span>
            </div>
            <div class="flex justify-between text-gray-500 dark:text-gray-300 text-sm font-medium">
                <span>Discount</span>
                <span><?= htmlspecialchars($shop_currency) ?> 0.00</span>
            </div>
            <div class="flex justify-between text-gray-500 dark:text-gray-300 text-sm font-medium border-b border-gray-100 dark:border-gray-700 pb-4">
                <span>Tax</span>
                <span><?= htmlspecialchars($shop_currency) ?> 0.00</span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 dark:text-white text-xl pt-2 pb-4 border-b border-gray-100 dark:border-gray-700">
                <span>Total</span>
                <span x-text="'<?= htmlspecialchars($shop_currency) ?> ' + subtotal.toFixed(2)"></span>
            </div>
            
            <div class="pt-2">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cash Tendered (<?= htmlspecialchars($shop_currency) ?>)</label>
                <input type="number" x-model.number="cashTendered" min="0" step="0.01" placeholder="Enter amount..."
                    class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-[#1E3A8A] focus:ring-[#1E3A8A] py-3 px-4 mb-3 text-lg font-bold">
                
                <div class="flex justify-between text-[#1E3A8A] dark:text-brand-lighter text-lg font-bold" x-show="cashTendered > 0">
                    <span>Balance</span>
                    <span x-text="'<?= htmlspecialchars($shop_currency) ?> ' + Math.max(0, cashTendered - subtotal).toFixed(2)"></span>
                </div>
            </div>
        </div>

        <button @click="proceedToCheckout()" 
            class="w-full bg-[#1E3A8A] hover:bg-[#1e3a8a] text-white font-bold py-4 rounded-[1.25rem] transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="cart.length === 0">
            Continue
        </button>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('posSystem', () => ({
        products: <?= json_encode($products) ?>,
        productCategories: <?= json_encode($product_categories_map) ?>,
        searchQuery: '',
        selectedCategory: 0,
        cart: [],
        cashTendered: 0,
        
        get filteredProducts() {
            return this.products.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || p.item_code.toLowerCase().includes(this.searchQuery.toLowerCase()) || (p.search_keywords && p.search_keywords.toLowerCase().includes(this.searchQuery.toLowerCase()));
                const matchesCategory = this.selectedCategory === 0 || (this.productCategories[p.id] && this.productCategories[p.id].includes(this.selectedCategory));
                return matchesSearch && matchesCategory;
            });
        },
        
        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (parseFloat(item.selling_price) * item.qty), 0);
        },
        
        addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            const currentQty = existing ? existing.qty : 0;
            
            <?php if (!empty($_SESSION['module_stock'])): ?>
            if (currentQty >= product.stock_quantity) {
                alert('Not enough stock available.');
                return;
            }
            <?php endif; ?>

            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({...product, qty: 1});
            }
        },
        
        increaseQty(item) {
            <?php if (!empty($_SESSION['module_stock'])): ?>
            if (item.qty >= item.stock_quantity) {
                alert('Not enough stock available.');
                return;
            }
            <?php endif; ?>
            item.qty++;
        },
        
        decreaseQty(item) {
            if (item.qty > 1) {
                item.qty--;
            } else {
                this.cart = this.cart.filter(i => i.id !== item.id);
            }
        },
        
        async proceedToCheckout() {
            if (this.cart.length === 0) return;
            // Sync cart with session via AJAX
            const formData = new FormData();
            formData.append('action', 'sync_cart');
            formData.append('cart_data', JSON.stringify(this.cart.map(i => ({id: i.id, qty: i.qty}))));
            
            try {
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                if (res.ok) {
                    window.location.href = 'checkout.php';
                }
            } catch (e) {
                alert('Error syncing cart.');
            }
        }    }));
});
</script>

    </div> <!-- End POS Content Area -->
</div> <!-- End Main Layout -->

<?php require_once '../includes/footer.php'; ?>
