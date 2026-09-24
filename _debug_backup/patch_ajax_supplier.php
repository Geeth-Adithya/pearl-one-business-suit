<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

$ajaxHandler = '
// Handle AJAX Add Supplier
if (isset($_POST[\'ajax_add_supplier\'])) {
    header(\'Content-Type: application/json\');
    $name = trim($_POST[\'sup_name\'] ?? \'\');
    $phone = trim($_POST[\'sup_phone\'] ?? \'\');
    $email = trim($_POST[\'sup_email\'] ?? \'\');
    $admin_id = $_SESSION[\'role\'] === \'superadmin\' ? null : $_SESSION[\'user_id\'];
    
    if (!$name) {
        echo json_encode([\'success\' => false, \'error\' => \'Name is required\']);
        exit;
    }
    
    $check = $pdo->prepare("SELECT id FROM Suppliers WHERE name = ? AND (admin_id = ? OR admin_id IS NULL)");
    $check->execute([$name, $admin_id]);
    if ($check->fetch()) {
        echo json_encode([\'success\' => false, \'error\' => \'Supplier already exists\']);
        exit;
    }
    
    $stmt = $pdo->prepare(\'INSERT INTO Suppliers (name, admin_id, phone, email, address, product_types) VALUES (?, ?, ?, ?, ?, ?)\');
    if ($stmt->execute([$name, $admin_id, $phone, $email, \'\', \'\'])) {
        echo json_encode([\'success\' => true, \'id\' => $pdo->lastInsertId(), \'name\' => htmlspecialchars($name)]);
    } else {
        echo json_encode([\'success\' => false, \'error\' => \'Failed to add supplier\']);
    }
    exit;
}
';

if (strpos($content, 'ajax_add_supplier\']))') === false) {
    // Inject right after $success = ''; using regex
    $content = preg_replace('/(\$success\s*=\s*\'\';)/is', "$1\n" . $ajaxHandler, $content);
    file_put_contents($file, $content);
    echo "AJAX handler injected!\n";
} else {
    echo "Already injected.\n";
}
?>
