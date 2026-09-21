<?php
$file = "h:/Downloads/Backup 09- 10/Store_Purchase&Sell/admin/products.php";
$content = file_get_contents($file);

$startStr = "$categoryQuery .= \" ORDER BY name\";";
$endStr = "<div class=\"bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden\">";

$posStart = strpos($content, $startStr);
$posEnd = strpos($content, $endStr);

if ($posStart !== false && $posEnd !== false) {
    $before = substr($content, 0, $posStart + strlen($startStr));
    $after = substr($content, $posEnd);
    
    $replacement = "
\$categoriesStmt = \$pdo->prepare(\$categoryQuery);
\$categoriesStmt->execute(\$categoryParams);
\$categories = \$categoriesStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class=\"mb-8 flex justify-between items-center\">
    <h1 class=\"text-3xl font-bold text-gray-900 dark:text-white\">Product Management</h1>
    <div class=\"flex gap-2\">
        <a href=\"manage_supplier_links.php\" title=\"Share with Suppliers\"
            class=\"bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2\">
            <ion-icon name=\"share-social-outline\"></ion-icon> <span class=\"hidden sm:inline\">Share</span>
        </a>
        <a href=\"google_sync.php\" title=\"Google Sheets Sync\"
            class=\"bg-green-600 hover:bg-green-700 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2\">
            <ion-icon name=\"sync-outline\"></ion-icon> <span class=\"hidden sm:inline\">Sync</span>
        </a>
        <a href=\"product_import.php\" title=\"Bulk Import Products\"
            class=\"bg-gray-600 hover:bg-gray-700 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2\">
            <ion-icon name=\"cloud-upload-outline\"></ion-icon> <span class=\"hidden sm:inline\">Import</span>
        </a>
        <a href=\"product_add.php\" title=\"Add New Product\"
            class=\"bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm transition flex items-center gap-2\">
            <ion-icon name=\"add-circle-outline\"></ion-icon> <span class=\"hidden sm:inline\">Add Product</span>
        </a>
    </div>
</div>

<!-- Filter Form -->
<div class=\"bg-white dark:bg-gray-800 rounded-xl card-shadow mb-6\">
    <div class=\"p-6\">
        <h3 class=\"text-lg font-medium text-gray-900 dark:text-white\">Filter Products</h3>
        <form action=\"products.php\" method=\"GET\" class=\"mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 items-end\">
            <div>
                <label for=\"search\" class=\"block text-sm font-medium text-gray-700 dark:text-gray-300\">Search</label>
                <input type=\"text\" name=\"search\" id=\"search\" value=\"<?= htmlspecialchars(\$search_term ?? '') ?>\"
                    placeholder=\"Name or Item Code\"
                    class=\"mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2\">
            </div>
            <div>
                <label for=\"category\"
                    class=\"block text-sm font-medium text-gray-700 dark:text-gray-300\">Category</label>
                <select name=\"category\" id=\"category\"
                    class=\"mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2\">
                    <option value=\"\">All Categories</option>
                    <?php foreach (\$categories as \$cat): ?>
                        <option value=\"<?= \$cat['id'] ?>\" <?= (isset(\$category_filter) && \$category_filter == \$cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(\$cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class=\"flex items-center gap-2\">
                <button type=\"submit\" title=\"Filter\"
                    class=\"bg-brand-blue hover:bg-brand-blueDark text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm w-full md:w-auto flex items-center justify-center gap-2\">
                    <ion-icon name=\"filter-outline\"></ion-icon> <span class=\"hidden sm:inline\">Filter</span></button>
                <a href=\"products.php\"
                    class=\"text-center bg-gray-500 hover:bg-gray-600 text-white p-2 sm:px-4 sm:py-2 rounded-md shadow-sm w-full md:w-auto flex items-center justify-center gap-2\"
                    title=\"Reset\">
                    <ion-icon name=\"refresh-outline\"></ion-icon> <span class=\"hidden sm:inline\">Reset</span></a>
            </div>
        </form>
    </div>
</div>

";

    file_put_contents($file, $before . $replacement . $after);
    echo "Fixed successfully!";
} else {
    echo "Positions not found!";
}

