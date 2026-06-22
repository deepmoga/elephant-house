<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'elephant_house';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `name` VARCHAR(200) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `parent_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `api_category_id` VARCHAR(100) NOT NULL,
        `api_category_name` VARCHAR(200) NOT NULL,
        `name` VARCHAR(200) NOT NULL,
        `slug` VARCHAR(200) NOT NULL UNIQUE,
        `image` VARCHAR(500) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `show_in_menu` TINYINT(1) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `category_mapping` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `parent_category_id` INT NOT NULL,
        `api_category_id` VARCHAR(100) NOT NULL,
        `api_category_name` VARCHAR(200) NOT NULL,
        `image` VARCHAR(500) DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`parent_category_id`) REFERENCES `parent_categories`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_mapping` (`parent_category_id`, `api_category_id`)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `banners` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(300) DEFAULT NULL,
        `subtitle` VARCHAR(500) DEFAULT NULL,
        `image` VARCHAR(500) NOT NULL,
        `link` VARCHAR(500) DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `offer_banners` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(300) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `image` VARCHAR(500) NOT NULL,
        `link` VARCHAR(500) DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `site_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `pages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `title` VARCHAR(300) NOT NULL,
        `content` LONGTEXT DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(200) NOT NULL,
        `email` VARCHAR(200) NOT NULL UNIQUE,
        `phone` VARCHAR(50) DEFAULT NULL,
        `password` VARCHAR(255) NOT NULL,
        `address` VARCHAR(500) DEFAULT NULL,
        `city` VARCHAR(100) DEFAULT NULL,
        `state` VARCHAR(100) DEFAULT NULL,
        `postcode` VARCHAR(20) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT DEFAULT NULL,
        `order_number` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(200) NOT NULL,
        `email` VARCHAR(200) NOT NULL,
        `phone` VARCHAR(50) DEFAULT NULL,
        `address` VARCHAR(500) NOT NULL,
        `city` VARCHAR(100) NOT NULL,
        `state` VARCHAR(100) NOT NULL,
        `postcode` VARCHAR(20) NOT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `coupon_code` VARCHAR(50) DEFAULT NULL,
        `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `status` ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` VARCHAR(100) NOT NULL,
        `product_name` VARCHAR(300) NOT NULL,
        `product_image` VARCHAR(500) DEFAULT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `coupons` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
        `value` DECIMAL(10,2) NOT NULL,
        `min_order` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `max_uses` INT NOT NULL DEFAULT 0,
        `used_count` INT NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `expires_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `blogs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(300) NOT NULL,
        `slug` VARCHAR(300) NOT NULL UNIQUE,
        `excerpt` TEXT DEFAULT NULL,
        `content` LONGTEXT DEFAULT NULL,
        `image` VARCHAR(500) DEFAULT NULL,
        `author` VARCHAR(200) DEFAULT NULL,
        `is_published` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `faqs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `question` VARCHAR(500) NOT NULL,
        `answer` TEXT NOT NULL,
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Migrations for existing installs
    $migrations = [
        "ALTER TABLE `parent_categories` ADD COLUMN `api_category_id` VARCHAR(100) NOT NULL DEFAULT '' AFTER `id`",
        "ALTER TABLE `parent_categories` ADD COLUMN `api_category_name` VARCHAR(200) NOT NULL DEFAULT '' AFTER `api_category_id`",
        "ALTER TABLE `parent_categories` ADD COLUMN `show_in_menu` TINYINT(1) DEFAULT 0 AFTER `sort_order`",
        "ALTER TABLE `category_mapping` ADD COLUMN `image` VARCHAR(500) DEFAULT NULL AFTER `api_category_name`",
    ];
    foreach ($migrations as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) {}
    }

    // Insert default admin
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `admin_users` (`username`, `password`, `name`) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $hash, 'Administrator']);

    // Insert default settings
    $defaults = [
        'site_name' => 'Elephant House',
        'site_tagline' => 'Premium Sri Lankan & Asian Grocery Store',
        'phone' => '+61 3 9999 0000',
        'email' => 'info@elephanthouse.com.au',
        'address' => '123 Main Street, Melbourne, VIC 3000',
        'facebook' => '',
        'instagram' => '',
        'opening_hours' => 'Mon-Sat: 9AM - 8PM | Sun: 10AM - 6PM',
        'logo' => '',
        'favicon' => '',
        'footer_text' => '© 2026 Elephant House. All Rights Reserved.',
        'google_maps' => '',
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    // Insert default pages
    $pages = [
        ['about-us', 'About Us', '<h2>Welcome to Elephant House</h2><p>Your one-stop destination for authentic Sri Lankan and Asian groceries in Australia.</p>'],
        ['privacy-policy', 'Privacy Policy', '<h2>Privacy Policy</h2><p>Your privacy is important to us.</p>'],
        ['terms-conditions', 'Terms & Conditions', '<h2>Terms & Conditions</h2><p>Please read these terms carefully.</p>'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `pages` (`slug`, `title`, `content`) VALUES (?, ?, ?)");
    foreach ($pages as $page) {
        $stmt->execute($page);
    }

    echo '<div style="font-family:Arial;max-width:600px;margin:80px auto;text-align:center;">';
    echo '<h1 style="color:#b52d31;">Installation Successful!</h1>';
    echo '<p>Database and tables created successfully.</p>';
    echo '<p><strong>Admin Login:</strong><br>Username: admin<br>Password: admin123</p>';
    echo '<p><a href="index.php" style="background:#b52d31;color:#fff;padding:12px 30px;text-decoration:none;border-radius:8px;display:inline-block;margin:10px;">Visit Website</a>';
    echo '<a href="admin/index.php" style="background:#8B1A1A;color:#fff;padding:12px 30px;text-decoration:none;border-radius:8px;display:inline-block;margin:10px;">Admin Panel</a></p>';
    echo '</div>';

} catch (PDOException $e) {
    die('Installation failed: ' . $e->getMessage());
}
