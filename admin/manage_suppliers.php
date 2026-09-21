<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_supply');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$edit_supplier = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $action = $_POST['action'] ?? '';
    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $product_types = trim($_POST['product_types'] ?? '');

    if (($action === 'add' || $action === 'update') && $name === '') {
        $error = 'Supplier name is required.';
    } elseif (($action === 'add' || $action === 'update') && strlen($name) > 255) {
        $error = 'Supplier name must be 255 characters or fewer.';
    } elseif ($action === 'add' || $action === 'update') {
        $duplicate_sql = 'SELECT id FROM Suppliers WHERE name = ? AND admin_id = ?';
        $duplicate_params = [$name, $_SESSION['user_id']];
        if ($action === 'update') {
            $duplicate_sql .= ' AND id != ?';
            $duplicate_params[] = $supplier_id;
        }
        $duplicate_stmt = $pdo->prepare($duplicate_sql);
        $duplicate_stmt->execute($duplicate_params);

        if ($duplicate_stmt->fetch()) {
            $error = 'A supplier with this name already exists.';
        } elseif ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO Suppliers (name, admin_id, phone, email, address, product_types) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $_SESSION['user_id'], $phone, $email, $address, $product_types]);
            $success = 'Supplier added successfully.';
        } else {
            $old_stmt = $pdo->prepare('SELECT name FROM Suppliers WHERE id = ? AND admin_id = ?');
            $old_stmt->execute([$supplier_id, $_SESSION['user_id']]);
            $old_supplier = $old_stmt->fetch();
            
            $stmt = $pdo->prepare('UPDATE Suppliers SET name = ?, phone = ?, email = ?, address = ?, product_types = ? WHERE id = ? AND admin_id = ?');
            $stmt->execute([$name, $phone, $email, $address, $product_types, $supplier_id, $_SESSION['user_id']]);
            
            // Sync product supplier names if the supplier name changed
            if ($old_supplier && $old_supplier['name'] !== $name) {
                $products_stmt = $pdo->prepare('SELECT id, supplier_name FROM Products WHERE created_by_admin_id = ? AND FIND_IN_SET(?, supplier_name)');
                $products_stmt->execute([$_SESSION['user_id'], $old_supplier['name']]);
                $update_product_stmt = $pdo->prepare('UPDATE Products SET supplier_name = ? WHERE id = ?');
                foreach ($products_stmt as $product) {
                    $product_suppliers = array_map('trim', explode(',', $product['supplier_name']));
                    $product_suppliers = array_map(function ($supplier) use ($old_supplier, $name) {
                        return $supplier === $old_supplier['name'] ? $name : $supplier;
                    }, $product_suppliers);
                    $update_product_stmt->execute([implode(', ', $product_suppliers), $product['id']]);
                }
            }
            $success = 'Supplier updated successfully.';
        }
    } elseif ($action === 'delete' && $supplier_id > 0) {
        $supplier_stmt = $pdo->prepare('SELECT name FROM Suppliers WHERE id = ? AND admin_id = ?');
        $supplier_stmt->execute([$supplier_id, $_SESSION['user_id']]);
        $supplier = $supplier_stmt->fetch();

        if (!$supplier) {
            $error = 'Supplier not found.';
        } else {
            $used_stmt = $pdo->prepare('SELECT COUNT(*) FROM Products WHERE created_by_admin_id = ? AND FIND_IN_SET(?, supplier_name)');
            $used_stmt->execute([$_SESSION['user_id'], $supplier['name']]);
            if ((int) $used_stmt->fetchColumn() > 0) {
                $error = 'This supplier cannot be deleted while it is assigned to a product.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM Suppliers WHERE id = ? AND admin_id = ?');
                $stmt->execute([$supplier_id, $_SESSION['user_id']]);
                $success = 'Supplier deleted successfully.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM Suppliers WHERE id = ? AND admin_id = ?');
    $stmt->execute([(int) $_GET['edit'], $_SESSION['user_id']]);
    $edit_supplier = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM Suppliers WHERE admin_id = ? ORDER BY name');
$stmt->execute([$_SESSION['user_id']]);
$suppliers = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Suppliers</h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <?= $edit_supplier ? 'Edit Supplier' : 'Add Supplier' ?>
                </h3>
            </div>
            
            <?php if ($error): ?>
                <div class="mx-6 mt-4 p-3 bg-red-100 text-red-700 border border-red-200 rounded-md">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mx-6 mt-4 p-3 bg-green-100 text-green-700 border border-green-200 rounded-md">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="<?= $edit_supplier ? 'update' : 'add' ?>">
                <?php if ($edit_supplier): ?>
                    <input type="hidden" name="supplier_id" value="<?= $edit_supplier['id'] ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supplier Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit_supplier['name'] ?? '') ?>" required
                        class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($edit_supplier['phone'] ?? '') ?>"
                        class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_supplier['email'] ?? '') ?>"
                        class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                    <textarea name="address" rows="2"
                        class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"><?= htmlspecialchars($edit_supplier['address'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Types (e.g. Beverages, Snacks)</label>
                    <input type="text" name="product_types" value="<?= htmlspecialchars($edit_supplier['product_types'] ?? '') ?>"
                        class="mt-1 block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <button type="submit"
                        class="w-full bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition">
                        <?= $edit_supplier ? 'Update Supplier' : 'Add Supplier' ?>
                    </button>
                    <?php if ($edit_supplier): ?>
                        <a href="manage_suppliers.php" class="ml-4 text-sm text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Suppliers</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name & Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Types</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr onclick="if(!event.target.closest('a') && !event.target.closest('form')){ window.location='supplier_view.php?id=<?= $supplier['id'] ?>'; }" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($supplier['name']) ?>
                                    </div>
                                    <?php if (!empty($supplier['phone'])): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <ion-icon name="call-outline" class="align-middle"></ion-icon> <?= htmlspecialchars($supplier['phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                    <?= htmlspecialchars($supplier['product_types'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="?edit=<?= $supplier['id'] ?>" class="text-brand-blue hover:text-brand-blueDark mr-3" title="Edit">
                                        <ion-icon name="create-outline" class="text-lg"></ion-icon>
                                    </a>
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="supplier_id" value="<?= $supplier['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 confirm-delete-btn" title="Delete" data-confirm-message="Are you sure you want to delete this supplier?">
                                            <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No suppliers found.
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