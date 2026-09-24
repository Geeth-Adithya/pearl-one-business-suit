<?php
$file = 'user/checkout.php';
$content = file_get_contents($file);

$search = <<<HTML
<div class="max-w-4xl mx-auto py-10">
HTML;

$replace = <<<HTML
<!-- Main Layout with Sidebar -->
<div class="flex h-[calc(100vh-4rem)] overflow-hidden">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto p-4 lg:p-10 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-4xl mx-auto">
HTML;

$content = str_replace($search, $replace, $content);

$search_end = <<<HTML
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
HTML;

$replace_end = <<<HTML
        </div>
    <?php endif; ?>
        </div> <!-- End max-w-4xl -->
    </div> <!-- End Content Area -->
</div> <!-- End Main Layout -->

<?php require_once '../includes/footer.php'; ?>
HTML;

$content = str_replace($search_end, $replace_end, $content);

file_put_contents($file, $content);
echo "Patched checkout.php\n";
?>

