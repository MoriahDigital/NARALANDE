<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "--- FORMATIONS ---\n";
print_r($pdo->query("SELECT * FROM formations")->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- ARTICLES ---\n";
print_r($pdo->query("SELECT * FROM articles")->fetchAll(PDO::FETCH_ASSOC));
?>
