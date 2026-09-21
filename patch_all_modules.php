<?php
function patchFile($filePath, $moduleName) {
    if (!file_exists($filePath)) {
        echo "File not found: \$filePath\n";
        return;
    }
    
    $content = file_get_contents($filePath);
    
    // Some files have requireAdmin() or requireLogin() already
    // We want to add requireModule('\$moduleName'); after includes
    
    $searchString = "require_once '../includes/auth.php';";
    if (strpos($content, $searchString) !== false) {
        $replaceString = "require_once '../includes/auth.php';\nrequireModule('\$moduleName');";
        
        // Ensure we don't add it multiple times
        if (strpos($content, "requireModule('\$moduleName')") === false) {
            $content = str_replace($searchString, $replaceString, $content);
            file_put_contents($filePath, $content);
            echo "Patched \$filePath with \$moduleName\n";
        } else {
            echo "Already patched: \$filePath\n";
        }
    } else {
        echo "Could not find auth.php include in \$filePath\n";
    }
}

// Module POS
patchFile('admin/orders.php', 'module_pos');
patchFile('user/dashboard.php', 'module_pos');
patchFile('user/checkout.php', 'module_pos');

// Module Stock
patchFile('admin/products.php', 'module_stock');
patchFile('admin/product_add.php', 'module_stock');
patchFile('admin/product_edit.php', 'module_stock');
patchFile('admin/manage_categories.php', 'module_stock');
patchFile('admin/google_sync.php', 'module_stock');

// Module Supply
patchFile('admin/manage_suppliers.php', 'module_supply');

?>

