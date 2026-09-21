<?php
$file = 'admin/reports.php';
$content = file_get_contents($file);

$content = preg_replace(
    '/\$orders = \$pdo->prepare\("SELECT (.*?) FROM Orders WHERE shop_id = \? ORDER BY created_at DESC"\);\s*\$orders->execute\(\[\$_SESSION\[\'user_id\'\]\]\);/s',
    '$orders = $pdo->prepare("SELECT o.id, o.user_id, o.total_amount, o.status, o.created_at FROM Orders o JOIN Users u ON o.user_id = u.id WHERE u.assigned_admin_id = ? OR u.id = ? ORDER BY o.created_at DESC");
    $orders->execute([$_SESSION[\'user_id\'], $_SESSION[\'user_id\']]);',
    $content
);

file_put_contents($file, $content);
echo "Fixed Orders export query in reports.php";
?>
