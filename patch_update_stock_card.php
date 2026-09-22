<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$searchStr = '<button type="button" onclick="document.getElementById(\'quickAddModal\').classList.remove(\'hidden\'); document.getElementById(\'quickAddModal\').classList.add(\'flex\');"';

$updateStockCard = '
        <!-- Update Stock Card -->
        <a href="<?= BASE_URL ?>/admin/products.php" class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group text-left flex flex-col justify-between cursor-pointer">
            <div class="flex items-center text-brand-blue dark:text-brand-lighter mb-4 transform group-hover:scale-110 transition-transform">
                <ion-icon name="sync-circle" class="text-3xl"></ion-icon>
            </div>
            <div>
                <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Inventory</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1 group-hover:text-brand-blue transition">Update Stock</p>
            </div>
        </a>
        
        ';

$content = str_replace($searchStr, $updateStockCard . $searchStr, $content);
file_put_contents($file, $content);
echo "Added Update Stock card!";
?>
