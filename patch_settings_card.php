<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$insert_html = <<<HTML
            <!-- Shop Settings -->
            <a href="<?= BASE_URL ?>/admin/shop_settings.php"
                class="bg-white dark:bg-gray-800 p-6 rounded-xl card-shadow hover:bg-gray-50 dark:hover:bg-gray-700 transition group">
                <div class="flex items-center text-pink-500 mb-3">
                    <ion-icon name="settings-outline" class="text-4xl"></ion-icon>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Shop Settings</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update shop name, contact, and address.</p>
            </a>
HTML;

$search = "            <!-- Manage Categories -->";
$replace = $insert_html . "\n\n" . $search;
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Added Shop Settings card to admin/index.php\n";
?>

