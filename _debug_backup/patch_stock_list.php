<?php
$file = 'admin/products.php';
$content = file_get_contents($file);

// 1. Add AJAX Handler at the top
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
    
    // Check ownership (or superadmin)
    $where = "id = ?";
    $params = [$change, $id];
    if ($_SESSION[\'role\'] !== \'superadmin\') {
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
if (strpos($content, 'ajax_stock_update') === false) {
    $content = str_replace("<?php\nrequire_once '../includes/db.php';\nrequire_once '../includes/auth.php';\nrequireAdmin();\n", "<?php\nrequire_once '../includes/db.php';\nrequire_once '../includes/auth.php';\nrequireAdmin();\n" . $ajaxHandler, $content);
}

// 2. Modify the Stock TD
$oldTd = '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($p[\'stock_quantity\'] ?? 0) ?> <?= htmlspecialchars($p[\'unit\'] ?? \'\') ?>
                        </td>';

$newTd = '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="adjustStock(<?= $p[\'id\'] ?>, \'reduce\', \'<?= htmlspecialchars(addslashes($p[\'name\'])) ?>\')" class="bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 w-7 h-7 rounded flex items-center justify-center transition border border-red-200 dark:border-red-800/30" title="Reduce Stock">
                                    <ion-icon name="remove-outline" class="text-lg"></ion-icon>
                                </button>
                                
                                <span id="stock-val-<?= $p[\'id\'] ?>" class="font-bold text-base min-w-[2rem] text-center"><?= htmlspecialchars($p[\'stock_quantity\'] ?? 0) ?></span>
                                <span class="text-gray-500 text-xs"><?= htmlspecialchars($p[\'unit\'] ?? \'\') ?></span>
                                
                                <button type="button" onclick="adjustStock(<?= $p[\'id\'] ?>, \'add\', \'<?= htmlspecialchars(addslashes($p[\'name\'])) ?>\')" class="bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50 w-7 h-7 rounded flex items-center justify-center transition border border-green-200 dark:border-green-800/30" title="Add Stock">
                                    <ion-icon name="add-outline" class="text-lg"></ion-icon>
                                </button>
                            </div>
                        </td>';
$content = str_replace($oldTd, $newTd, $content);

// 3. Add SweetAlert JS Function
$js = '
<script>
function adjustStock(productId, type, productName) {
    const isAdd = type === \'add\';
    const title = isAdd ? \'Add Stock\' : \'Reduce Stock\';
    const text = isAdd ? `How many items of <b>${productName}</b> arrived?` : `How many items of <b>${productName}</b> did you sell/use?`;
    const confirmColor = isAdd ? \'#10b981\' : \'#ef4444\';
    const confirmText = isAdd ? \'Add Stock\' : \'Reduce Stock\';

    Swal.fire({
        title: title,
        html: text,
        input: \'number\',
        inputValue: 1,
        inputAttributes: {
            min: 1,
            step: 0.01
        },
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        confirmButtonText: confirmText,
        inputValidator: (value) => {
            if (!value || value <= 0) {
                return \'Please enter a valid quantity!\';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const qty = result.value;
            const formData = new FormData();
            formData.append(\'ajax_stock_update\', \'1\');
            formData.append(\'product_id\', productId);
            formData.append(\'qty_change\', qty);
            formData.append(\'update_type\', type);

            fetch(\'products.php\', {
                method: \'POST\',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById(\'stock-val-\' + productId).textContent = data.new_qty;
                    Swal.fire({
                        icon: \'success\',
                        title: \'Updated!\',
                        text: `Stock updated successfully. New quantity: ${data.new_qty}`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire(\'Error\', data.error || \'Failed to update stock\', \'error\');
                }
            })
            .catch(() => {
                Swal.fire(\'Error\', \'Network error. Please try again.\', \'error\');
            });
        }
    });
}
</script>
';
if (strpos($content, 'function adjustStock') === false) {
    $content = str_replace("</body>", $js . "\n</body>", $content);
}

file_put_contents($file, $content);
echo "Products list patched!";
?>
