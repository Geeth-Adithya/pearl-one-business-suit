<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$error = '';
$success = '';
$imported_count = 0;

if (isset($_GET['action']) && $_GET['action'] === 'template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=product_import_template.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Category', 'Item Code (Optional)', 'Product Name', 'Search Keywords', 'Type', 'Purchasing Price', 'Selling Price', 'Stock Quantity', 'Unit', 'Description']);
    fputcsv($output, ['Electronics', 'LAP-001', 'Sample Laptop', 'laptop, pc', 'New', '150000', '160000', '10', 'pcs', 'A sample description']);
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "CSRF Token validation failed.";
    } else {
        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'csv') {
            $error = "Please upload a valid CSV file.";
        } else {
            $handle = fopen($file['tmp_name'], "r");
            if ($handle !== FALSE) {
                // Read header row
                $header = fgetcsv($handle, 1000, ",");
                
                $pdo->beginTransaction();
                try {
                    $stmtCatCheck = $pdo->prepare("SELECT id FROM Categories WHERE name = ? AND (admin_id = ? OR admin_id IS NULL) LIMIT 1");
                    $stmtCatInsert = $pdo->prepare("INSERT INTO Categories (name, admin_id) VALUES (?, ?)");
                    
                    $stmtProdInsert = $pdo->prepare("INSERT INTO Products (item_code, name, search_keywords, type, purchasing_price, selling_price, stock_quantity, unit, description, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtProdCatInsert = $pdo->prepare("INSERT INTO Product_Categories (product_id, category_id) VALUES (?, ?)");

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (empty($data[0]) && empty($data[2])) continue; // Skip empty rows

                        $category_name = trim($data[0]);
                        $item_code = trim($data[1]);
                        $product_name = trim($data[2]);
                        $keywords = trim($data[3] ?? '');
                        $type = trim($data[4] ?? '');
                        $purchasing_price = floatval($data[5] ?? 0);
                        $selling_price = floatval($data[6] ?? 0);
                        $stock_qty = intval($data[7] ?? 0);
                        $unit = trim($data[8] ?? '');
                        $description = trim($data[9] ?? '');

                        // Handle Category
                        $category_id = null;
                        if (!empty($category_name)) {
                            $stmtCatCheck->execute([$category_name, $_SESSION['user_id']]);
                            $category_id = $stmtCatCheck->fetchColumn();
                            
                            if (!$category_id) {
                                $stmtCatInsert->execute([$category_name, $_SESSION['user_id']]);
                                $category_id = $pdo->lastInsertId();
                            }
                        }

                        // Auto-generate item code if missing
                        if (empty($item_code)) {
                            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $category_name), 0, 3));
                            if (empty($prefix)) $prefix = 'ITM';
                            $item_code = $prefix . '-' . strtoupper(uniqid());
                        }

                        $stmtProdInsert->execute([
                            $item_code, $product_name, $keywords, $type, 
                            $purchasing_price, $selling_price, $stock_qty, 
                            $unit, $description, $_SESSION['user_id']
                        ]);
                        $product_id = $pdo->lastInsertId();

                        if ($category_id) {
                            $stmtProdCatInsert->execute([$product_id, $category_id]);
                        }
                        
                        $imported_count++;
                    }
                    $pdo->commit();
                    $success = "$imported_count products successfully imported!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error importing data: " . $e->getMessage();
                }
                fclose($handle);
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="mb-8 flex items-center gap-4">
    <a href="products.php" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Back to Products">
        <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
    </a>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Bulk Import Products</h1>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <strong>Error!</strong> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        <strong>Success!</strong> <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. Download Template</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">Download our standard CSV template file. Fill in your product details row by row. Do not change the column headers in the first row.</p>
        <a href="product_import.php?action=template" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg shadow-sm transition flex items-center gap-3 w-max">
            <ion-icon name="download-outline" class="text-xl"></ion-icon> Download CSV Template
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. Upload Filled File</h2>
        <form action="product_import.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center bg-gray-50 dark:bg-gray-700/50">
                <ion-icon name="cloud-upload-outline" class="text-4xl text-brand-blue mb-2"></ion-icon>
                <div class="mb-4">
                    <label for="import_file" class="cursor-pointer text-brand-blue hover:text-brand-blueDark font-medium">Click to select a file</label>
                    <input type="file" name="import_file" id="import_file" accept=".csv" class="hidden" required onchange="document.getElementById('file-name').textContent = this.files[0].name">
                </div>
                <p id="file-name" class="text-sm text-gray-500 dark:text-gray-400">No file chosen (CSV only)</p>
            </div>

            <button type="submit" class="w-full bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-3 rounded-lg shadow-sm transition flex items-center justify-center gap-2 font-medium">
                <ion-icon name="checkmark-circle-outline" class="text-xl"></ion-icon> Start Import
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
