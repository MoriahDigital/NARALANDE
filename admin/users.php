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

<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="index.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
            <a href="users.php" class="is-active"><i class="fa-solid fa-users"></i> Utilisateurs</a>
            <a href="posts.php"><i class="fa-solid fa-newspaper"></i> Publications</a>
            <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="resources.php"><i class="fa-solid fa-toolbox"></i> Ressources</a>
            <a href="reports.php"><i class="fa-solid fa-flag"></i> Signalements</a>
            <a href="../ads.php"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <div class="admin-heading"><div><span class="eyebrow">Communauté</span><h1>Gestion des utilisateurs</h1><p>Activez, suspendez et surveillez les comptes de Naralandé.</p></div></div>
        
        <div class="admin-table-wrap"><table class="admin-table">
            <thead>
                <tr style="background: var(--color-bg); text-align: left;">
                    <th>ID</th><th>Utilisateur</th><th>Statut</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><?= $user['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong><br>
                        <span style="font-size: 12px; color: #666;">@<?= htmlspecialchars($user['username']) ?></span>
                    </td>
                    <td>
                        <span class="status-pill status-<?= $user['status'] === 'active' ? 'active' : 'suspended' ?>">
                            <?= strtoupper($user['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($user['role'] !== 'admin'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <?php if($user['status'] == 'active'): ?>
                                    <input type="hidden" name="action" value="suspend">
                                    <button type="submit" class="btn btn-danger admin-action">Suspendre</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn btn-primary admin-action">Activer</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
