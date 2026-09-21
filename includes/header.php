<?php
// Initialize global shop currency if not already set
if (!isset($shop_currency)) {
    $shop_currency = 'Rs';
    if (isset($_SESSION['user_id']) && isset($pdo)) {
        $admin_id_to_use = $_SESSION['assigned_admin_id'] ?? $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
        $stmt->execute(['shop_currency_' . $admin_id_to_use]);
        $val = $stmt->fetchColumn();
        if ($val) {
            $shop_currency = $val;
        }
    }
}
?>
<?php
// Session and BASE_URL are now expected to be handled by db.php before this file is included.

?>
<!DOCTYPE html>
<html lang="en">
<script>
    // Apply system theme or saved theme immediately to prevent flashing
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>



<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pearl Store</title>
    <!-- Favicon -->
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/logo.png" type="image/png">
    <!-- Tailwind CSS (Local Fallback) -->
    <script src="<?= BASE_URL ?>/assets/js/tailwind.js"></script>
    <!-- Tailwind Config for Custom Colors -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue: '#26658C',
                            blueDark: '#023859',
                            light: '#54ACBF',
                            lighter: '#A7EBF2',
                        },
                        gray: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#26658C',
                            800: '#023859',
                            900: '#011C40',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <!-- AlpineJS for dropdowns -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Ionicons for modern icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<?php
$body_class = "bg-gradient-to-br from-cyan-100 via-blue-50 to-indigo-100 text-gray-900 dark:bg-gradient-to-br dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 dark:text-gray-100 min-h-screen flex flex-col";
$body_style = "";
if (isset($is_space_page) && $is_space_page) {
    // A nice space background with stars (via unsplash or CSS gradient)
    $body_class = "text-gray-100 min-h-screen flex flex-col relative";
    $body_style = "background-image: url('https://images.unsplash.com/photo-1506703719100-a0f3a48c0f41?q=80&w=2000&auto=format&fit=crop'); background-size: cover; background-position: center; background-attachment: fixed;";
}
?>

<body class="<?= $body_class ?>" style="<?= $body_style ?>">
    <?php if (isset($is_space_page) && $is_space_page): ?>
        <!-- Overlay to make text readable -->
        <div class="absolute inset-0 bg-black/60 z-[-1]"></div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-cyan-50 to-blue-100 dark:bg-gradient-to-r dark:from-gray-900 dark:to-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="<?= isset($full_width_layout) && $full_width_layout ? 'w-full px-4 sm:px-6' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' ?>">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="<?= BASE_URL ?>/index.php" class="flex-shrink-0 flex items-center gap-3">
                        <img class="h-8 w-auto" src="<?= BASE_URL ?>/assets/images/logo.png" alt="Pearl Store Logo">
                        <span class="text-xl font-bold text-gray-900 dark:text-white tracking-wide">Pearl Store</span>
                    </a>
                </div>
                <div class="flex items-center gap-4">


                    <button id="theme-toggle"
                        class="p-2 rounded-full text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none">
                        <ion-icon name="moon" id="theme-icon" class="text-xl"></ion-icon>
                    </button>

                    <button onclick="location.reload();"
                        class="p-2 rounded-full text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none"
                        title="Refresh">
                        <ion-icon name="refresh" class="text-xl"></ion-icon>
                    </button>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        $dashboard_url = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin')
                            ? BASE_URL . '/admin/index.php'
                            : BASE_URL . '/user/dashboard.php';
                        ?>
                        <a href="<?= $dashboard_url ?>"
                            class="p-2 rounded-full text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none"
                            title="Home">
                            <ion-icon name="home" class="text-xl"></ion-icon>
                        </a>



                        <div class="relative flex items-center gap-2" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="flex text-sm bg-gray-300 dark:bg-gray-700 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-600 focus:ring-brand-blue">
                                <span class="sr-only">Open user menu</span>
                                <div
                                    class="h-8 w-8 rounded-full flex items-center justify-center text-white bg-brand-blue font-bold">
                                    <?= strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                            </button>
                            <a href="<?= BASE_URL ?>/logout.php"
                                class="p-2 rounded-full text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none"
                                title="Logout">
                                <ion-icon name="log-out-outline" class="text-xl"></ion-icon>
                            </a>
                            <div x-show="open" @click.away="open = false" x-transition style="display: none;"
                                class="origin-top-right absolute right-0 top-full mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                <div class="py-1" role="menu" aria-orientation="vertical"
                                    aria-labelledby="user-menu-button">
                                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white" title="Username">
                                            <?= htmlspecialchars($_SESSION['username']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" title="Role">
                                            <?= htmlspecialchars(ucfirst($_SESSION['role'])); ?>
                                        </p>
                                    </div>
                                    <?php if ($_SESSION['role'] === 'user' && !empty($_SESSION['assigned_admin_id']) && isset($pdo)): ?>
                                        <?php
                                        try {
                                            $admin_stmt = $pdo->prepare("SELECT username FROM Users WHERE id = ?");
                                            $admin_stmt->execute([$_SESSION['assigned_admin_id']]);
                                            if ($admin = $admin_stmt->fetch()): ?>
                                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Assigned Admin</p>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        <?= htmlspecialchars($admin['username']); ?>
                                                    </p>
                                                </div>
                                            <?php endif;
                                        } catch (PDOException $e) { /* Fail silently if DB not available */
                                        }
                                        ?>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/reset-password.php"
                                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        role="menuitem">Reset Password</a>
                                    <a href="<?= BASE_URL ?>/logout.php"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        role="menuitem">Logout</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (basename($_SERVER['SCRIPT_NAME']) !== 'login.php'): ?>
                            <a href="<?= BASE_URL ?>/login.php"
                                class="text-gray-600 dark:text-gray-300 hover:text-brand-blue dark:hover:text-brand-blue font-medium">Login</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php
    // Centralized notification handling
    // Ensure local variables are defined to prevent notices on pages that don't use them.
    if (!isset($error))
        $error = '';
    if (!isset($success))
        $success = '';

    $notification_message = '';
    $notification_type = '';

    // Prioritize session messages from redirects, as they relate to a completed action.
    if (isset($_SESSION['success_message'])) {
        $notification_message = $_SESSION['success_message'];
        $notification_type = 'success';
        unset($_SESSION['success_message']);
        unset($_SESSION['error_message']); // Clear both to avoid conflicts
    } elseif (isset($_SESSION['error_message'])) {
        $notification_message = $_SESSION['error_message'];
        $notification_type = 'error';
        unset($_SESSION['error_message']);
        unset($_SESSION['success_message']);
    }
    // If no session message, check for locally set variables from the current page's logic (e.g., form validation).
    elseif (!empty($error)) {
        $notification_message = $error;
        $notification_type = 'error';
    } elseif (!empty($success)) {
        $notification_message = $success;
        $notification_type = 'success';
    }

    if (!empty($notification_message)): ?>
        <div class="server-notification hidden" data-notification-type="<?= $notification_type ?>"
            data-notification-message="<?= htmlspecialchars($notification_message, ENT_QUOTES) ?>"></div>
        <?php
    endif;
    ?> <!-- Main Content Wrapper -->
    <main class="flex-grow w-full <?= isset($full_width_layout) && $full_width_layout ? 'px-4 sm:px-6 py-6' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8' ?>">