<?php
$file = 'user/checkout.php';
$content = file_get_contents($file);

// 1. Main wrapper
$content = str_replace(
    '<div class="flex h-[calc(100vh-4rem)] overflow-hidden">',
    '<div class="flex min-h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] lg:overflow-hidden relative max-w-full">',
    $content
);

// 2. Main content wrapper inside checkout
$content = preg_replace(
    '/<div class="flex-1 overflow-hidden p-2 lg:p-4"/',
    '<div class="flex-1 lg:overflow-hidden p-3 lg:p-4 w-full max-w-full"',
    $content
);

$content = preg_replace(
    '/<div class="flex flex-col lg:flex-row h-full gap-4"/',
    '<div class="flex flex-col lg:flex-row lg:h-full gap-4 max-w-full"',
    $content
);

$content = preg_replace(
    '/<div class="flex-1 flex flex-col min-w-0"/',
    '<div class="flex-1 flex flex-col min-w-0 max-w-full"',
    $content
);

$content = preg_replace(
    '/<div class="flex-1 overflow-y-auto pr-2 no-scrollbar pb-10"/',
    '<div class="flex-1 lg:overflow-y-auto lg:pr-2 no-scrollbar pb-10 max-w-full"',
    $content
);

file_put_contents($file, $content);
echo "Updated checkout.php layout for mobile.";
?>
