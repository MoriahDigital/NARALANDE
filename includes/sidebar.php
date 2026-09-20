<?php if(isLoggedIn()): ?>
<?php
    if(!isset($pdo)) {
        require_once __DIR__ . '/../config/database.php';
    }
    
    // User info
    $sidebarStmt = $pdo->prepare("SELECT first_name, last_name, profile_photo, role FROM users WHERE id = ?");
    $sidebarStmt->execute([$_SESSION['user_id']]);
    $sidebarUser = $sidebarStmt->fetch();
    $sidebarPhoto = $sidebarUser['profile_photo'] ?? 'default_profile.png';
    $is_sidebar_admin = ($sidebarUser['role'] === 'admin');

    // Ads
    $adsStmt = $pdo->query("SELECT * FROM ads WHERE status = 'active' ORDER BY RAND() LIMIT 2");
    $sidebarAds = $adsStmt->fetchAll();
?>
<aside class="sidebar" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
    <div class="sidebar-menu">

        <a href="<?= BASE_URL ?>dashboard.php">
            <i class="fa-solid fa-newspaper" style="width: 24px; color: var(--color-primary-light);"></i> Fil d'actualité
        </a>
        <a href="<?= BASE_URL ?>messages/index.php">
            <i class="fa-solid fa-envelope" style="width: 24px; color: var(--color-primary-light);"></i> Messages
        </a>
        <a href="<?= BASE_URL ?>notifications.php">
            <i class="fa-solid fa-bell" style="width: 24px; color: var(--color-primary-light);"></i> Notifications
        </a>
        <a href="<?= BASE_URL ?>search.php">
            <i class="fa-solid fa-magnifying-glass" style="width: 24px; color: var(--color-primary-light);"></i> Recherche
        </a>

        <a href="<?= BASE_URL ?>jobs.php">
            <i class="fa-solid fa-briefcase" style="width: 24px; color: var(--color-primary-light);"></i> Emplois
        </a>
        <a href="<?= BASE_URL ?>marketplace.php">
            <i class="fa-solid fa-store" style="width: 24px; color: var(--color-primary-light);"></i> Boutique
        </a>

        <a href="<?= BASE_URL ?>settings.php">
            <i class="fa-solid fa-gear" style="width: 24px; color: var(--color-primary-light);"></i> Paramètres
        </a>
        <?php if($is_sidebar_admin): ?>
        <a href="<?= BASE_URL ?>admin/index.php" style="color: #ef4444; font-weight: bold; margin-top: 10px; border-top: 1px solid #eee; padding-top: 15px;">
            <i class="fa-solid fa-shield-halved" style="width: 24px;"></i> Tableau de bord Admin
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>logout.php" style="color: #ef4444; font-weight: bold; margin-top: 10px; border-top: 1px solid #eee; padding-top: 15px;">
            <i class="fa-solid fa-right-from-bracket" style="width: 24px;"></i> Déconnexion
        </a>
    </div>

    <!-- Ads Display Section -->
    <?php if(!empty($sidebarAds)): ?>
    <div style="margin-top: 30px; padding: 15px; border-top: 1px solid #eee;">
        <h4 style="font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">Sponsorisé</h4>
        <?php foreach($sidebarAds as $ad): ?>
            <a href="<?= htmlspecialchars($ad['link']) ?>" target="_blank" style="display: block; text-decoration: none; margin-bottom: 15px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.2s;">
                <img src="<?= BASE_URL ?>uploads/ads/<?= htmlspecialchars($ad['image']) ?>" style="width: 100%; height: 120px; object-fit: cover; display: block;">
                <div style="padding: 10px;">
                    <h5 style="color: var(--color-text); font-size: 13px; margin: 0; line-height: 1.4; font-weight: 600;"><?= htmlspecialchars($ad['title']) ?></h5>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</aside>
<?php endif; ?>
