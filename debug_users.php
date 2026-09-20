<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "--- USERS ---\n";
print_r($pdo->query("SELECT id, role FROM users")->fetchAll(PDO::FETCH_ASSOC));
?>
