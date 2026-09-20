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

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Recherche</h2>
        
        <form method="GET" action="search.php" style="display: flex; gap: 10px; margin-top: 20px; margin-bottom: 30px;">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Rechercher par nom ou pseudo..." class="form-control" style="flex: 1;" required>
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </form>
        
        <?php if ($query !== ''): ?>
            <h3>Résultats pour "<?= htmlspecialchars($query) ?>"</h3>
            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 15px;">
                <?php foreach($results as $user): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #eee; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <a href="<?= BASE_URL ?>users/profile.php?id=<?= $user['id'] ?>" style="font-weight: 600; font-size: 16px; color: var(--color-text);">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </a>
                            <div style="color: #666; font-size: 14px;">@<?= htmlspecialchars($user['username']) ?></div>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>users/profile.php?id=<?= $user['id'] ?>" class="btn btn-secondary" style="padding: 6px 15px;">Voir le profil</a>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($results)): ?>
                    <p style="color: #666;">Aucun utilisateur trouvé.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
