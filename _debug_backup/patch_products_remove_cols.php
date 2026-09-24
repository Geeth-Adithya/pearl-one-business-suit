<?php
$file = 'admin/products.php';
$content = file_get_contents($file);

// Remove Attribute header
$pattern1 = '/<th\s*class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase\s*tracking-wider">\s*Attribute<\/th>/is';
$content = preg_replace($pattern1, '', $content);

// Remove Type header
$pattern2 = '/<th\s*class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase\s*tracking-wider">\s*Type<\/th>/is';
$content = preg_replace($pattern2, '', $content);

// Remove Attribute data cell
$pattern3 = '/<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">\s*<\?= htmlspecialchars\(\$p\[\'attribute\'\] \?: \'N\/A\'\) \?>\s*<\/td>/is';
$content = preg_replace($pattern3, '', $content);

// Remove Type data cell
$pattern4 = '/<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">\s*<\?= htmlspecialchars\(\$p\[\'type\'\] \?: \'N\/A\'\) \?>\s*<\/td>/is';
$content = preg_replace($pattern4, '', $content);

file_put_contents($file, $content);
echo "Removed Attribute and Type columns from products.php\n";
?>

