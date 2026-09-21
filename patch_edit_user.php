<?php
$file = 'admin/edit_user.php';
$content = file_get_contents($file);

// 1. Fix authorization
$search1 = <<<PHP
// Authorization check for superadmin
if (!isset(\$_SESSION['role']) || \$_SESSION['role'] !== 'superadmin') {
    \$_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}
PHP;

$replace1 = <<<PHP
// Authorization check for superadmin and admin
if (!isset(\$_SESSION['role']) || !in_array(\$_SESSION['role'], ['superadmin', 'admin'])) {
    \$_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: index.php');
    exit;
}
PHP;
$content = str_replace($search1, $replace1, $content);

// 2. Fix Fetching User
$search2 = <<<PHP
// Fetch the user's data
\$stmt = \$pdo->prepare("SELECT id, username, full_name, email, assigned_admin_id FROM Users WHERE id = ? AND role = 'user'");
\$stmt->execute([\$user_id]);
\$user = \$stmt->fetch();
PHP;

$replace2 = <<<PHP
// Fetch the user's data
if (\$_SESSION['role'] === 'admin') {
    \$stmt = \$pdo->prepare("SELECT id, username, full_name, email, assigned_admin_id FROM Users WHERE id = ? AND role = 'user' AND assigned_admin_id = ?");
    \$stmt->execute([\$user_id, \$_SESSION['user_id']]);
} else {
    \$stmt = \$pdo->prepare("SELECT id, username, full_name, email, assigned_admin_id FROM Users WHERE id = ? AND role = 'user'");
    \$stmt->execute([\$user_id]);
}
\$user = \$stmt->fetch();
PHP;
$content = str_replace($search2, $replace2, $content);

// 3. Fix Backend Processing for assigned_admin_id
$search3 = "\$assigned_admin_id = \$_POST['assigned_admin_id'] == '0' ? null : (int) \$_POST['assigned_admin_id'];";
$replace3 = <<<PHP
if (\$_SESSION['role'] === 'admin') {
        \$assigned_admin_id = \$_SESSION['user_id'];
    } else {
        \$assigned_admin_id = isset(\$_POST['assigned_admin_id']) && \$_POST['assigned_admin_id'] == '0' ? null : (int) \$_POST['assigned_admin_id'];
    }
PHP;
$content = str_replace($search3, $replace3, $content);

// 4. Hide Assigned Shop Dropdown for Admin
$search4 = <<<PHP
            <div>
                <label for="assigned_admin_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign
                    to Shop (Admin)</label>
                <select name="assigned_admin_id" id="assigned_admin_id"
PHP;

$replace4 = <<<PHP
            <?php if (\$_SESSION['role'] === 'superadmin'): ?>
            <div>
                <label for="assigned_admin_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign
                    to Shop (Admin)</label>
                <select name="assigned_admin_id" id="assigned_admin_id"
PHP;
$content = str_replace($search4, $replace4, $content);

$search5 = <<<PHP
                    <?php endforeach; ?>
                </select>
            </div>
PHP;

$replace5 = <<<PHP
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
PHP;
$content = str_replace($search5, $replace5, $content);

file_put_contents($file, $content);
echo "Patched edit_user.php for admin access.\n";
?>
