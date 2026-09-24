<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $login = trim($input['login'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email/username and password.']);
        exit;
    }

    // --- Rate limiting: max 5 attempts per IP per 15 minutes ---
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitKey = 'login_attempts_' . md5($ip);
    $rateLimitTimeKey = 'login_lockout_' . md5($ip);
    
    if (!isset($_SESSION[$rateLimitKey])) $_SESSION[$rateLimitKey] = 0;
    if (!isset($_SESSION[$rateLimitTimeKey])) $_SESSION[$rateLimitTimeKey] = 0;

    // Check if locked out
    if ($_SESSION[$rateLimitKey] >= 5 && time() - $_SESSION[$rateLimitTimeKey] < 900) {
        $remaining = ceil((900 - (time() - $_SESSION[$rateLimitTimeKey])) / 60);
        echo json_encode(['success' => false, 'message' => "Too many failed attempts. Try again in $remaining minutes."]);
        exit;
    }

    // Reset if lockout expired
    if ($_SESSION[$rateLimitKey] >= 5 && time() - $_SESSION[$rateLimitTimeKey] >= 900) {
        $_SESSION[$rateLimitKey] = 0;
    }

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ? OR username = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            echo json_encode(['success' => false, 'message' => 'Your account is disabled.']);
            exit;
        }

        // Reset rate limit on success
        $_SESSION[$rateLimitKey] = 0;

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Optionally fetch shop details if admin
        $shopName = 'My Shop';
        if ($user['role'] === 'admin') {
            $shopStmt = $pdo->prepare("SELECT setting_value FROM Settings WHERE setting_key = ?");
            $shopStmt->execute(['shop_name_' . $user['id']]);
            $shopName = $shopStmt->fetchColumn() ?: 'My Shop';
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'shopName' => $shopName
            ]
        ]);
        exit;
    } else {
        // Increment failed attempts
        $_SESSION[$rateLimitKey]++;
        if ($_SESSION[$rateLimitKey] >= 5) {
            $_SESSION[$rateLimitTimeKey] = time();
        }
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
}
?>
