<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_role = $stmt->fetchColumn();

if ($user_role !== 'admin') {
    die("Accès refusé. Vous devez être administrateur.");
}
?>
