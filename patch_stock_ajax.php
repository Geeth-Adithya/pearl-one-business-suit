<?php
$file = 'admin/products.php';
$content = file_get_contents($file);

$ajaxHandler = '
// Handle AJAX Stock Update
if (isset($_POST[\'ajax_stock_update\'])) {
    header(\'Content-Type: application/json\');
    $id = (int)$_POST[\'product_id\'];
    $change = (float)$_POST[\'qty_change\'];
    $type = $_POST[\'update_type\']; // \'add\' or \'reduce\'
    
    if ($type === \'reduce\') {
        $change = -$change;
    }
    
    $where = "id = ?";
    $params = [$change, $id];
    if (isset($_SESSION[\'role\']) && $_SESSION[\'role\'] !== \'superadmin\') {
        $where .= " AND created_by_admin_id = ?";
        $params[] = $_SESSION[\'user_id\'];
    }
    
    $stmt = $pdo->prepare("UPDATE Products SET stock_quantity = stock_quantity + ? WHERE $where");
    if ($stmt->execute($params)) {
        $newQtyStmt = $pdo->prepare("SELECT stock_quantity FROM Products WHERE id = ?");
        $newQtyStmt->execute([$id]);
        echo json_encode([\'success\' => true, \'new_qty\' => (float)$newQtyStmt->fetchColumn()]);
    } else {
        echo json_encode([\'success\' => false, \'error\' => \'Failed to update stock\']);
    }
    exit;
}
';

if (strpos($content, 'isset($_POST[\'ajax_stock_update\'])') === false) {
    // Inject right after require_once '../includes/auth.php';
    $content = preg_replace('/(require_once\s*\'\.\.\/includes\/auth\.php\';)/i', "$1\n" . $ajaxHandler, $content, 1);
    file_put_contents($file, $content);
    echo "Injected AJAX handler successfully!";
} else {
    echo "AJAX handler already exists.";
}
?>
