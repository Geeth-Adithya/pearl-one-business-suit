<?php
$content = file_get_contents('login.php');

$oldLoginSuccess = <<<'EOD'
                // Password is correct
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'user') {
                    $_SESSION['assigned_admin_id'] = $user['assigned_admin_id'];
                }
EOD;

$newLoginSuccess = <<<'EOD'
                // Password is correct
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
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
EOD;

$content = str_replace($oldLoginSuccess, $newLoginSuccess, $content);

$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $False
[System.IO.File]::WriteAllText("login.php", $content, $Utf8NoBomEncoding)
echo "login.php updated";
?>
