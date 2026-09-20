<?php
// To be included in pages requiring login
require_once __DIR__ . '/../config/session.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// Check if user has accepted terms
if (isset($_SESSION['user_id'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    // Pages that do not require terms to be accepted yet
    $exempt_pages = ['terms.php', 'logout.php'];
    
    if (!in_array($current_page, $exempt_pages)) {
        // We use a small query to check the DB directly to be sure, or rely on session if we want to optimize.
        // But since this runs on every page load, doing a quick query is fine for now, or check session.
        if (!isset($_SESSION['terms_accepted'])) {
            $stmt = $pdo->prepare("SELECT terms_accepted FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $_SESSION['terms_accepted'] = (bool)$stmt->fetchColumn();
        }
        
        if (!$_SESSION['terms_accepted']) {
            header('Location: ' . BASE_URL . 'terms.php');
            exit;
        }
    }
}
?>
