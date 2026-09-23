<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Naralandé — Le réseau communautaire qui transforme les liens en opportunités. Échangez, apprenez, trouvez des missions et développez votre visibilité.">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' . SITE_NAME : SITE_NAME . ' — Communauté & Opportunités' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= time() ?>">
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <!-- Cropper.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
</head>
<body class="site-page page-<?= htmlspecialchars(pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME)) ?>">
<div id="nl-toast-container" aria-live="polite" aria-atomic="false"></div>
<div id="nl-sidebar-overlay" class="nl-sidebar-overlay"></div>
