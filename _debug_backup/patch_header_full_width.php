<?php
$file = 'includes/header.php';
$content = file_get_contents($file);

$search = '<main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">';
$replace = '<main class="flex-grow w-full <?= isset($full_width_layout) && $full_width_layout ? \'px-4 sm:px-6 py-6\' : \'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8\' ?>">';
$content = str_replace($search, $replace, $content);

// Also for nav, let's make it full width if full_width_layout is set
$search_nav = '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';
$replace_nav = '<div class="<?= isset($full_width_layout) && $full_width_layout ? \'w-full px-4 sm:px-6\' : \'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8\' ?>">';
$content = str_replace($search_nav, $replace_nav, $content);

file_put_contents($file, $content);
echo "Restored full_width_layout support in header.php\n";
?>

