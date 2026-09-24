<?php
$file = 'includes/db.php';
$content = file_get_contents($file);

$tableQuery = <<<EOD
        "CREATE TABLE IF NOT EXISTS Password_Resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            reset_code VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS Settings
EOD;

$content = str_replace('"CREATE TABLE IF NOT EXISTS Settings', $tableQuery, $content);
file_put_contents($file, $content);

// Also execute the query right now so the user doesn't have to wait for the next DB connection init if it's skipped
require_once $file;
$pdo->exec("CREATE TABLE IF NOT EXISTS Password_Resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    reset_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

echo "Password_Resets table created and db.php updated.";
?>
