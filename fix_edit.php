<?php
$content = file_get_contents('admin/product_edit.php');

$insert = <<<PHP
    if (empty(\)) {
        try {
            \->beginTransaction();

            \ = "UPDATE Products SET 
                        item_code = ?, name = ?, search_keywords = ?, attribute = ?, type = ?, purchasing_price = ?,
                        margin_percent = ?, selling_price = ?, unit = ?, 
                        description = ?, supplier_name = ?, image_url = ?, video_url = ?, stock_quantity = ?, low_stock_threshold = ?
                    WHERE id = ?";

            \ = \->prepare(\);
            \->execute([
                \['item_code'],
                \['name'],
                \['search_keywords'] ?? '',
                \['attribute'],
                \['type'],
                \['purchasing_price'],
                \['margin_percent'],
                \['selling_price'],
                \['unit'],
                \['description'],
                \['supplier_name'],
                \,
                \,
                \['stock_quantity'] ?? 0,
                \['low_stock_threshold'] ?? 10,
                \
            ]);

            \ = \->prepare("DELETE FROM Product_Categories WHERE product_id = ?");
            \->execute([\]);

            if (!empty(\)) {
                \ = \->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");
                foreach (\ as \) {
                    \->execute([\, \]);
                }
            }

            if (\) {
                \ = \->prepare("INSERT INTO Products (item_code, name, search_keywords, attribute, type, purchasing_price, margin_percent, selling_price, unit, description, supplier_name, stock_quantity, low_stock_threshold, image_url, video_url, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                \->execute([
                    \['item_code'], \['name'], \['search_keywords'] ?? '', \['attribute'], \['type'],
                    \['purchasing_price'], \['margin_percent'], \['selling_price'],
                    \['unit'], \['description'], \['supplier_name'], \['stock_quantity'] ?? 0, \['low_stock_threshold'] ?? 10, \, \,
                    \['user_id']
                ]);
                \ = \->lastInsertId();
                if (!empty(\)) {
                    foreach (\ as \) {
                        \->execute([\, \]);
                    }
                }
            }
PHP;

$pattern = '/if \(\\[.item_code.\] === .. \|\|(.*?)\}\s*\}\s*\}\s*\->commit\(\);/is';
$replacement = "if (\['item_code'] === '' || \['attribute'] === '' || \['purchasing_price'] === '' || \['selling_price'] === '') {
                \ = 'Please complete the new variant Item Code, Attribute, Purchasing Price, and Selling Price.';
            }
        }
    }
" . $insert . "

            \->commit();";

$content = preg_replace($pattern, $replacement, $content);
file_put_contents('admin/product_edit.php', $content);
echo "Restored and updated block!";
?>
