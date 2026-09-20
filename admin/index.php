<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Stats
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$postCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$reportCount = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="index.php" style="font-weight: bold;">Tableau de bord</a>
            <a href="users.php">Gérer les utilisateurs</a>
            <a href="posts.php">Gérer les publications</a>
            <a href="../ads.php">Gérer les publicités</a>
        </div>
    </aside>
    
    <main style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Administration Naralandé</h2>
        
        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <div style="flex: 1; background: var(--color-bg); padding: 20px; border-radius: 8px; text-align: center;">
                <h3>Utilisateurs</h3>
                <p style="font-size: 24px; font-weight: bold; color: var(--color-primary);"><?= $userCount ?></p>
            </div>
            <div style="flex: 1; background: var(--color-bg); padding: 20px; border-radius: 8px; text-align: center;">
                <h3>Publications</h3>
                <p style="font-size: 24px; font-weight: bold; color: var(--color-primary);"><?= $postCount ?></p>
            </div>
            <div style="flex: 1; background: var(--color-bg); padding: 20px; border-radius: 8px; text-align: center;">
                <h3>Signalements en attente</h3>
                <p style="font-size: 24px; font-weight: bold; color: red;"><?= $reportCount ?></p>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
