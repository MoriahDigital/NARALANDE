<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    // 1. Create missing tables
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `formations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `school_name` varchar(255) DEFAULT NULL,
        `location` varchar(255) DEFAULT NULL,
        `type` enum('online','offline') DEFAULT 'offline',
        `description` text,
        `image` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `jobs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `company` varchar(255) NOT NULL,
        `location` varchar(255) DEFAULT NULL,
        `salary` varchar(100) DEFAULT NULL,
        `description` text NOT NULL,
        `image` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `marketplace` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `price` decimal(10,2) DEFAULT NULL,
        `currency` varchar(10) DEFAULT 'FG',
        `description` text NOT NULL,
        `image` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `ads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `link` varchar(255) NOT NULL,
        `image` varchar(255) NOT NULL,
        `status` enum('active','inactive') DEFAULT 'active',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `articles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `category` varchar(100) NOT NULL,
        `content` text NOT NULL,
        `image` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Tables created.\n";

} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}

// 2. Setup directories and copy placeholder image
$dirs = [
    __DIR__ . '/uploads/profiles',
    __DIR__ . '/uploads/posts',
    __DIR__ . '/uploads/marketplace',
    __DIR__ . '/uploads/ads',
    __DIR__ . '/uploads/formations',
    __DIR__ . '/uploads/jobs'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

$source_image = __DIR__ . '/uploads/ads/ad_promo_naralande.jpg';
$files_to_create = [
    __DIR__ . '/uploads/formations/form_1.jpg',
    __DIR__ . '/uploads/formations/form_2.jpg',
    __DIR__ . '/uploads/jobs/job_1.jpg',
    __DIR__ . '/uploads/jobs/job_2.jpg',
    __DIR__ . '/uploads/marketplace/market_1.jpg',
    __DIR__ . '/uploads/marketplace/market_2.jpg',
    __DIR__ . '/uploads/ads/ad_1.jpg',
    __DIR__ . '/uploads/posts/post_1.jpg',
    __DIR__ . '/uploads/profiles/profile_demo.jpg'
];

if (file_exists($source_image)) {
    foreach ($files_to_create as $file) {
        copy($source_image, $file);
    }
    echo "Placeholder images copied.\n";
} else {
    echo "Source image not found.\n";
}

// 3. Get admin user
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_id = $stmt->fetchColumn() ?: 1;

// 4. Insert dummy data
$pdo->exec("TRUNCATE TABLE formations; TRUNCATE TABLE jobs; TRUNCATE TABLE marketplace; TRUNCATE TABLE ads;");

$pdo->exec("INSERT INTO formations (user_id, title, school_name, location, type, description, image) VALUES 
($admin_id, 'Masterclass PHP 8', 'NARALANDE Academy', 'En Ligne', 'online', 'Une formation complète pour maitriser PHP 8.', 'form_1.jpg'),
($admin_id, 'Marketing Digital Intensif', 'Institut de Commerce', 'Conakry', 'offline', 'Apprenez à gérer des campagnes publicitaires et les réseaux sociaux.', 'form_2.jpg')");

$pdo->exec("INSERT INTO jobs (user_id, title, company, location, salary, description, image) VALUES 
($admin_id, 'Développeur Frontend React', 'Tech Innovate', 'Dakar', '1500000 FCFA', 'Nous cherchons un dev React expérimenté.', 'job_1.jpg'),
($admin_id, 'Community Manager', 'Agence Crea', 'Conakry', 'À débattre', 'Gestion des réseaux sociaux pour nos clients.', 'job_2.jpg')");

$pdo->exec("INSERT INTO marketplace (user_id, name, price, currency, description, image) VALUES 
($admin_id, 'MacBook Pro M1 2020', 8500000, 'FG', 'Très bon état, batterie 95%.', 'market_1.jpg'),
($admin_id, 'Casque Sony WH-1000XM4', 2500000, 'FG', 'Casque réducteur de bruit, avec boîte.', 'market_2.jpg')");

$pdo->exec("INSERT INTO ads (title, link, image, status) VALUES 
('Promotion Hébergement Web', 'https://example.com/promo', 'ad_1.jpg', 'active')");

$pdo->exec("INSERT INTO posts (user_id, content, image) VALUES 
($admin_id, 'Regardez ma nouvelle configuration de travail ! #dev', 'post_1.jpg')");

// Update admin profile photo
$pdo->exec("UPDATE users SET profile_photo = 'profile_demo.jpg' WHERE id = $admin_id");

echo "Done.\n";
?>
