<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$search = <<<HTML
            <!-- Google Sheets Sync -->
            <a href="<?= BASE_URL ?>/admin/google_sync.php"
HTML;

$replace = <<<HTML
            <!-- Google Sheets Sync -->
            <?php if (\$_SESSION['module_stock']): ?>
            <a href="<?= BASE_URL ?>/admin/google_sync.php"
HTML;

$content = str_replace($search, $replace, $content);

$search2 = <<<HTML
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Google Sheets</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sync product data with Sheets.</p>
            </a>
HTML;

$replace2 = <<<HTML
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Google Sheets</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sync product data with Sheets.</p>
            </a>
            <?php endif; ?>
HTML;

$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Patched admin/index.php for Google Sheets\n";
?>

