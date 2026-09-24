<?php
$file = 'includes/header.php';
$content = file_get_contents($file);

$search = <<<HTML
    ?> <!-- Main Content Wrapper -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
HTML;

$replace = <<<HTML
    ?> 
    
    <!-- Main Content Wrapper -->
    <?php if (isset(\$full_width_layout) && \$full_width_layout): ?>
    <main class="flex-grow w-full flex flex-col">
    <?php else: ?>
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php endif; ?>
HTML;

$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
echo "Patched header.php\n";
?>

