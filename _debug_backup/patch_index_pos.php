<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$search = <<<HTML
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow">
            <div class="flex items-center text-brand-blue dark:text-brand-lighter mb-4">
                <ion-icon name="cube" class="text-3xl"></ion-icon>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Products</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= \$totalProducts ?></p>
        </div>
HTML;

$replace = <<<HTML
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow">
            <div class="flex items-center text-brand-blue dark:text-brand-lighter mb-4">
                <ion-icon name="cube" class="text-3xl"></ion-icon>
            </div>
            <h3 class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wider">Total Products</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= \$totalProducts ?></p>
        </div>
        
        <?php if (\$_SESSION['module_pos']): ?>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="bg-gradient-to-r from-[#1E3A8A] to-[#1e3a8a] p-6 rounded-xl card-shadow hover:opacity-90 transition group flex flex-col justify-between">
            <div class="flex items-center text-white mb-4">
                <ion-icon name="calculator" class="text-3xl"></ion-icon>
            </div>
            <div>
                <h3 class="text-white text-sm font-medium uppercase tracking-wider">Open POS System</h3>
                <p class="text-xl font-bold text-white mt-1 group-hover:underline">Start New Sale &rarr;</p>
            </div>
        </a>
        <?php endif; ?>
HTML;

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Added POS quick action next to total products\n";
?>

