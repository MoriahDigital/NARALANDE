<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([(int)$_POST['post_id']]);
    }
    header('Location: posts.php');
    exit;
}

$posts = $pdo->query("
    SELECT p.id, p.content, p.created_at, u.username 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="index.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
            <a href="users.php"><i class="fa-solid fa-users"></i> Utilisateurs</a>
            <a href="posts.php" class="is-active"><i class="fa-solid fa-newspaper"></i> Publications</a>
            <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="resources.php"><i class="fa-solid fa-toolbox"></i> Ressources</a>
            <a href="reports.php"><i class="fa-solid fa-flag"></i> Signalements</a>
            <a href="../ads.php"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <div class="admin-heading"><div><span class="eyebrow">Modération</span><h1>Gestion des publications</h1><p>Gardez un fil d’actualité utile, respectueux et vivant.</p></div></div>
        
        <div class="admin-table-wrap"><table class="admin-table">
            <thead>
                <tr style="background: var(--color-bg); text-align: left;">
                    <th>ID</th><th>Auteur</th><th>Contenu</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($posts as $post): ?>
                <tr>
                    <td><?= $post['id'] ?></td>
                    <td>@<?= htmlspecialchars($post['username']) ?></td>
                    <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($post['content']) ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette publication ?');">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger admin-action">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
