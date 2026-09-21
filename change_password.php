<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Token validation failed.");
    }
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 4) {
        $error = "Password must be at least 4 characters.";
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE Users SET password_hash = ?, force_password_change = 0 WHERE id = ?");
        if ($stmt->execute([$hash, $_SESSION['user_id']])) {
            $_SESSION['success_message'] = "Password updated successfully.";
            if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin') {
                header("Location: admin/index.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { brand: { blue: '#2563eb', blueDark: '#1d4ed8' } } } } }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-8">
        <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">Change Password Required</h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm">Since this is your first time logging in, please set a new permanent password for your account.</p>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="change_password.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                <input type="password" name="new_password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-blue focus:border-brand-blue sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                <input type="password" name="confirm_password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-brand-blue focus:border-brand-blue sm:text-sm">
            </div>
            <button type="submit" class="w-full bg-brand-blue hover:bg-brand-blueDark text-white py-2 px-4 rounded-md shadow-sm transition">Change Password</button>
        </form>
    </div>
</body>
</html>
