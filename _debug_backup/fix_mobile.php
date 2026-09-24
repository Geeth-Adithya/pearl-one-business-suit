<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

// 1. Main wrapper
$content = str_replace(
    '<div class="flex h-[calc(100vh-4rem)] overflow-hidden">',
    '<div class="flex min-h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] lg:overflow-hidden relative max-w-full">',
    $content
);

// 2. POS Content Area
$content = str_replace(
    '<div class="flex-1 overflow-hidden p-2 lg:p-4"',
    '<div class="flex-1 lg:overflow-hidden p-3 lg:p-4 w-full max-w-full"',
    $content
);

// 3. Flex row/col wrapper
$content = str_replace(
    '<div class="flex flex-col lg:flex-row h-full gap-4">',
    '<div class="flex flex-col lg:flex-row lg:h-full gap-4 max-w-full">',
    $content
);

// 4. Header: Search & Filter
$content = str_replace(
    '<div class="flex justify-between items-center mb-6">',
    '<div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0 mb-6 max-w-full">',
    $content
);

// Also make search bar take full width on mobile
$content = str_replace(
    '<div class="relative w-full max-w-xs">',
    '<div class="relative w-full sm:max-w-xs">',
    $content
);

// 5. Products Grid wrapper (remove mobile overflow to allow native body scroll)
$content = str_replace(
    '<div class="flex-1 overflow-y-auto pr-2 no-scrollbar pb-10">',
    '<div class="flex-1 lg:overflow-y-auto lg:pr-2 no-scrollbar pb-10 max-w-full">',
    $content
);

// 6. Right Side (Cart)
$content = str_replace(
    'shadow-sm flex flex-col h-full shrink-0">',
    'shadow-sm flex flex-col lg:h-full shrink-0 mt-4 lg:mt-0 max-w-full">',
    $content
);

// 7. Grid columns
$content = str_replace(
    '<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">',
    '<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-4">',
    $content
);

// 8. Product Card sizing for mobile (slightly smaller padding)
$content = str_replace(
    '<div class="bg-white dark:bg-gray-800 rounded-3xl p-5 flex flex-col items-center justify-between',
    '<div class="bg-white dark:bg-gray-800 rounded-[1.5rem] p-3 lg:p-5 flex flex-col items-center justify-between',
    $content
);
$content = str_replace(
    '<div class="w-32 h-32 rounded-2xl bg-gray-50 dark:bg-gray-700 mb-4 overflow-hidden flex',
    '<div class="w-24 h-24 lg:w-32 lg:h-32 rounded-2xl bg-gray-50 dark:bg-gray-700 mb-3 lg:mb-4 overflow-hidden flex',
    $content
);
$content = str_replace(
    '<h3 class="font-bold text-gray-900 dark:text-white text-center text-[15px] leading-tight mb-2"',
    '<h3 class="font-bold text-gray-900 dark:text-white text-center text-sm lg:text-[15px] leading-tight mb-2"',
    $content
);

file_put_contents($file, $content);
echo "Updated dashboard.php layout for mobile.";
?>
