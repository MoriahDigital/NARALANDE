<nav class="navbar">
    <div class="navbar-container">
        <button type="button" id="nl-sidebar-toggle" class="nl-sidebar-toggle" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <a href="<?= BASE_URL ?>index.php" class="navbar-brand">
            <img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo" class="brand-mark">
            <span><?= SITE_NAME ?></span>
        </a>

        <?php if(isLoggedIn()): ?>
            <?php
                if(!isset($pdo)) {
                    require_once __DIR__ . '/../config/database.php';
                }
                $navStmt = $pdo->prepare("SELECT first_name, last_name, username, profile_photo, role FROM users WHERE id = ?");
                $navStmt->execute([$_SESSION['user_id']]);
                $navUser = $navStmt->fetch();
                $navPhoto = $navUser['profile_photo'] ?? 'default_profile.png';

                // Count unread notifications
                $navNotifStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                $navNotifStmt->execute([$_SESSION['user_id']]);
                $navNotifCount = (int) $navNotifStmt->fetchColumn();

                // Count unread messages
                $navMsgStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
                $navMsgStmt->execute([$_SESSION['user_id']]);
                $navMsgCount = (int) $navMsgStmt->fetchColumn();
            ?>
            <div class="nav-actions">
                <a href="<?= BASE_URL ?>messages/index.php" class="nav-icon-link" title="Messages">
                    <i class="fa-solid fa-envelope"></i>
                    <span class="nl-badge nl-badge-msg" style="<?= $navMsgCount > 0 ? '' : 'display:none' ?>"><?= $navMsgCount ?></span>
                </a>
                <a href="<?= BASE_URL ?>notifications.php" class="nav-icon-link" title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="nl-badge nl-badge-notif" style="<?= $navNotifCount > 0 ? '' : 'display:none' ?>"><?= $navNotifCount ?></span>
                </a>
                <details class="profile-menu">
                    <summary class="nav-user-link">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($navPhoto) ?>" alt="Profil" class="nav-avatar">
                        <span><?= htmlspecialchars(displayFullName($navUser)) ?></span>
                        <i class="fa-solid fa-chevron-down profile-chevron"></i>
                    </summary>
                    <div class="profile-menu-panel">
                        <a href="<?= BASE_URL ?>users/profile.php?id=<?= (int) $_SESSION['user_id'] ?>"><i class="fa-regular fa-user"></i> Mon profil</a>
                        <a href="<?= BASE_URL ?>settings.php"><i class="fa-solid fa-sliders"></i> Paramètres</a>
                        <a href="<?= BASE_URL ?>logout.php" class="profile-menu-danger"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
                    </div>
                </details>
            </div>
        <?php else: ?>
            <ul class="navbar-menu">
                <li><a href="<?= BASE_URL ?>login.php">Connexion</a></li>
                <li><a href="<?= BASE_URL ?>register.php" class="btn btn-secondary btn-small">Créer un compte</a></li>
            </ul>
        <?php endif; ?>
    </div>
</nav>
