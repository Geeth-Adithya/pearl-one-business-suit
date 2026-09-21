<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

$pattern = '/<div>\s*<label for="username"\s*class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username<\/label>\s*<input type="text" name="username" id="username" required\s*class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600\s*rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm\s*bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">\s*<\/div>/s';

$replacement = <<<HTML
            <div>
                <label for="shop_name_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                <input type="text" name="shop_name" id="shop_name_quick" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="full_name_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner's Name</label>
                <input type="text" name="full_name" id="full_name_quick" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_contact_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                <input type="text" name="shop_contact" id="shop_contact_quick" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" required onkeyup="checkUsername(this.value, 'quick_username_feedback', 'username')"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <p id="quick_username_feedback" class="text-xs mt-1"></p>
            </div>
HTML;

$content = preg_replace($pattern, $replacement, $content, 1);

file_put_contents($file, $content);
echo "Patched admin/index.php form\n";
?>

