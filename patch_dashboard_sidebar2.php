<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

$pattern = '/<!-- Wrapper for full height POS -->\s*<div class="flex flex-col lg:flex-row h-\[calc\(100vh-6rem\)\] gap-6" x-data="posSystem\(\)">/';

$replace = <<<HTML
<!-- Main Layout with Sidebar -->
<div class="flex h-[calc(100vh-4rem)] overflow-hidden">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- POS Content Area -->
    <div class="flex-1 overflow-hidden p-4 lg:p-6" x-data="posSystem()">
        <div class="flex flex-col lg:flex-row h-full gap-6">
HTML;

$content = preg_replace($pattern, $replace, $content);

$pattern_end = '/\s*}\)\);\s*}\);\s*<\/script>\s*<\?php require_once \'\.\.\/includes\/footer\.php\'; \?>/s';

$replace_end = <<<HTML
    }));
});
</script>

    </div> <!-- End POS Content Area -->
</div> <!-- End Main Layout -->

<?php require_once '../includes/footer.php'; ?>
HTML;

$content = preg_replace($pattern_end, $replace_end, $content);

file_put_contents($file, $content);
echo "Regex patched user/dashboard.php\n";
?>

