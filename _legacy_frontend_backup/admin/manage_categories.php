<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireModule('module_stock');

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}

$isSuperAdmin = $_SESSION['role'] === 'superadmin';
$error = '';
$success = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $name = trim($_POST['name']);
    if (empty($name)) {
        $_SESSION['error_message'] = "Category name cannot be empty.";
    } else {
        if ($isSuperAdmin) {
            $stmt = $pdo->prepare("SELECT id FROM Categories WHERE name = ?");
            $stmt->execute([$name]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM Categories WHERE name = ? AND (admin_id = ? OR admin_id IS NULL)");
            $stmt->execute([$name, $_SESSION['user_id']]);
        }

        if ($stmt->fetch()) {
            $_SESSION['error_message'] = "Category already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Categories (name, admin_id) VALUES (?, ?)");
            if ($stmt->execute([$name, $isSuperAdmin ? null : $_SESSION['user_id']])) {
                $_SESSION['success_message'] = "Category added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding category.";
            }
        }
    }
    header('Location: manage_categories.php');
    exit;
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { die('CSRF Token validation failed.'); }

    $id = $_POST['id'];
    $name = trim($_POST['name']);
    if (empty($name)) {
        $_SESSION['error_message'] = "Category name cannot be empty.";
    } else {
        if ($isSuperAdmin) {
            $query = "SELECT id FROM Categories WHERE name = ? AND id != ?";
            $params = [$name, $id];
        } else {
            $query = "SELECT id FROM Categories WHERE name = ? AND id != ? AND (admin_id = ? OR admin_id IS NULL)";
            $params = [$name, $id, $_SESSION['user_id']];
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = "Another category with this name already exists.";
        } else {
            $updateSql = "UPDATE Categories SET name = ? WHERE id = ?";
            $updateParams = [$name, $id];
            if (!$isSuperAdmin) {
                $updateSql .= " AND admin_id = ?"; // Cannot edit global categories
                $updateParams[] = $_SESSION['user_id'];
            }
            $stmt = $pdo->prepare($updateSql);
            if ($stmt->execute($updateParams)) {
                $_SESSION['success_message'] = "Category updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating category.";
            }
        }
    }
    header('Location: manage_categories.php');
    exit;
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Ensure user has permission to delete this category
        $canDelete = true;
        if (!$isSuperAdmin) {
            $check = $pdo->prepare("SELECT id FROM Categories WHERE id = ? AND admin_id = ?");
            $check->execute([$id, $_SESSION['user_id']]);
            if (!$check->fetch()) {
                $canDelete = false;
            }
        }
        
        if ($canDelete) {
            // First delete all products in this category
            $stmtProds = $pdo->prepare("DELETE FROM Products WHERE id IN (SELECT product_id FROM Product_Categories WHERE category_id = ?)");
            $stmtProds->execute([$id]);
            
            // Then delete the category
            $stmtCat = $pdo->prepare("DELETE FROM Categories WHERE id = ?");
            $stmtCat->execute([$id]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Category and all its items were successfully deleted.";
        } else {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Unauthorized to delete this category.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error deleting category: " . $e->getMessage();
    }
    
    header('Location: manage_categories.php');
    exit;
}$categoryQuery = "SELECT * FROM Categories";
$categoryParams = [];
if (!$isSuperAdmin) {
    $categoryQuery .= " WHERE admin_id = ? OR admin_id IS NULL";
    $categoryParams[] = $_SESSION['user_id'];
}
$categoryQuery .= " ORDER BY name";
$categories = $pdo->prepare($categoryQuery);
$categories->execute($categoryParams);
$categories = $categories->fetchAll();

require_once '../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 sm:gap-0">
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Go Back">
            <ion-icon name="arrow-back-outline" class="text-xl"></ion-icon>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Categories</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Add, edit, or delete product categories.</p>
        </div>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add New Category</h3>
            </div>
            <form action="manage_categories.php" method="POST" class="p-6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
<label for="name"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category Name</label><input
                    type="text" name="name" id="name" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"><button
                    type="submit" name="add_category"
                    class="mt-4 w-full bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition">Add
                    Category</button></form>
        </div>
    </div>
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Existing Categories</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Name</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($categories as $category): ?>
                                                        <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($category['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="openEditCategoryModal(<?= $category['id'] ?>, '<?= addslashes(htmlspecialchars($category['name'])) ?>')"
                                        class="text-brand-blue hover:text-brand-blueDark mr-3">Edit</button>
                                    <a href="manage_categories.php?delete=<?= $category['id'] ?>"
                                        class="text-red-600 hover:text-red-900 confirm-delete-link"
                                        data-confirm-message="WARNING: Are you sure you want to delete this category? All the products inside this category will also be permanently deleted!">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="editCategoryForm" method="POST" action="manage_categories.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="id" id="editCategoryId">
    <input type="hidden" name="name" id="editCategoryName">
    <input type="hidden" name="edit_category" value="1">
</form>

<script>
function openEditCategoryModal(id, currentName) {
    Swal.fire({
        title: 'Edit Category',
        input: 'text',
        inputValue: currentName,
        showCancelButton: true,
        confirmButtonText: 'Save',
        confirmButtonColor: '#26658C',
        inputValidator: (value) => {
            if (!value) {
                return 'Category name cannot be empty!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editCategoryName').value = result.value;
            document.getElementById('editCategoryForm').submit();
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>