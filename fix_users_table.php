<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_visible TINYINT(1) DEFAULT 0;");
    echo "Column 'phone_visible' added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
