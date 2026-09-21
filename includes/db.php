<?php
// includes/db.php

// Start session if not already started
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

date_default_timezone_set('Asia/Colombo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!defined('BASE_URL')) {
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $base = (basename($script_dir) === 'admin' || basename($script_dir) === 'user') ? dirname($script_dir) : $script_dir;
    define('BASE_URL', rtrim(str_replace('\\', '/', $base), '/'));
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db = getenv('DB_NAME') ?: 'store_app_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // --- Schema Validation and Creation ---
    $table_queries = [
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
            subscription_plan ENUM('7 Days', 'Monthly', 'Yearly') NULL,
            subscription_start_date DATETIME NULL,
            subscription_end_date DATETIME NULL,
            FOREIGN KEY (assigned_admin_id) REFERENCES Users(id) ON DELETE SET NULL
        )",
        "CREATE TABLE IF NOT EXISTS Categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            admin_id INT NULL,
            FOREIGN KEY (admin_id) REFERENCES Users(id) ON DELETE SET NULL
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
            stock_quantity INT DEFAULT 0,
            low_stock_threshold INT DEFAULT 10,
            description TEXT,
            supplier_name VARCHAR(255),
            image_url VARCHAR(255),
            video_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
                "CREATE TABLE IF NOT EXISTS Password_Resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            reset_code VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS Settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT
        )"
    ];
    foreach ($table_queries as $query) {
        $pdo->exec($query);
    }

    $attributeColumn = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Products' AND COLUMN_NAME = 'attribute'");
    if ($attributeColumn->fetchColumn() === false) {
        $pdo->exec("ALTER TABLE `Products` ADD COLUMN `attribute` VARCHAR(255) NULL AFTER `name`");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS Suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(100) NULL,
        address TEXT NULL,
        product_types TEXT NULL,
        admin_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_supplier_per_admin (name, admin_id),
        FOREIGN KEY (admin_id) REFERENCES Users(id) ON DELETE CASCADE
    )");

    // Migration to remove retired ZSA and SA product fields
    foreach (['zsa', 'sa'] as $column) {
        $checkProductColumn = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Products' AND COLUMN_NAME = ?");
        $checkProductColumn->execute([$db, $column]);
        if ($checkProductColumn->fetchColumn() !== false) {
            $pdo->exec("ALTER TABLE `Products` DROP COLUMN `$column`");
        }
    }

    // Migration to store shop details with each order
    foreach ([
        'shop_name' => 'VARCHAR(255) NULL AFTER `user_id`',
        'shop_address' => 'VARCHAR(500) NULL AFTER `shop_name`',
        'contact_no' => 'VARCHAR(50) NULL AFTER `shop_address`'
    ] as $column => $definition) {
        $checkOrderColumn = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Orders' AND COLUMN_NAME = ?");
        $checkOrderColumn->execute([$db, $column]);
        if ($checkOrderColumn->fetchColumn() === false) {
            $pdo->exec("ALTER TABLE `Orders` ADD COLUMN `$column` $definition");
        }
    }

    // Migration: single category_id -> Product_Categories table
    $checkOldCategoryColumn = $pdo->prepare("SHOW COLUMNS FROM `Products` LIKE 'category_id'");
    $checkOldCategoryColumn->execute();
    if ($checkOldCategoryColumn->rowCount() > 0) {
        $pdo->exec("INSERT IGNORE INTO Product_Categories (product_id, category_id)
                    SELECT id, category_id FROM Products WHERE category_id IS NOT NULL");

        $fk_name_stmt = $pdo->prepare("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                       WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Products' AND COLUMN_NAME = 'category_id' AND REFERENCED_TABLE_NAME = 'Categories'");
        $fk_name_stmt->execute([$db]);
        if ($fk_name_row = $fk_name_stmt->fetch()) {
            $fk_name = $fk_name_row['CONSTRAINT_NAME'];
            if ($fk_name && $fk_name !== 'PRIMARY') {
                $pdo->exec("ALTER TABLE Products DROP FOREIGN KEY `{$fk_name}`");
            }
        }
        $pdo->exec("ALTER TABLE Products DROP COLUMN category_id");
    }

    // Migration to add stock tracking
    $checkStockColumn = $pdo->prepare("SHOW COLUMNS FROM `Products` LIKE 'stock_quantity'");
    $checkStockColumn->execute();
    if ($checkStockColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Products` ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 0 AFTER `type`");
    }

    // Migration to add admin_id to public links
    $checkAdminIdColumn = $pdo->prepare("SHOW COLUMNS FROM `Public_Links` LIKE 'admin_id'");
    $checkAdminIdColumn->execute();
    if ($checkAdminIdColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Public_Links` ADD COLUMN `admin_id` INT NULL AFTER `supplier_name`");
        $pdo->exec("ALTER TABLE `Public_Links` ADD CONSTRAINT `fk_public_links_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `Users`(`id`) ON DELETE CASCADE");
    }

    // Migration to add admin_id to categories
    $checkCategoryAdminIdColumn = $pdo->prepare("SHOW COLUMNS FROM `Categories` LIKE 'admin_id'");
    $checkCategoryAdminIdColumn->execute();
    if ($checkCategoryAdminIdColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Categories` ADD COLUMN `admin_id` INT NULL AFTER `name`");
        $pdo->exec("ALTER TABLE `Categories` ADD CONSTRAINT `fk_categories_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `Users`(`id`) ON DELETE SET NULL");
    }

    // Migration to add created_by_admin_id to products
    $checkCreatedByAdminIdColumn = $pdo->prepare("SHOW COLUMNS FROM `Products` LIKE 'created_by_admin_id'");
    $checkCreatedByAdminIdColumn->execute();
    if ($checkCreatedByAdminIdColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Products` ADD COLUMN `created_by_admin_id` INT NULL AFTER `video_url`");
        $pdo->exec("ALTER TABLE `Products` ADD CONSTRAINT `fk_products_created_by_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `Users`(`id`) ON DELETE SET NULL");
    }

    // Import existing product supplier names
    $pdo->exec("INSERT IGNORE INTO Suppliers (name, admin_id)
                SELECT DISTINCT supplier_name, created_by_admin_id
                FROM Products
                WHERE supplier_name IS NOT NULL AND TRIM(supplier_name) != ''");

    // Migration to add full_name
    $checkFullName = $pdo->prepare("SHOW COLUMNS FROM `Users` LIKE 'full_name'");
    $checkFullName->execute();
    if ($checkFullName->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Users` ADD COLUMN `full_name` VARCHAR(255) NULL AFTER `username`");
    }

    // Migration to add is_active
    $checkIsActiveColumn = $pdo->prepare("SHOW COLUMNS FROM `Users` LIKE 'is_active'");
    $checkIsActiveColumn->execute();
    if ($checkIsActiveColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `last_seen`");
    }

    // Migration: ensure subscription_plan includes '7 Days'
    $pdo->exec("ALTER TABLE `Users` MODIFY COLUMN `subscription_plan` ENUM('7 Days', 'Monthly', 'Yearly') NULL DEFAULT NULL");

    // Migration: add subscription date columns
    foreach ([
        'subscription_start_date' => 'DATETIME NULL AFTER `subscription_plan`',
        'subscription_end_date' => 'DATETIME NULL AFTER `subscription_start_date`'
    ] as $column => $definition) {
        $checkCol = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Users' AND COLUMN_NAME = ?");
        $checkCol->execute([$column]);
        if ($checkCol->fetchColumn() === false) {
            $pdo->exec("ALTER TABLE `Users` ADD COLUMN `$column` $definition");
        }
    }

    // Migration: add force_password_change
    $checkFPC = $pdo->prepare("SHOW COLUMNS FROM `Users` LIKE 'force_password_change'");
    $checkFPC->execute();
    if ($checkFPC->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Users` ADD COLUMN `force_password_change` TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Migration: add module permission columns
    foreach (['module_pos', 'module_stock', 'module_supply'] as $modCol) {
        $checkModCol = $pdo->prepare("SHOW COLUMNS FROM `Users` LIKE '$modCol'");
        $checkModCol->execute();
        if ($checkModCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `Users` ADD COLUMN `$modCol` TINYINT(1) NOT NULL DEFAULT 1");
        }
    }

    // Migration to add search_keywords to Products if missing
    $checkSearchKeywords = $pdo->prepare("SHOW COLUMNS FROM `Products` LIKE 'search_keywords'");
    $checkSearchKeywords->execute();
    if ($checkSearchKeywords->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `Products` ADD COLUMN `search_keywords` VARCHAR(255) NULL AFTER `name`");
    }

} catch (\PDOException $e) {
    $errorCode = (string) $e->getCode();
    $errorMessage = $e->getMessage();
    if ($errorCode === '1049' || strpos($errorMessage, 'Unknown database') !== false) {
        die("Database not found. Please run <a href='setup.php'>setup.php</a> to initialize the database.");
    } elseif ($errorCode === '2002' || strpos($errorMessage, 'actively refused it') !== false) {
        die("Could not connect to the database server. Please ensure your database server (e.g., MySQL in XAMPP) is running and that the connection settings in <code>includes/db.php</code> are correct.");
    } else {
        throw new \PDOException($e->getMessage(), (int) $e->getCode());
    }
}
?>