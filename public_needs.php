<?php
require_once 'includes/db.php'; // For DB connection and BASE_URL

$token = $_GET['token'] ?? null;
if (!$token) {
    die("Invalid link.");
}

// Get link details from token
$link_stmt = $pdo->prepare("SELECT supplier_name, admin_id FROM Public_Links WHERE token = ?");
$link_stmt->execute([$token]);
$link_data = $link_stmt->fetch();

if (!$link_data) {
    die("This link is invalid or has expired.");
}

$selected_supplier = $link_data['supplier_name'];
$admin_id = $link_data['admin_id'];

// Fetch data (same query as suppliers.php)
$query = "
    SELECT 
        p.id, p.item_code, p.name, p.image_url, p.attribute, p.selling_price,
        p.type,
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

// If the link is associated with a specific admin, filter orders by their assigned users.
if ($admin_id) {
    $query .= " JOIN Users u ON o.user_id = u.id ";
    $conditions .= " AND u.assigned_admin_id = ?";
    $params[] = $admin_id;
}

$query .= " WHERE " . $conditions;
$query .= " GROUP BY p.id, p.item_code, p.name, p.image_url, p.attribute, p.selling_price, p.type ORDER BY p.name";

$summary_stmt = $pdo->prepare($query);
$summary_stmt->execute($params);
$needed_products = $summary_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Needs:
        <?= htmlspecialchars($selected_supplier) ?>
    </title>
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue: '#3B82F6',
                            blueDark: '#1D4ED8',
                        }
                    }
                }
            }
        }
    </script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- dom-to-image-more for modern image generation -->
    <script src="https://cdn.jsdelivr.net/npm/dom-to-image-more@2.10.1/dist/dom-to-image-more.min.js"></script>
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="<?= BASE_URL ?>/index.php" class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="<?= BASE_URL ?>/assets/images/logo.png" alt="StoreApp Logo">
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Supplier Needs Summary</h1>
                <p class="text-gray-500 mt-2">
                    A consolidated list of products needed from <strong>
                        <?= htmlspecialchars($selected_supplier) ?>
                    </strong> to fulfill pending orders.
                </p>
            </div>
            <button id="download-image-btn"
                class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition flex items-center gap-2">
                <ion-icon name="download-outline"></ion-icon>
                <span>Download as Image</span>
            </button>
        </div>

        <div id="needs-summary-content" class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Categories</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Attribute</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Quantity Needed</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($needed_products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($product['image_url']):
                                        $img_src = $product['image_url'];
                                        if (!filter_var($img_src, FILTER_VALIDATE_URL)) {
                                            $img_src = BASE_URL . '/assets/uploads/' . $img_src;
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($img_src) ?>" alt="Product"
                                            class="h-10 w-10 object-contain rounded">
                                    <?php else: ?>
                                        <div
                                            class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center text-gray-400">
                                            <ion-icon name="image"></ion-icon>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($product['item_code']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['category_names'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['attribute'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                    <?= htmlspecialchars($shop_currency ?? 'Rs') ?> <?= number_format((float) $product['selling_price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['type'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-blue-600">
                                    <?= htmlspecialchars($product['total_quantity_needed']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('download-image-btn').addEventListener('click', function () {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<ion-icon name="hourglass-outline" class="animate-spin"></ion-icon> <span>Generating...</span>';
            btn.disabled = true;

            const content = document.getElementById('needs-summary-content');

            // Options for dom-to-image-more for higher quality
            const options = {
                quality: 1.0,
                bgcolor: '#ffffff', // Explicitly set background to white
                // The library will attempt to inline images, which can help with CORS issues
            };

            domtoimage.toPng(content, options)
                .then(function (dataUrl) {
                    const link = document.createElement('a');
                    link.download = 'supplier-needs-<?= str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $selected_supplier)) ?>-<?= date('Y-m-d') ?>.png';
                    link.href = dataUrl;
                    link.click();

                    btn.innerHTML = originalText;
                    btn.disabled = false;
                })
                .catch(function (error) {
                    console.error('Image generation failed:', error);
                    alert('Sorry, something went wrong while generating the image. Please check the browser console for more details.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });
    </script>
</body>

</html>
