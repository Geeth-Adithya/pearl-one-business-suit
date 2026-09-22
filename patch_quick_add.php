<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// 1. Add POST handler for Quick Category Add at the top
$handler = "
// Quick Category Add Handler
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['quick_category_name'])) {
    \$catName = trim(\$_POST['quick_category_name']);
    if (!empty(\$catName)) {
        // Check if exists
        \$check = \$pdo->prepare(\"SELECT id FROM Categories WHERE name = ? AND (admin_id = ? OR admin_id IS NULL)\");
        \$check->execute([\$catName, \$_SESSION['role'] === 'superadmin' ? null : \$_SESSION['user_id']]);
        if (!\$check->fetch()) {
            \$stmt = \$pdo->prepare(\"INSERT INTO Categories (name, admin_id) VALUES (?, ?)\");
            \$stmt->execute([\$catName, \$_SESSION['role'] === 'superadmin' ? null : \$_SESSION['user_id']]);
            \$_SESSION['success_message'] = \"Category '\$catName' created! You can now add your product.\";
        }
    }
    header(\"Location: product_add.php\");
    exit;
}
";

if (strpos($content, 'Quick Category Add Handler') === false) {
    $content = str_replace("requireAdmin();\n", "requireAdmin();\n" . $handler, $content);
}

// 2. Fetch categories for the modal
$catFetcher = "
    // Fetch categories for Quick Add Modal
    \$catStmt = \$pdo->prepare(\"SELECT * FROM Categories WHERE admin_id = ? OR admin_id IS NULL ORDER BY name\");
    \$catStmt->execute([\$_SESSION['user_id']]);
    \$all_categories = \$catStmt->fetchAll();
";
if (strpos($content, 'Fetch categories for Quick Add Modal') === false) {
    $content = str_replace("\$assigned_users = \$assigned_users_stmt->fetchAll();\n", "\$assigned_users = \$assigned_users_stmt->fetchAll();\n" . $catFetcher, $content);
}

// 3. Add Quick Add button to Total Products card
$cardOriginal = '<h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Products</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= $totalProducts ?></p>';
$cardNew = '<div class="flex justify-between items-center">
                <div>
                    <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Products</h3>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= $totalProducts ?></p>
                </div>
                <button type="button" onclick="document.getElementById(\'quickAddModal\').classList.remove(\'hidden\'); document.getElementById(\'quickAddModal\').classList.add(\'flex\');" class="bg-brand-blue hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition flex items-center justify-center transform hover:scale-110" title="Quick Add Product">
                    <ion-icon name="add" class="text-2xl"></ion-icon>
                </button>
            </div>';
if (strpos($content, 'quickAddModal') === false) {
    $content = str_replace($cardOriginal, $cardNew, $content);
}

// 4. Add the Modal HTML and JS at the bottom
$modalHtml = '
<!-- Quick Add Product Wizard Modal -->
<div id="quickAddModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-[60]">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow w-full max-w-md transform transition-all">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <ion-icon name="flash" class="text-yellow-500"></ion-icon> Quick Add Wizard
            </h3>
            <button type="button" onclick="document.getElementById(\'quickAddModal\').classList.add(\'hidden\'); document.getElementById(\'quickAddModal\').classList.remove(\'flex\');" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-5">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">To add a new product, you need a category. You can select an existing one or quickly create a new one!</p>
                
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Existing Category (Optional)</label>
                <select id="quick_cat_select" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white mb-4">
                    <option value="">-- Choose Category --</option>
                    <?php if(!empty($all_categories)): foreach($all_categories as $c): ?>
                        <option value="<?= htmlspecialchars($c[\'name\']) ?>"><?= htmlspecialchars($c[\'name\']) ?></option>
                    <?php endforeach; endif; ?>
                </select>

                <div class="relative flex items-center py-2">
                    <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                    <span class="flex-shrink-0 mx-4 text-gray-400 text-sm">OR CREATE NEW</span>
                    <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                </div>

                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mt-4 mb-1">New Category Name</label>
                <input type="text" name="quick_category_name" id="quick_category_name" placeholder="" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <a href="product_add.php" class="px-4 py-2 bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 transition">Skip to Product Add</a>
                <button type="submit" class="px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-blue-700 shadow transition flex items-center gap-2">
                    Next Step <ion-icon name="arrow-forward-outline"></ion-icon>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById("quick_cat_select")?.addEventListener("change", function() {
        if(this.value) {
            // If they select an existing category, they can just skip to product add
            // Because product_add.php will have it available
            document.getElementById("quick_category_name").value = "";
        }
    });
    document.getElementById("quick_category_name")?.addEventListener("input", function() {
        if(this.value) {
            document.getElementById("quick_cat_select").value = "";
        }
    });
</script>
';
if (strpos($content, 'quickAddModal') === false) {
    $content = str_replace("<?php require_once '../includes/footer.php'; ?>", $modalHtml . "\n<?php require_once '../includes/footer.php'; ?>", $content);
}

file_put_contents($file, $content);
echo "Dashboard updated!";
?>
