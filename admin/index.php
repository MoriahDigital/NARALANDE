<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// Stats
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$postCount = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$reportCount = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$suspendedCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
$recentUsers = $pdo->query("SELECT first_name, last_name, username, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentPosts = $pdo->query("SELECT p.content, p.created_at, u.username FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="index.php" class="is-active"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
            <a href="users.php"><i class="fa-solid fa-users"></i> Utilisateurs</a>
            <a href="posts.php"><i class="fa-solid fa-newspaper"></i> Publications</a>
            <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="resources.php"><i class="fa-solid fa-toolbox"></i> Ressources</a>
            <a href="reports.php"><i class="fa-solid fa-flag"></i> Signalements <span class="menu-count"><?= $reportCount ?></span></a>
            <a href="../ads.php"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <div class="admin-heading">
            <div>
                <span class="eyebrow">Centre de contrôle</span>
                <h1>Administration Naralandé</h1>
                <p>Suivez la communauté et intervenez rapidement quand nécessaire.</p>
            </div>
            <a class="btn btn-primary" href="users.php"><i class="fa-solid fa-user-plus"></i> Voir les membres</a>
        </div>
        
        <div class="admin-stats">
            <div class="admin-stat stat-green">
                <span class="stat-icon"><i class="fa-solid fa-users"></i></span>
                <div><span>Utilisateurs</span><strong><?= $userCount ?></strong></div>
            </div>
            <div class="admin-stat stat-gold">
                <span class="stat-icon"><i class="fa-solid fa-newspaper"></i></span>
                <div><span>Publications</span><strong><?= $postCount ?></strong></div>
            </div>
            <div class="admin-stat stat-red">
                <span class="stat-icon"><i class="fa-solid fa-flag"></i></span>
                <div><span>Signalements en attente</span><strong><?= $reportCount ?></strong></div>
            </div>
            <div class="admin-stat stat-blue">
                <span class="stat-icon"><i class="fa-solid fa-user-slash"></i></span>
                <div><span>Comptes suspendus</span><strong><?= $suspendedCount ?></strong></div>
            </div>
        </div>

        <div class="admin-columns">
            <section class="admin-panel">
                <div class="panel-heading"><h2>Nouveaux membres</h2><a href="users.php">Tout voir</a></div>
                <div class="admin-list">
                    <?php foreach($recentUsers as $user): ?>
                        <div class="admin-list-item">
                            <span class="list-avatar"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></span>
                            <div><strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong><small>@<?= htmlspecialchars($user['username']) ?></small></div>
                            <time><?= date('d/m', strtotime($user['created_at'])) ?></time>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="admin-panel">
                <div class="panel-heading"><h2>Dernières publications</h2><a href="posts.php">Modérer</a></div>
                <div class="admin-list">
                    <?php foreach($recentPosts as $post): ?>
                        <div class="admin-list-item post-preview">
                            <span class="list-avatar"><i class="fa-solid fa-quote-left"></i></span>
                            <div><strong>@<?= htmlspecialchars($post['username']) ?></strong><small><?= htmlspecialchars(mb_strimwidth($post['content'] ?? '', 0, 58, '...')) ?></small></div>
                            <time><?= date('d/m', strtotime($post['created_at'])) ?></time>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
