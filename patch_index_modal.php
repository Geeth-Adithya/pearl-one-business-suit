<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

// Replace "Add Admin" text
$content = str_replace('<h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Admin</h3>', '<h3 class="text-lg font-bold text-gray-900 dark:text-white">Add Shop</h3>', $content);
$content = str_replace('<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly create a new admin account.</p>', '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly create a new shop account.</p>', $content);
$content = str_replace('<h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Admin</h3>', '<h3 class="text-lg font-medium text-gray-900 dark:text-white">Create New Shop</h3>', $content);
$content = str_replace('<button type="submit"
                    class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm 
transition">
                    Create Admin
                </button>', '<button type="submit" class="bg-brand-blue hover:bg-brand-blueDark text-white px-4 py-2 rounded-md shadow-sm transition">Create Shop</button>', $content);

// Update Modal Form
$modal_search = <<<HTML
            <div>
                <label for="username"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" required
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 
rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm 
bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
HTML;

$modal_replace = <<<HTML
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
                <label for="username_quick" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username_quick" required onkeyup="checkUsername(this.value, 'quick_username_feedback', 'username_quick')"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <p id="quick_username_feedback" class="text-xs mt-1"></p>
            </div>
HTML;

$content = str_replace(trim(preg_replace('/\s+/', ' ', $modal_search)), trim(preg_replace('/\s+/', ' ', $modal_replace)), preg_replace('/\s+/', ' ', $content));
// The above regex replace is messy. Let's do it cleaner.
PHP;
?>

