<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$query = trim($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    $search = "%$query%";
    $current_uid = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, username, profile_photo 
        FROM users 
        WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
        AND id != ?
        AND id IN (
            SELECT following_id FROM follows WHERE follower_id = ? AND status = 'accepted'
            UNION
            SELECT follower_id FROM follows WHERE following_id = ? AND status = 'accepted'
        )
    ");
    $stmt->execute([$search, $search, $search, $current_uid, $current_uid, $current_uid]);
    $results = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="surface-main">
        <header class="page-heading"><div><span class="eyebrow">Explorer le réseau</span><h1><i class="fa-solid fa-magnifying-glass"></i> Recherche</h1><p>Retrouvez rapidement les membres de votre réseau.</p></div></header>
        
        <form method="GET" action="search.php" class="search-bar">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Nom, prénom ou pseudo..." class="form-control" required>
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </form>
        
        <?php if ($query !== ''): ?>
            <h3>Résultats pour "<?= htmlspecialchars($query) ?>"</h3>
            <div class="list-stack">
                <?php foreach($results as $user): ?>
                <div class="menu-list-item">
                    <div class="menu-list-user">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="menu-avatar">
                        <div>
                            <a href="<?= BASE_URL ?>users/profile.php?id=<?= $user['id'] ?>" class="menu-list-name">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </a>
                            <div class="menu-list-handle">@<?= htmlspecialchars($user['username']) ?></div>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>users/profile.php?id=<?= $user['id'] ?>" class="btn btn-secondary btn-small">Voir le profil</a>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($results)): ?>
                    <div class="empty-panel"><i class="fa-solid fa-user-slash"></i><strong>Aucun utilisateur trouvé</strong><span>Essayez un autre nom ou pseudo.</span></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
