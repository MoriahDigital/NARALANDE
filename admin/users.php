<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $target_user_id = (int) $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'suspend') {
        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$target_user_id]);
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$target_user_id]);
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT id, first_name, last_name, username, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="index.php">Tableau de bord</a>
            <a href="users.php" style="font-weight: bold;">Gérer les utilisateurs</a>
            <a href="posts.php">Gérer les publications</a>
            <a href="../ads.php">Gérer les publicités</a>
        </div>
    </aside>
    
    <main style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Gestion des utilisateurs</h2>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: var(--color-bg); text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">ID</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">Utilisateur</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">Statut</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><?= $user['id'] ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong><br>
                        <span style="font-size: 12px; color: #666;">@<?= htmlspecialchars($user['username']) ?></span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <span style="color: <?= $user['status'] == 'active' ? 'green' : 'red' ?>; font-weight: bold;">
                            <?= strtoupper($user['status']) ?>
                        </span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <?php if($user['role'] !== 'admin'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <?php if($user['status'] == 'active'): ?>
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" class="btn" style="background: red; color: white; padding: 5px 10px; font-size: 12px;">Suspendre</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn" style="background: green; color: white; padding: 5px 10px; font-size: 12px;">Activer</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
