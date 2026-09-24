<?php
function improveTableReadability($file) {
    if (!file_exists($file)) return;
    $content = file_get_contents($file);
    
    // Replace text-gray-500 dark:text-gray-400 with dark:text-gray-200 for better general readability in tables
    $content = str_replace('dark:text-gray-400', 'dark:text-gray-200', $content);
    $content = str_replace('dark:text-gray-500', 'dark:text-gray-300', $content);
    
    // Specifically make "Rs." columns stand out more with brand-lighter
    // If a td contains Rs., let's just make it brand-lighter
    $content = preg_replace('/(<td[^>]*class="[^"]*)text-gray-500 dark:text-gray-200([^"]*"[^>]*>\s*Rs\.)/s', '$1text-brand-blue dark:text-brand-lighter font-semibold$2', $content);
    
    file_put_contents($file, $content);
}

improveTableReadability(__DIR__ . '/user/sales_manage.php');
improveTableReadability(__DIR__ . '/user/invoice_history.php');
improveTableReadability(__DIR__ . '/admin/suppliers.php');

echo "Improved table readability.\n";
?>

