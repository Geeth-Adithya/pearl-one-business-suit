<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireAdmin();

$selected_supplier = $_GET['supplier'] ?? null;

if (!$selected_supplier) {
    die("No supplier specified.");
}

// Fetch data
$query = "
    SELECT 
        p.id, p.item_code, p.name, p.attribute,
        GROUP_CONCAT(c.name SEPARATOR ', ') as category_names,
        SUM(oi.quantity) as total_quantity_needed
    FROM Order_Items oi
    JOIN Orders o ON oi.order_id = o.id
    JOIN Products p ON oi.product_id = p.id
    LEFT JOIN Product_Categories pc ON p.id = pc.product_id
    LEFT JOIN Categories c ON pc.category_id = c.id
";
$params = [];

$conditions = "o.status = 'pending' AND FIND_IN_SET(?, p.supplier_name)";
$params[] = $selected_supplier;

// If the user is an admin (not superadmin), they should only see orders from their assigned users.
if ($_SESSION['role'] === 'admin') {
    $query .= " JOIN Users u ON o.user_id = u.id ";
    $conditions .= " AND u.assigned_admin_id = ?";
    $params[] = $_SESSION['user_id'];
}

$query .= " WHERE " . $conditions;
$query .= " GROUP BY p.id, p.item_code, p.name, p.attribute ORDER BY p.name";

$summary_stmt = $pdo->prepare($query);
$summary_stmt->execute($params);
$needed_products = $summary_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Supplier Needs:
        <?= htmlspecialchars($selected_supplier) ?>
    </title>
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="bg-white text-gray-900">
    <div class="max-w-4xl mx-auto p-8">
        <div class="mb-8 border-b pb-4">
            <h1 class="text-3xl font-bold">Supplier Needs Summary</h1>
            <h2 class="text-xl text-gray-600">Supplier:
                <?= htmlspecialchars($selected_supplier) ?>
            </h2>
            <p class="text-sm text-gray-500">Generated on:
                <?= date('Y-m-d H:i:s') ?>
            </p>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Categories</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Attribute</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity
                        Needed</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($needed_products as $product): ?>
                    <tr>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($product['name']) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= htmlspecialchars($product['item_code']) ?>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($product['category_names'] ?? 'N/A') ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($product['attribute'] ?? 'N/A') ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-800">
                            <?= htmlspecialchars($product['total_quantity_needed']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        window.onload = function () { window.print(); }
    </script>
</body>

</html>