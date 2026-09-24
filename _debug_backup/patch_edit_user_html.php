<?php
$file = 'admin/edit_user.php';
$content = file_get_contents($file);

// Add full_name to SELECT query
$content = str_replace(
    "SELECT id, username, email, assigned_admin_id FROM Users",
    "SELECT id, username, full_name, email, assigned_admin_id FROM Users",
    $content
);

// Add full_name POST processing
$content = str_replace(
    "\$username = trim(\$_POST['username']);",
    "\$username = trim(\$_POST['username']);\n    \$full_name = trim(\$_POST['full_name'] ?? '');",
    $content
);

// Add full_name to UPDATE query
$content = str_replace(
    "UPDATE Users SET username = ?, email = ?, assigned_admin_id = ?",
    "UPDATE Users SET full_name = ?, username = ?, email = ?, assigned_admin_id = ?",
    $content
);

$content = str_replace(
    "\$params = [\$username, \$email, \$assigned_admin_id];",
    "\$params = [\$full_name, \$username, \$email, \$assigned_admin_id];",
    $content
);

// Re-populate logic
$content = str_replace(
    "\$user['username'] = \$username;",
    "\$user['username'] = \$username;\n    \$user['full_name'] = \$full_name;",
    $content
);

// HTML insertion
$html = <<<HTML
            <div>
                <label for="full_name"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars(\$user['full_name'] ?? '') ?>"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
            </div>
HTML;

$content = str_replace(
    '        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">',
    "        <div class=\"grid grid-cols-1 md:grid-cols-2 gap-6\">\n" . $html,
    $content
);

// Fix admin restriction in UI
$content = str_replace(
    'if ($_SESSION[\'role\'] === \'superadmin\')',
    'if ($_SESSION[\'role\'] === \'superadmin\')', // Just checking, we need to hide the assign admin dropdown if role is admin
    $content
);

file_put_contents($file, $content);
echo "Updated edit_user.php layout\n";
?>

