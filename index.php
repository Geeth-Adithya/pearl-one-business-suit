<?php
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}
$is_space_page = true;
require_once 'includes/header.php'; // Must be after db.php

// Check if the initial setup (superadmin creation) has been completed.
$is_setup_complete = false;
try {
    $stmt = $pdo->query("SELECT 1 FROM Users WHERE role = 'superadmin' LIMIT 1");
    if ($stmt && $stmt->fetchColumn()) {
        $is_setup_complete = true;
    }
} catch (Exception $e) {
    // If this fails, it means tables are likely missing. Setup is not complete.
}
?>

<div class="flex flex-col items-center justify-center min-h-[70vh]">
    <div
        class="bg-white/90 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200 dark:border-gray-700 p-8 rounded-xl shadow-2xl w-full max-w-md text-center">
        <div class="mb-6">
            <img class="h-16 w-auto mx-auto" src="<?= BASE_URL ?>/assets/images/logo.png" alt="StoreApp Logo">
            <h1 class="text-3xl font-bold mt-4 text-gray-900 dark:text-white">Pearl Store</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Manage your purchases and sales</p>
        </div>

        <div class="space-y-4">
            <?php if (!$is_setup_complete): ?>
                <a href="setup.php"
                    class="block w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                    Run First-Time Setup
                </a>
            <?php endif; ?>
            <a href="login.php"
                class="block w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                Login
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>