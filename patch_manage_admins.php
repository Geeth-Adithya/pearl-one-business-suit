<?php
$file = 'admin/manage_admins.php';
$content = file_get_contents($file);

$content = str_replace('Add Admin', 'Add Shop', $content);
$content = str_replace('Create New Admin', 'Create New Shop', $content);
$content = str_replace('Manage Admins', 'Manage Shops', $content);
$content = str_replace('Existing Admins', 'Existing Shops', $content);
$content = str_replace('Create Admin', 'Create Shop', $content);

$pattern = '/<div>\s*<label for="username"\s*class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username<\/label>\s*<input type="text" name="username" id="username" required\s*class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600\s*rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm\s*bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">\s*<\/div>/s';

$replacement = <<<HTML
            <div>
                <label for="shop_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shop Name</label>
                <input type="text" name="shop_name" id="shop_name" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner's Name</label>
                <input type="text" name="full_name" id="full_name" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="shop_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
                <input type="text" name="shop_contact" id="shop_contact" required class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" name="username" id="username" required onkeyup="checkUsername(this.value, 'username_feedback', 'username')"
                    class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                <p id="username_feedback" class="text-xs mt-1"></p>
            </div>
HTML;

$content = preg_replace($pattern, $replacement, $content);

$js = <<<JS
<script>
let checkTimeout;
function checkUsername(username, feedbackId, inputId) {
    clearTimeout(checkTimeout);
    const feedback = document.getElementById(feedbackId);
    if(username.trim() === '') {
        feedback.innerHTML = '';
        return;
    }
    
    checkTimeout = setTimeout(() => {
        fetch('validate_username.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'username=' + encodeURIComponent(username)
        })
        .then(response => response.json())
        .then(data => {
            if(data.available) {
                feedback.className = 'text-xs mt-1 text-green-600';
                feedback.innerHTML = data.message;
            } else {
                feedback.className = 'text-xs mt-1 text-red-600';
                feedback.innerHTML = data.message + ' Suggestion: <a href="#" class="text-blue-600 underline cursor-pointer" onclick="document.getElementById(\'' + inputId + '\').value=\'' + data.suggestion + '\'; checkUsername(\'' + data.suggestion + '\', \'' + feedbackId + '\', \'' + inputId + '\'); return false;">' + data.suggestion + '</a>';
            }
        });
    }, 500);
}
</script>
JS;

$content = str_replace('</body>', $js . "\n</body>", $content);

file_put_contents($file, $content);
echo "Patched manage_admins.php\n";
?>

