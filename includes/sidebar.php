<?php if(isLoggedIn()): ?>
<?php
    $current_page = basename($_SERVER['PHP_SELF']);
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
<aside class="sidebar">
    <div class="sidebar-menu">
        <div class="sidebar-section-label">Réseau</div>
        <a href="<?= BASE_URL ?>dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-newspaper"></i> Fil d'actualité
        </a>
        <a href="<?= BASE_URL ?>friends.php" class="<?= $current_page === 'friends.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-user-group"></i> Amis
        </a>
        <a href="<?= BASE_URL ?>groups.php" class="<?= $current_page === 'groups.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-people-group"></i> Groupes
        </a>
        <a href="<?= BASE_URL ?>search.php" class="<?= $current_page === 'search.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-magnifying-glass"></i> Recherche
        </a>

        <div class="sidebar-section-label">Parcours</div>
        <a href="<?= BASE_URL ?>jobs.php" class="<?= $current_page === 'jobs.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-briefcase"></i> Emplois
        </a>
        <a href="<?= BASE_URL ?>formations.php" class="<?= $current_page === 'formations.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-graduation-cap"></i> Formations
        </a>
        <a href="<?= BASE_URL ?>orientation.php" class="<?= $current_page === 'orientation.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-compass"></i> Orientation
        </a>

        <div class="sidebar-section-label">Commerce</div>
        <a href="<?= BASE_URL ?>marketplace.php" class="<?= $current_page === 'marketplace.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-store"></i> Boutique
        </a>
        <a href="<?= BASE_URL ?>ressources.php" class="<?= $current_page === 'ressources.php' ? 'is-active' : '' ?>">
            <i class="fa-solid fa-toolbox"></i> Ressources utiles
        </a>

        <?php if($is_sidebar_admin): ?>
        <div class="sidebar-section-label">Admin</div>
        <a href="<?= BASE_URL ?>admin/index.php" class="sidebar-admin-link">
            <i class="fa-solid fa-shield-halved"></i> Tableau de bord Admin
        </a>
        <?php endif; ?>
    </div>

    <!-- Ads Display Section -->
    <?php if(!empty($sidebarAds)): ?>
    <div class="sidebar-ads">
        <h4>Sponsorisé</h4>
        <?php foreach($sidebarAds as $ad): ?>
            <a href="<?= htmlspecialchars($ad['link']) ?>" target="_blank">
                <img src="<?= BASE_URL ?>uploads/ads/<?= htmlspecialchars($ad['image']) ?>" alt="<?= htmlspecialchars($ad['title']) ?>">
                <h5><?= htmlspecialchars($ad['title']) ?></h5>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</aside>
<?php endif; ?>
