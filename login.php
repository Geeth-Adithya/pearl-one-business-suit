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

$error = '';

// --- Security Fix: Rate Limiting (Brute-Force Protection) ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if ($_SESSION['login_attempts'] >= 5) {
    if (time() - ($_SESSION['last_attempt_time'] ?? 0) < 300) {
        $_SESSION['error_message'] = 'Too many failed attempts. Please try again in 5 minutes.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['login_attempts'] = 0; // Reset after 5 mins
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Security Fix: CSRF Protection ---
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Token validation failed. Invalid request!");
    }

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $_SESSION['error_message'] = 'Please enter both email/username and password.';
        header('Location: login.php');
        exit;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ? OR username = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                $_SESSION['error_message'] = 'Your account has been disabled. Please contact the super administrator.';
                header('Location: login.php');
                exit;
            } else {
                // --- Security Fix: Prevent Session Fixation ---
                session_regenerate_id(true);

                // Reset login attempts on success
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_attempt_time']);

                // Password is correct
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'superadmin') {
                    $_SESSION['module_pos'] = 1;
                    $_SESSION['module_stock'] = 1;
                    $_SESSION['module_supply'] = 1;
                } elseif ($user['role'] === 'admin') {
                    $_SESSION['module_pos'] = $user['module_pos'];
                    $_SESSION['module_stock'] = $user['module_stock'];
                    $_SESSION['module_supply'] = $user['module_supply'];
                } elseif ($user['role'] === 'user') {
                    $_SESSION['assigned_admin_id'] = $user['assigned_admin_id'];
                    // Inherit from admin
                    $adminStmt = $pdo->prepare("SELECT module_pos, module_stock, module_supply FROM Users WHERE id = ?");
                    $adminStmt->execute([$user['assigned_admin_id']]);
                    $adminData = $adminStmt->fetch();
                    if ($adminData) {
                        $_SESSION['module_pos'] = $adminData['module_pos'];
                        $_SESSION['module_stock'] = $adminData['module_stock'];
                        $_SESSION['module_supply'] = $adminData['module_supply'];
                    } else {
                        $_SESSION['module_pos'] = 0;
                        $_SESSION['module_stock'] = 0;
                        $_SESSION['module_supply'] = 0;
                    }
                }

                // Redirect based on role
                if ($user['role'] === 'admin' || $user['role'] === 'superadmin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: user/dashboard.php');
                }
                exit;
            }
        } else {
            // Failed login
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $_SESSION['error_message'] = 'Invalid credentials.';
            header('Location: login.php');
            exit;
        }
    }
}

$is_space_page = true;
require_once 'includes/header.php';
?>

<div class="flex flex-col items-center justify-center min-h-[70vh] px-4 sm:px-6 lg:px-8">
    <div
        class="bg-white/90 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200 dark:border-gray-700 p-8 rounded-xl shadow-2xl w-full max-w-md">
        <h2 class="text-center text-3xl font-extrabold text-gray-900 dark:text-white mb-6">Sign in to your account</h2>

        <form class="space-y-6" action="login.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div>
                <label for="login" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email or
                    Username</label>
                <div class="mt-1">
                    <input id="login" name="login" type="text" autocomplete="username" required
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div>
                <label for="password"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="appearance-none block w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-blue focus:border-brand-blue sm:text-sm bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div class="mt-2 text-right">
                    <a href="<?= BASE_URL ?>/forgot_password.php" class="text-sm text-brand-blue hover:text-brand-blueDark font-medium">
                        Forgot Password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-blue hover:bg-brand-blueDark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-blue transition-colors">
                    Sign in
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">Please contact a super administrator to get an account.
            </p>
        </div>

        <div class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
            Powered by <a href="https://www.linkedin.com/in/rowsul-ilahi-b4611a19b/" target="_blank"
                rel="noopener noreferrer" class="hover:underline">Ilahi</a>
            &bull;
            Designed with <a href="https://www.facebook.com/share/1BADKhBsJM/" target="_blank" rel="noopener noreferrer"
                class="hover:underline">Pearlwaves</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
