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

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="index.php">Tableau de bord</a>
            <a href="users.php">Gérer les utilisateurs</a>
            <a href="posts.php" style="font-weight: bold;">Gérer les publications</a>
            <a href="../ads.php">Gérer les publicités</a>
        </div>
    </aside>
    
    <main style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Gestion des publications</h2>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: var(--color-bg); text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">ID</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">Auteur</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">Contenu</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($posts as $post): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><?= $post['id'] ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">@<?= htmlspecialchars($post['username']) ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($post['content']) ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette publication ?');">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn" style="background: red; color: white; padding: 5px 10px; font-size: 12px;">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
