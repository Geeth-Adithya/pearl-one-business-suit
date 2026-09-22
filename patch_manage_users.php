<?php
$file = 'admin/manage_users.php';
$content = file_get_contents($file);

// Add the 'Name' field (mapped to full_name) right after Username
$search = <<<HTML
            <div>
                <label for="user_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
HTML;

$replace = <<<HTML
            <div>
                <label for="user_full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="full_name" id="user_full_name" required
                    class="mt-1 appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="">
            </div>
            <div>
                <label for="user_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
HTML;

// Since we added a 5th column, let's update the grid to lg:grid-cols-5
$content = str_replace('lg:grid-cols-4', 'lg:grid-cols-5', $content);
$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Added Name field to manage_users.php\n";
?>
