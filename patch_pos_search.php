<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

$search = "const matchesSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || p.item_code.toLowerCase().includes(this.searchQuery.toLowerCase());";

$replace = "const matchesSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || p.item_code.toLowerCase().includes(this.searchQuery.toLowerCase()) || (p.search_keywords && p.search_keywords.toLowerCase().includes(this.searchQuery.toLowerCase()));";

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Patched POS search logic\n";
?>

