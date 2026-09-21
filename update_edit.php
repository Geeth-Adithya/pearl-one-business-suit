<?php
$content = file_get_contents('admin/edit_admin.php');

// 1. Update SELECT to include module columns
$content = str_replace(
    'SELECT id, username, email, subscription_plan FROM Users WHERE id = ? AND role = ''admin''',
    'SELECT id, username, email, subscription_plan, module_pos, module_stock, module_supply FROM Users WHERE id = ? AND role = ''admin''',
    $content
);

// 2. Update POST handling to fetch modules
$oldPost = <<<'EOD'
        } else {
            $params = [$username, $email, $subscription_plan];
            $sql = "UPDATE Users SET username = ?, email = ?, subscription_plan = ?";
EOD;

$newPost = <<<'EOD'
        } else {
            $module_pos = isset($_POST['module_pos']) ? 1 : 0;
            $module_stock = isset($_POST['module_stock']) ? 1 : 0;
            $module_supply = isset($_POST['module_supply']) ? 1 : 0;

            $params = [$username, $email, $subscription_plan, $module_pos, $module_stock, $module_supply];
            $sql = "UPDATE Users SET username = ?, email = ?, subscription_plan = ?, module_pos = ?, module_stock = ?, module_supply = ?";
EOD;
$content = str_replace($oldPost, $newPost, $content);

// 3. Add checkboxes to HTML form
$oldHtml = <<<'EOD'
        <div>
            <label for="subscription_plan"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription Plan</label>
            <select name="subscription_plan" id="subscription_plan" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="Monthly" <?= $admin['subscription_plan'] === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="Yearly" <?= $admin['subscription_plan'] === 'Yearly' ? 'selected' : '' ?>>Yearly</option>
            </select>
        </div>
        <div class="flex justify-between items-center pt-4">
EOD;

$newHtml = <<<'EOD'
        <div>
            <label for="subscription_plan"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subscription Plan</label>
            <select name="subscription_plan" id="subscription_plan" required
                class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="Monthly" <?= $admin['subscription_plan'] === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="Yearly" <?= $admin['subscription_plan'] === 'Yearly' ? 'selected' : '' ?>>Yearly</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Enabled Modules</label>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="module_pos" value="1" <?= $admin['module_pos'] ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-blue shadow-sm focus:ring-brand-blue dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">POS System</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="module_stock" value="1" <?= $admin['module_stock'] ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-blue shadow-sm focus:ring-brand-blue dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Stock Management</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="module_supply" value="1" <?= $admin['module_supply'] ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-blue shadow-sm focus:ring-brand-blue dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Supply Management</span>
                </label>
            </div>
        </div>

        <div class="flex justify-between items-center pt-4">
EOD;
$content = str_replace($oldHtml, $newHtml, $content);

$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $False
[System.IO.File]::WriteAllText("admin/edit_admin.php", $content, $Utf8NoBomEncoding)
echo "edit_admin.php updated";
?>
