<?php
$file = 'admin/manage_categories.php';
$content = file_get_contents($file);

// Fix 1: Edit logic backend - don't allow non-superadmins to edit global categories
$search1 = <<<PHP
            if (!\$isSuperAdmin) {
                \$updateSql .= " AND (admin_id = ? OR admin_id IS NULL)";
                \$updateParams[] = \$_SESSION['user_id'];
            }
PHP;
$replace1 = <<<PHP
            if (!\$isSuperAdmin) {
                \$updateSql .= " AND admin_id = ?"; // Cannot edit global categories
                \$updateParams[] = \$_SESSION['user_id'];
            }
PHP;
$content = str_replace($search1, $replace1, $content);

// Fix 2: Delete logic backend - don't allow non-superadmins to delete global categories
$search2 = <<<PHP
    if (!\$isSuperAdmin) {
        \$deleteSql .= " AND (admin_id = ? OR admin_id IS NULL)";
        \$deleteParams[] = \$_SESSION['user_id'];
    }
PHP;
$replace2 = <<<PHP
    if (!\$isSuperAdmin) {
        \$deleteSql .= " AND admin_id = ?"; // Cannot delete global categories
        \$deleteParams[] = \$_SESSION['user_id'];
    }
PHP;
$content = str_replace($search2, $replace2, $content);

// Fix 3: UI - hide Edit/Delete buttons if it's a global category and user is not superadmin
$search3 = <<<HTML
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="editing = true; \$nextTick(() => \$refs.input.focus())"
                                        class="text-brand-blue hover:text-brand-blueDark mr-3">Edit</button>
                                    <a href="manage_categories.php?delete=<?= \$category['id'] ?>"
                                        class="text-red-600 hover:text-red-900 confirm-delete-link"
                                        data-confirm-message="Are you sure you want to delete this category? This will remove it from all associated products.">Delete</a>
                                </td>
HTML;

$replace3 = <<<HTML
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if (\$isSuperAdmin || \$category['admin_id'] == \$_SESSION['user_id']): ?>
                                        <button @click="editing = true; \$nextTick(() => \$refs.input.focus())"
                                            class="text-brand-blue hover:text-brand-blueDark mr-3">Edit</button>
                                        <a href="manage_categories.php?delete=<?= \$category['id'] ?>"
                                            class="text-red-600 hover:text-red-900 confirm-delete-link"
                                            data-confirm-message="Are you sure you want to delete this category? This will remove it from all associated products.">Delete</a>
                                    <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-500 text-xs italic">Global (Read-only)</span>
                                    <?php endif; ?>
                                </td>
HTML;
$content = str_replace($search3, $replace3, $content);

// Fix 4: Also in the inline edit form, don't show the input if they can't edit
$search4 = <<<HTML
                            <tr x-data="{ editing: false }">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <form action="manage_categories.php" method="POST" x-show="editing"
                                        @submit.prevent="editing = false; \$el.submit()">
HTML;

$replace4 = <<<HTML
                            <tr x-data="{ editing: false }">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?php if (\$isSuperAdmin || \$category['admin_id'] == \$_SESSION['user_id']): ?>
                                    <form action="manage_categories.php" method="POST" x-show="editing"
                                        @submit.prevent="editing = false; \$el.submit()">
HTML;
$content = str_replace($search4, $replace4, $content);

$search5 = <<<HTML
                                        <input type="hidden" name="id" value="<?= \$category['id'] ?>"><input type="text"
                                            name="name" value="<?= htmlspecialchars(\$category['name']) ?>"
                                            class="bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md p-1"
                                            x-ref="input" @click.away="editing = false"><input type="hidden"
                                            name="edit_category">
                                    </form>
                                    <span x-show="!editing" @click="editing = true; \$nextTick(() => \$refs.input.focus())">
                                        <?= htmlspecialchars(\$category['name']) ?>
                                    </span>
                                </td>
HTML;

$replace5 = <<<HTML
                                        <input type="hidden" name="id" value="<?= \$category['id'] ?>"><input type="text"
                                            name="name" value="<?= htmlspecialchars(\$category['name']) ?>"
                                            class="bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md p-1"
                                            x-ref="input" @click.away="editing = false"><input type="hidden"
                                            name="edit_category">
                                    </form>
                                    <span x-show="!editing" @click="editing = true; \$nextTick(() => \$refs.input.focus())" class="cursor-pointer border-b border-dashed border-gray-400">
                                        <?= htmlspecialchars(\$category['name']) ?>
                                    </span>
                                    <?php else: ?>
                                        <span><?= htmlspecialchars(\$category['name']) ?></span>
                                    <?php endif; ?>
                                </td>
HTML;
$content = str_replace($search5, $replace5, $content);

file_put_contents($file, $content);
echo "Fixed global category editing leak in manage_categories.php\n";
?>

