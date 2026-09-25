<?php
 = file_get_contents('api/portal.php');

 = <<<EOD
        if (\ === 'update_request') {
            \ = \['request_id'] ?? 0;
            \ = \['status'] ?? ''; // 'accepted' or 'rejected'
            \ = \['reply'] ?? '';
            
            if (!in_array(\, ['accepted', 'rejected'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            \ = \->prepare("UPDATE Supply_Requests SET status = ?, supplier_reply = ? WHERE id = ? AND supplier_id = ?");
            \->execute([\, \, \, \]);
            
            // Notify Admin
            \ = \ === 'accepted' ? 'Accepted' : 'Rejected';
            \ = "Supplier {\['name']} has \ your supply request. Reply: \";
EOD;

 = <<<EOD
        if (\ === 'update_request') {
            \ = \['request_id'] ?? 0;
            \ = \['status'] ?? ''; 
            \ = \['reply'] ?? '';
            
            if (!in_array(\, ['accepted', 'rejected', 'fulfilled'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            if (\ === 'fulfilled') {
                \ = \->prepare("UPDATE Supply_Requests SET status = ? WHERE id = ? AND supplier_id = ?");
                \->execute([\, \, \]);
                \ = 'Delivered';
                \ = "Supplier {\['name']} has marked your supply request as Delivered.";
            } else {
                \ = \->prepare("UPDATE Supply_Requests SET status = ?, supplier_reply = ? WHERE id = ? AND supplier_id = ?");
                \->execute([\, \, \, \]);
                \ = \ === 'accepted' ? 'Accepted' : 'Rejected';
                \ = "Supplier {\['name']} has \ your supply request. Reply: \";
            }
EOD;

 = str_replace(, , );

 = <<<EOD
    // 4. Fetch Explicit Supply Requests
    \ = \->prepare("
        SELECT sr.id, sr.quantity, sr.note, sr.created_at,
               p.name, p.item_code, p.image_url
        FROM Supply_Requests sr
        JOIN Products p ON sr.product_id = p.id
        WHERE sr.supplier_id = ? AND sr.status = 'pending'
        ORDER BY sr.created_at DESC
    ");
EOD;

 = <<<EOD
    // 4. Fetch Explicit Supply Requests
    \ = \->prepare("
        SELECT sr.id, sr.quantity, sr.note, sr.created_at, sr.status,
               p.name, p.item_code, p.image_url
        FROM Supply_Requests sr
        JOIN Products p ON sr.product_id = p.id
        WHERE sr.supplier_id = ? AND sr.status IN ('pending', 'accepted')
        ORDER BY sr.status DESC, sr.created_at DESC
    ");
EOD;

 = str_replace(, , );
file_put_contents('api/portal.php', );
echo 'Updated portal.php';
?>
