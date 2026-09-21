<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_supply');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

$supplier_id = (int)($_GET['id'] ?? 0);

if (!$supplier_id) {
    header('Location: manage_suppliers.php');
    exit;
}

// Fetch supplier details
$stmt = $pdo->prepare('SELECT * FROM Suppliers WHERE id = ? AND admin_id = ?');
$stmt->execute([$supplier_id, $_SESSION['user_id']]);
$supplier = $stmt->fetch();

if (!$supplier) {
    $_SESSION['error_message'] = "Supplier not found.";
    header('Location: manage_suppliers.php');
    exit;
}

// Handle Assign Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_product'])) {
    $product_id = (int)$_POST['product_id'];
    if ($product_id > 0) {
        // Fetch current supplier_name
        $stmt = $pdo->prepare("SELECT supplier_name FROM Products WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$product_id, $_SESSION['user_id']]);
        $curr = $stmt->fetchColumn();
        
        if ($curr !== false) {
            $suppliers = array_filter(array_map('trim', explode(',', $curr)));
            if (!in_array($supplier['name'], $suppliers)) {
                $suppliers[] = $supplier['name'];
                $new_suppliers = implode(', ', $suppliers);
                $update = $pdo->prepare("UPDATE Products SET supplier_name = ? WHERE id = ?");
                $update->execute([$new_suppliers, $product_id]);
                $_SESSION['success_message'] = "Product assigned successfully.";
            } else {
                $_SESSION['error_message'] = "Product is already assigned to this supplier.";
            }
        }
    }
    header("Location: supplier_view.php?id=" . $supplier_id);
    exit;
}

// Handle Remove Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product'])) {
    $product_id = (int)$_POST['product_id'];
    if ($product_id > 0) {
        $stmt = $pdo->prepare("SELECT supplier_name FROM Products WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$product_id, $_SESSION['user_id']]);
        $curr = $stmt->fetchColumn();
        
        if ($curr !== false) {
            $suppliers = array_filter(array_map('trim', explode(',', $curr)));
            $key = array_search($supplier['name'], $suppliers);
            if ($key !== false) {
                unset($suppliers[$key]);
                $new_suppliers = implode(', ', $suppliers);
                $update = $pdo->prepare("UPDATE Products SET supplier_name = ? WHERE id = ?");
                $update->execute([$new_suppliers, $product_id]);
                $_SESSION['success_message'] = "Product removed from supplier.";
            }
        }
    }
    header("Location: supplier_view.php?id=" . $supplier_id);
    exit;
}

// Fetch products assigned to this supplier
$product_stmt = $pdo->prepare("SELECT id, item_code, name, stock_quantity, purchasing_price FROM Products WHERE created_by_admin_id = ? AND FIND_IN_SET(?, REPLACE(supplier_name, ', ', ',')) ORDER BY name");
$product_stmt->execute([$_SESSION['user_id'], $supplier['name']]);
$products = $product_stmt->fetchAll();

// Fetch unassigned products for this admin
$unassigned_stmt = $pdo->prepare("SELECT id, name, item_code FROM Products WHERE created_by_admin_id = ? AND (supplier_name IS NULL OR supplier_name = '' OR NOT FIND_IN_SET(?, REPLACE(supplier_name, ', ', ','))) ORDER BY name");
$unassigned_stmt->execute([$_SESSION['user_id'], $supplier['name']]);
$unassigned_products = $unassigned_stmt->fetchAll();

// Generate WhatsApp message for low stock items
$low_stock_threshold = 10;
$low_stock_items = [];
foreach ($products as $p) {
    if ($p['stock_quantity'] <= $low_stock_threshold) {
        $low_stock_items[] = $p['name'] . ' (' . $p['stock_quantity'] . ' in stock)';
    }
}

$whatsapp_link = '';
if (!empty($supplier['phone']) && count($low_stock_items) > 0) {
    $clean_phone = preg_replace('/[^0-9]/', '', $supplier['phone']);
    // Format to international if local Sri Lankan number (starts with 0)
    if (strpos($clean_phone, '0') === 0 && strlen($clean_phone) === 10) {
        $clean_phone = '94' . substr($clean_phone, 1);
    }
    
    $shop_name = $_SESSION['username'] ?? 'Pearl Store';
    
    $message = "Hello {$supplier['name']},\n\nWe are running low on the following items:\n\n";
    foreach ($low_stock_items as $item) {
        $message .= "- $item\n";
    }
    $message .= "\nPlease arrange a restock.\n\nThank you,\n$shop_name";
    
    $whatsapp_link = "https://wa.me/{$clean_phone}?text=" . urlencode($message);
}

require_once '../includes/header.php';
?>

<div class="mb-8 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <a href="manage_suppliers.php" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white bg-gray-100 dark:bg-gray-800 p-2 rounded-full transition">
            <ion-icon name="arrow-back-outline" class="text-xl block"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($supplier['name']) ?></h1>
    </div>
    
    <div class="flex gap-3">
        <?php if ($whatsapp_link): ?>
            <a href="<?= $whatsapp_link ?>" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="logo-whatsapp"></ion-icon> Send Restock Alert
            </a>
        <?php elseif (!empty($supplier['phone'])): ?>
            <button disabled class="bg-green-600/50 cursor-not-allowed text-white px-4 py-2 rounded-md shadow-sm flex items-center gap-2" title="No low stock items">
                <ion-icon name="logo-whatsapp"></ion-icon> All Stock OK
            </button>
        <?php else: ?>
            <button disabled class="bg-gray-400 cursor-not-allowed text-white px-4 py-2 rounded-md shadow-sm flex items-center gap-2" title="No phone number saved">
                <ion-icon name="logo-whatsapp"></ion-icon> Phone Required
            </button>
        <?php endif; ?>
        
        <a href="manage_suppliers.php?edit=<?= $supplier['id'] ?>" class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
            <ion-icon name="create-outline"></ion-icon> Edit Profile
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Supplier Details Card -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow p-6">
            <div class="w-20 h-20 bg-brand-blue/10 text-brand-blue rounded-full flex items-center justify-center text-4xl mb-4 mx-auto">
                <ion-icon name="business-outline"></ion-icon>
            </div>
            <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-6"><?= htmlspecialchars($supplier['name']) ?></h2>
            
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone Number</p>
                    <p class="text-gray-900 dark:text-white font-medium mt-1">
                        <?= !empty($supplier['phone']) ? htmlspecialchars($supplier['phone']) : '<span class="text-gray-400 italic">Not provided</span>' ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email Address</p>
                    <p class="text-gray-900 dark:text-white font-medium mt-1">
                        <?= !empty($supplier['email']) ? htmlspecialchars($supplier['email']) : '<span class="text-gray-400 italic">Not provided</span>' ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Address</p>
                    <p class="text-gray-900 dark:text-white font-medium mt-1 whitespace-pre-wrap"><?= !empty($supplier['address']) ? htmlspecialchars($supplier['address']) : '<span class="text-gray-400 italic">Not provided</span>' ?></p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Types</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php if (!empty($supplier['product_types'])): ?>
                            <?php 
                                $types = explode(',', $supplier['product_types']);
                                foreach($types as $type): 
                            ?>
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-xs font-semibold">
                                    <?= htmlspecialchars(trim($type)) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-gray-400 italic text-sm">Not specified</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products List -->
        <div class="lg:col-span-2 space-y-6">
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4 rounded-md flex justify-between">
                <p class="text-green-700"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-md">
                <p class="text-red-700"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Assign Product Block -->
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <ion-icon name="add-circle-outline" class="text-green-500"></ion-icon> Assign Product to Supplier
                </h3>
            </div>
            <div class="p-6">
                <form method="POST" action="" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Product</label>
                        <select name="product_id" required class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">-- Choose a product --</option>
                            <?php foreach ($unassigned_products as $up): ?>
                                <option value="<?= $up['id'] ?>"><?= htmlspecialchars($up['item_code'] . ' - ' . $up['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_product" class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition whitespace-nowrap">
                        Assign Item
                    </button>
                </form>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <ion-icon name="cube-outline" class="text-brand-blue"></ion-icon> Supplied Products
                </h3>
                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                    <?= count($products) ?> Items
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Name</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purchasing Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($products as $p): ?>
                            <tr class="<?= $p['stock_quantity'] <= $low_stock_threshold ? 'bg-red-50 dark:bg-red-900/10' : '' ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($p['item_code']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($p['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-brand-blue dark:text-brand-lighter font-semibold">
                                    <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format($p['purchasing_price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold <?= $p['stock_quantity'] <= $low_stock_threshold ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' ?>">
                                    <?= $p['stock_quantity'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <ion-icon name="cube-outline" class="text-4xl text-gray-300 dark:text-gray-600 mb-2"></ion-icon>
                                    <p>No products are assigned to this supplier.</p>
                                    <p class="text-sm mt-1">Go to "Add Product" or "Manage Products" to assign items.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

