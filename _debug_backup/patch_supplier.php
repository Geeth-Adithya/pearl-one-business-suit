<?php
$file = 'admin/supplier_view.php';
$content = file_get_contents($file);

// Insert POST handler right after fetching the supplier details
$postHandler = <<<'EOD'
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
EOD;

$content = preg_replace('/if \(!\$supplier\) \{\s*\$_SESSION\[\'error_message\'\] = "Supplier not found\.";\s*header\(\'Location: manage_suppliers\.php\'\);\s*exit;\s*\}/', $postHandler, $content);

// Update FIND_IN_SET query to handle spaces
$content = preg_replace('/FIND_IN_SET\(\?, supplier_name\)/', 'FIND_IN_SET(?, REPLACE(supplier_name, ", ", ","))', $content);

// Fetch unassigned products to populate the dropdown
$fetchUnassigned = <<<'EOD'
// Fetch unassigned products for this admin
$unassigned_stmt = $pdo->prepare("SELECT id, name, item_code FROM Products WHERE created_by_admin_id = ? AND NOT FIND_IN_SET(?, REPLACE(supplier_name, ', ', ',')) ORDER BY name");
$unassigned_stmt->execute([$_SESSION['user_id'], $supplier['name']]);
$unassigned_products = $unassigned_stmt->fetchAll();

// Generate WhatsApp message for low stock items
EOD;

$content = preg_replace('/\/\/ Generate WhatsApp message for low stock items/', $fetchUnassigned, $content);

// UI additions
// Right after <div class="lg:col-span-2"> add the Assign block
$assignBlock = <<<'EOD'
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
EOD;

$content = preg_replace('/<div class="lg:col-span-2">/', $assignBlock, $content);

// Add action header
$content = preg_replace('/<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Qty<\/th>/', '<th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>', $content);

// Add remove button in loop
$removeBtn = <<<'EOD'
<td class="px-6 py-4 whitespace-nowrap text-right">
                                    <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to remove this product from the supplier?');">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" name="remove_product" class="text-red-500 hover:text-red-700 transition" title="Remove">
                                            <ion-icon name="trash-outline" class="text-xl"></ion-icon>
                                        </button>
                                    </form>
                                </td>
EOD;
$content = preg_replace('/(<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold[^>]*>\s*<\?= \$p\[\'stock_quantity\'\] \?>\s*<\/td>)/', "$1\n$removeBtn", $content);

file_put_contents($file, $content);
echo "supplier_view.php patched successfully.";
?>
