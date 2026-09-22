<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// Fix the card design
$oldCard = '<button type="button" onclick="document.getElementById(\'quickAddModal\').classList.remove(\'hidden\'); document.getElementById(\'quickAddModal\').classList.add(\'flex\');" class="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-xl card-shadow hover:opacity-90 transition group flex flex-col justify-between text-left">
            <div class="flex items-center text-white mb-4">
                <ion-icon name="add-circle" class="text-3xl"></ion-icon>
            </div>
            <div>
                <h3 class="text-white text-sm font-medium uppercase tracking-wider">Quick Add</h3>
                <p class="text-xl font-bold text-white mt-1 group-hover:underline">Add New Item &rarr;</p>
            </div>
        </button>';

$newCard = '<button type="button" onclick="document.getElementById(\'quickAddModal\').classList.remove(\'hidden\'); document.getElementById(\'quickAddModal\').classList.add(\'flex\');" class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group text-left flex flex-col justify-between cursor-pointer">
            <div class="flex items-center text-brand-blue dark:text-brand-lighter mb-4 transform group-hover:scale-110 transition-transform">
                <ion-icon name="add-circle" class="text-3xl"></ion-icon>
            </div>
            <div>
                <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Quick Add</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 group-hover:text-brand-blue transition">Add New Item</p>
            </div>
        </button>';

$content = str_replace($oldCard, $newCard, $content);

// Ensure the modal actually exists
if (strpos($content, 'id="quickAddModal"') === false) {
    $modalHtml = '
<!-- Quick Add Product Wizard Modal -->
<div id="quickAddModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-[100]">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow w-full max-w-md transform transition-all shadow-2xl">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <ion-icon name="flash" class="text-brand-blue"></ion-icon> Quick Add Wizard
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
                <input type="text" name="quick_category_name" id="quick_category_name" placeholder="" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
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
    $content = str_replace("<?php require_once '../includes/footer.php'; ?>", $modalHtml . "\n<?php require_once '../includes/footer.php'; ?>", $content);
    echo "Modal injected successfully!\n";
} else {
    echo "Modal already exists.\n";
}

file_put_contents($file, $content);
echo "File updated successfully!";
?>
