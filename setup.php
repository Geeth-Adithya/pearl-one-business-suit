<?php
// setup.php

$host = getenv('DB_HOST') ?: "127.0.0.1";
$db_name = getenv('DB_NAME') ?: "store_app_db";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASSWORD') ?: ""; // Use hosting database credentials in production
$charset = "utf8mb4";

try {
    // Check if setup is already done by trying to connect to the DB and a table
    $pdo_check = new PDO("mysql:host=$host;dbname=$db_name;charset=$charset", $user, $pass);
    $stmt = $pdo_check->query("SELECT 1 FROM Users LIMIT 1");
    if ($stmt->fetchColumn() !== false) {
        $setup_already_done = true;
    }
} catch (PDOException $e) {
    // Errors are expected if DB or tables don't exist. We can ignore them and proceed with setup.
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="text-gray-100 min-h-screen flex flex-col relative"
    style="background-image: url('https://images.unsplash.com/photo-1506703719100-a0f3a48c0f41?q=80&w=2000&auto=format&fit=crop'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="absolute inset-0 bg-black/60 z-[-1]"></div>

    <div class="flex flex-col items-center justify-center min-h-[70vh] px-4 mt-12">
        <div class="bg-gray-800/80 backdrop-blur-md p-8 rounded-xl shadow-xl w-full max-w-2xl border border-gray-700">
            <h2 class="text-3xl font-bold mb-6 text-center">System Setup</h2>
            <div class="space-y-4">

                <?php
                if (isset($setup_already_done)) {
                    echo "<p class='text-green-400'>Setup appears to be already completed. The database and tables are in place.</p>";
                    echo "<p class='text-gray-300'>If you need to run setup again, you must first delete the database '<code>$db_name</code>'.</p>";
                    echo "<div class='mt-6 text-center'><a href='login.php' class='bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition'>Go to Login Page</a></div>";
                    echo "</div></div></div></body></html>";
                    exit; // Stop further execution
                }


                try {
                    // 1. Connect without database selected
                    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    echo "<p>Connected to MySQL server successfully.</p>";

                    // 2. Create Database
                    $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci";
                    $pdo->exec($sql);
                    echo "<p>Database `$db_name` created or already exists.</p>";

                    // 3. Connect to the specific database
                    $pdo->exec("USE `$db_name`");

                    // 4. Create Tables
                    $queries = [
                        "CREATE TABLE IF NOT EXISTS Users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('superadmin', 'admin', 'user') NOT NULL DEFAULT 'user',
            assigned_admin_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            subscription_plan ENUM('Monthly', 'Yearly') NULL,
            subscription_start_date DATETIME NULL,
            subscription_end_date DATETIME NULL,
            force_password_change TINYINT(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (assigned_admin_id) REFERENCES Users(id) ON DELETE SET NULL
        )",
                        "CREATE TABLE IF NOT EXISTS Categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            admin_id INT NULL,
            FOREIGN KEY (admin_id) REFERENCES Users(id) ON DELETE SET NULL
        )",
                        "CREATE TABLE IF NOT EXISTS Suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            admin_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_supplier_per_admin (name, admin_id),
            FOREIGN KEY (admin_id) REFERENCES Users(id) ON DELETE CASCADE
        )",
                        "CREATE TABLE IF NOT EXISTS Products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_code VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            attribute VARCHAR(255),
            type VARCHAR(100),
            purchasing_price DECIMAL(10,2),
            margin_percent DECIMAL(5,2),
            selling_price DECIMAL(10,2),
            unit VARCHAR(50),
            description TEXT,
            supplier_name VARCHAR(255),
            image_url VARCHAR(255),
            video_url VARCHAR(255),
            created_by_admin_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by_admin_id) REFERENCES Users(id) ON DELETE SET NULL
        )",
                        "CREATE TABLE IF NOT EXISTS Product_Categories (
            product_id INT NOT NULL,
            category_id INT NOT NULL,
            PRIMARY KEY (product_id, category_id),
            FOREIGN KEY (product_id) REFERENCES Products(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES Categories(id) ON DELETE CASCADE
        )",
                        "CREATE TABLE IF NOT EXISTS Public_Links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            supplier_name VARCHAR(255) NOT NULL,
            admin_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES Users(id) ON DELETE CASCADE
        )",
                        "CREATE TABLE IF NOT EXISTS Orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            shop_name VARCHAR(255),
            shop_address VARCHAR(500),
            contact_no VARCHAR(50),
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
        )",
                        "CREATE TABLE IF NOT EXISTS Order_Items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            product_id INT,
            quantity INT NOT NULL,
            price_at_purchase DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES Orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES Products(id) ON DELETE CASCADE
        )",
                        "CREATE TABLE IF NOT EXISTS Settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT
        )"
                    ];

                    foreach ($queries as $query) {
                        $pdo->exec($query);
                    }
                    echo "<p>All tables created successfully.</p>";

                    // Optional: Create a default superadmin user if none exists
                    $stmt = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'superadmin'");
                    if ($stmt->fetchColumn() == 0) {
                        $superadmin_pass = password_hash("1111", PASSWORD_BCRYPT);
                        $pdo->exec("INSERT INTO Users (username, email, password_hash, role, is_active) VALUES ('SuperAdmin', 'superadmin@store.com', '$superadmin_pass', 'superadmin', 1)");
                        echo "<p>Default superadmin user created. (Email: superadmin@store.com / Password: 1111)</p>";
                    }

                    echo "<div class='mt-8 text-center'><a href='index.php' class='bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition font-bold'>Go to Homepage</a></div>";

                } catch (\PDOException $e) {
                    echo "<div class='bg-red-500/20 border border-red-500 text-red-100 p-4 rounded'>Setup failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                ?>
            </div>
        </div>
    </div>
</body>

</html>
