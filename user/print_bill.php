<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

// Fetch shop currency
$admin_id_to_use = ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin') ? $_SESSION['user_id'] : ($_SESSION['assigned_admin_id'] ?? 0);
$currStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
$currStmt->execute(['shop_currency_' . $admin_id_to_use]);
$shop_currency = $currStmt->fetchColumn() ?: 'Rs.';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$order_id) {
    die("Invalid Order ID");
}

$stmt = $pdo->prepare("SELECT * FROM Orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found or access denied.");
}

$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name, p.attribute 
    FROM Order_Items oi
    LEFT JOIN Products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll();

// Get settings (if they exist)
$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
if ($stmtSettings) {
    while ($row = $stmtSettings->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$admin_id_to_use = $_SESSION['assigned_admin_id'] ?? $_SESSION['user_id'];
$shop_name = $settings['shop_name_' . $admin_id_to_use] ?? ($settings['shop_name'] ?? 'My Store');
$shop_address = $settings['shop_address_' . $admin_id_to_use] ?? ($settings['shop_address'] ?? '123 Main Street, City');
$shop_phone = $settings['shop_contact_' . $admin_id_to_use] ?? ($settings['shop_contact'] ?? '+1 234 567 8900');

$salesperson = !empty($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$date = date('F j, Y', strtotime($order['created_at']));
$time = date('g:i A', strtotime($order['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $order['id'] ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f7f7f7;
            color: #000;
        }
        .receipt-container {
            max-width: 380px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .shop-name {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 5px;
            text-transform: lowercase;
        }
        .shop-address {
            font-size: 14px;
            color: #333;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        .shop-phone {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .total-box {
            border: 2px solid #000;
            border-radius: 8px;
            padding: 15px 10px;
            text-align: center;
            margin-bottom: 25px;
        }
        .total-box-label {
            font-size: 13px;
            font-weight: bold;
        }
        .total-box-amount {
            font-size: 32px;
            font-weight: 900;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 10px 0;
            border-bottom: 1px dashed #ccc;
        }
        th {
            text-align: left;
            font-size: 14px;
            border-bottom: 2px solid #000;
        }
        .item-name {
            font-size: 15px;
            font-weight: bold;
            display: block;
        }
        .item-meta {
            font-size: 13px;
            color: #555;
            display: block;
            margin-top: 3px;
        }
        .item-price {
            font-size: 15px;
            font-weight: bold;
            vertical-align: top;
            white-space: nowrap;
        }
        .totals-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .totals-table td {
            border: none;
            padding: 6px 0;
            font-size: 14px;
        }
        .totals-table .totals-label {
            text-align: right;
            padding-right: 20px;
        }
        .totals-table .totals-value {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }
        .grand-total {
            font-size: 18px !important;
            padding-bottom: 15px !important;
        }
        .grand-total-border {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }
        .footer-info {
            text-align: center;
            font-size: 13px;
            color: #444;
            line-height: 1.6;
        }
        
        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .receipt-container {
                max-width: 100%;
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 10px;
            }
            /* Hide print urls and pagination */
            @page { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-container">
        <!-- Header -->
        <div class="text-center">
            <div class="shop-name"><?= htmlspecialchars($shop_name) ?></div>
            <div class="shop-address"><?= nl2br(htmlspecialchars($shop_address)) ?></div>
            <div class="shop-phone"><?= htmlspecialchars($shop_phone) ?></div>
        </div>

        <div class="text-center" style="margin-bottom: 25px; font-weight: bold; font-size: 14px;">
            Receipt #1-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    $lineTotal = $item['price_at_purchase'] * $item['quantity'];
                ?>
                <tr>
                    <td>
                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                        <?php if ($item['attribute']): ?>
                            <span class="item-meta"><?= htmlspecialchars($item['attribute']) ?></span>
                        <?php endif; ?>
                        <?php if ($item['quantity'] > 1): ?>
                            <span class="item-meta"><?= $item['quantity'] ?> x <?= htmlspecialchars($shop_currency) ?> <?= number_format($item['price_at_purchase'], 2) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right item-price"><?= htmlspecialchars($shop_currency) ?> <?= number_format($lineTotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Subtotals / Tax / Total -->
        <div class="grand-total-border">
            <table class="totals-table">
                <tr>
                    <td class="totals-label text-gray-600">SUBTOTAL</td>
                    <td class="totals-value"><?= htmlspecialchars($shop_currency) ?> <?= number_format($order['total_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td class="totals-label text-gray-600">TAX (0%)</td>
                    <td class="totals-value"><?= htmlspecialchars($shop_currency) ?> 0.00</td>
                </tr>
                <tr>
                    <td class="totals-label grand-total font-bold">TOTAL</td>
                    <td class="totals-value grand-total font-bold"><?= htmlspecialchars($shop_currency) ?> <?= number_format($order['total_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td class="totals-label text-gray-600" style="padding-top: 15px;">CASH</td>
                    <td class="totals-value" style="padding-top: 15px;"><?= htmlspecialchars($shop_currency) ?> <?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer-info">
            <div>Date: <?= $date ?> at <?= $time ?></div>
            <div>Sold by: <?= htmlspecialchars($salesperson) ?></div>
            <div>Receipt: #1-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
        </div>
    </div>
</body>
</html>

