<?php
require_once __DIR__ . '/config/database.php';
$stmt = $pdo->query("SHOW COLUMNS FROM shares");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SHOW COLUMNS FROM posts");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
