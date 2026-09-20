<nav class="navbar">
    <div class="navbar-container">
        <a href="<?= BASE_URL ?>index.php" class="navbar-brand">
            <img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo" style="height: 40px; vertical-align: middle; margin-right: 10px; border-radius: 8px;">
            <?= SITE_NAME ?>
        </a>
        <?php if(isLoggedIn()): ?>
        <?php
            if(!isset($pdo)) {
                require_once __DIR__ . '/../config/database.php';
            }
            $navStmt = $pdo->prepare("SELECT first_name, last_name, profile_photo FROM users WHERE id = ?");
            $navStmt->execute([$_SESSION['user_id']]);
            $navUser = $navStmt->fetch();
            $navPhoto = $navUser['profile_photo'] ?? 'default_profile.png';
        ?>
        <div style="display: flex; align-items: center; gap: 15px; margin-left: auto;">
            <a href="<?= BASE_URL ?>users/profile.php" style="display: flex; flex-direction: column; align-items: center; gap: 0px; font-weight: 600; text-decoration: none; color: #ffffff;">
                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($navPhoto) ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #eee;"> 
                <span style="font-size: 12px; line-height: 1;"><?= htmlspecialchars($navUser['first_name'] . ' ' . $navUser['last_name']) ?></span>
            </a>
        </div>
        <?php else: ?>
        <ul class="navbar-menu">
            <li><a href="<?= BASE_URL ?>login.php">Se connecter</a></li>
            <li><a href="<?= BASE_URL ?>register.php">Créer un compte</a></li>
        </ul>
        <?php endif; ?>
    </div>
</nav>
