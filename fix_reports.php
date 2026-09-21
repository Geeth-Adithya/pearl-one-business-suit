<?php
$file = 'admin/reports.php';
$content = file_get_contents($file);

$content = preg_replace(
    '/\$products = \$pdo->query\("SELECT (.*?) FROM Products ORDER BY id DESC"\);/',
    '$products = $pdo->prepare("SELECT $1 FROM Products WHERE created_by_admin_id = ? ORDER BY id DESC");
    $products->execute([$_SESSION[\'user_id\']]);',
    $content
);

$content = preg_replace(
    '/\$orders = \$pdo->query\("SELECT (.*?) FROM Orders ORDER BY created_at DESC"\);/',
    '$orders = $pdo->prepare("SELECT $1 FROM Orders WHERE shop_id = ? ORDER BY created_at DESC");
    $orders->execute([$_SESSION[\'user_id\']]);',
    $content
);

file_put_contents($file, $content);
echo "Fixed security bug in reports.php";
?>
