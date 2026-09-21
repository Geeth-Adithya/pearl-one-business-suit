<?php
$file = 'admin/products.php';
$content = file_get_contents($file);

// Add Quantity header
$pattern1 = '/(<th[^>]*>\s*Selling Price<\/th>)/is';
$replacement1 = "$1\n                    <th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider\">\n                        Quantity</th>";

$content = preg_replace($pattern1, $replacement1, $content);

// Add Quantity data cell
$pattern2 = '/(<td[^>]*>\s*\$<\?= number_format\(\$p\[\'selling_price\'\], 2\) \?>\s*<\/td>)/is';
$replacement2 = "$1\n                        <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white\">\n                            <?= htmlspecialchars(\$p['stock_quantity'] ?? 0) ?> <?= htmlspecialchars(\$p['unit'] ?? '') ?>\n                        </td>";

$content = preg_replace($pattern2, $replacement2, $content);

file_put_contents($file, $content);
echo "Injected Quantity column into products.php\n";
?>

