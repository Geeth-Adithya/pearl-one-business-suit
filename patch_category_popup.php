<?php
$file = 'admin/manage_categories.php';
$content = file_get_contents($file);

$pattern = '/<tr x-data="\{ editing: false \}">[\s\S]*?<\/tr>/m';

$replacement = <<<HTML
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars(\$category['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="openEditCategoryModal(<?= \$category['id'] ?>, '<?= addslashes(htmlspecialchars(\$category['name'])) ?>')"
                                        class="text-brand-blue hover:text-brand-blueDark mr-3">Edit</button>
                                    <a href="manage_categories.php?delete=<?= \$category['id'] ?>"
                                        class="text-red-600 hover:text-red-900 confirm-delete-link"
                                        data-confirm-message="Are you sure you want to delete this category? This will remove it from all associated products.">Delete</a>
                                </td>
                            </tr>
HTML;

$content = preg_replace($pattern, $replacement, $content);

// Add the modal form and JS at the bottom before footer
$modalCode = <<<HTML
<form id="editCategoryForm" method="POST" action="manage_categories.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '') ?>">
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
HTML;

$content = str_replace("<?php require_once '../includes/footer.php'; ?>", $modalCode, $content);

file_put_contents($file, $content);
echo "Replaced inline edit with popup.\n";
?>

