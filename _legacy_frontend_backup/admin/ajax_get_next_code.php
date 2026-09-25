<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['role'])) {
    echo json_encode(['next_code' => 'ITM-0001']);
    exit;
}

$prefix = $_GET['prefix'] ?? 'ITM';

$stmt = $pdo->prepare("SELECT item_code FROM Products WHERE item_code LIKE ?");
$stmt->execute([$prefix . '-%']);
$existing_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$max_num = 0;
foreach ($existing_codes as $code) {
    if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)(?:-\d+)?$/', $code, $matches)) {
        $max_num = max($max_num, (int)$matches[1]);
    }
}
$next_num = $max_num + 1;
echo json_encode(['next_code' => $prefix . '-' . str_pad($next_num, 4, '0', STR_PAD_LEFT)]);

