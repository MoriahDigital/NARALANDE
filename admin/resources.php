<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS resources (
    id INT NOT NULL AUTO_INCREMENT,
    category VARCHAR(40) NOT NULL,
    title VARCHAR(160) NOT NULL,
    summary TEXT NOT NULL,
    content TEXT,
    link VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' && trim($_POST['title'] ?? '') !== '') {
        $pdo->prepare("INSERT INTO resources (category, title, summary, content, link) VALUES (?, ?, ?, ?, ?)")->execute([
            trim($_POST['category'] ?? 'general'), trim($_POST['title']), trim($_POST['summary'] ?? ''), trim($_POST['content'] ?? ''), trim($_POST['link'] ?? '') ?: null
        ]);
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM resources WHERE id = ?")->execute([(int) $_POST['resource_id']]);
    }
    header('Location: resources.php');
    exit;
}
$resources = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell"><aside class="sidebar"><div class="sidebar-menu">
    <a href="index.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a><a href="users.php"><i class="fa-solid fa-users"></i> Utilisateurs</a><a href="posts.php"><i class="fa-solid fa-newspaper"></i> Publications</a><a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a><a href="resources.php" class="is-active"><i class="fa-solid fa-toolbox"></i> Ressources</a><a href="reports.php"><i class="fa-solid fa-flag"></i> Signalements</a><a href="../ads.php"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
</div></aside><main class="admin-main"><div class="admin-heading"><div><span class="eyebrow">Éducation de la communauté</span><h1>Ressources utiles</h1><p>Ajoutez des fiches, liens et conseils que l’assistant pourra recommander.</p></div><span class="heading-badge"><?= count($resources) ?> ajoutée<?= count($resources) > 1 ? 's' : '' ?></span></div>
<section class="resource-admin-form"><form method="POST"><input type="hidden" name="action" value="add"><div class="admin-form-grid"><div class="form-group"><label>Catégorie</label><select name="category" class="form-control"><option value="emploi">Emploi</option><option value="formation">Formation</option><option value="financement">Financement</option><option value="securite">Sécurité</option><option value="activite">Activité</option><option value="orientation">Orientation</option></select></div><div class="form-group"><label>Titre</label><input name="title" class="form-control" required placeholder="Ex. Où trouver une bourse ?"></div><div class="form-group form-span-2"><label>Résumé</label><input name="summary" class="form-control" placeholder="Une phrase claire pour l'utilisateur"></div><div class="form-group form-span-2"><label>Contenu ou étapes</label><textarea name="content" class="form-control" rows="4" placeholder="Conseils, étapes, contacts utiles..."></textarea></div><div class="form-group form-span-2"><label>Lien externe facultatif</label><input name="link" type="url" class="form-control" placeholder="https://..."></div></div><button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus"></i> Publier la ressource</button></form></section>
<div class="admin-resource-list"><?php foreach ($resources as $resource): ?><article class="admin-resource-item"><div><span class="resource-label"><?= htmlspecialchars($resource['category']) ?></span><h2><?= htmlspecialchars($resource['title']) ?></h2><p><?= htmlspecialchars($resource['summary']) ?></p><?php if ($resource['link']): ?><a href="<?= htmlspecialchars($resource['link']) ?>" target="_blank" rel="noopener">Ouvrir le lien <i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?></div><form method="POST" onsubmit="return confirm('Supprimer cette ressource ?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="resource_id" value="<?= $resource['id'] ?>"><button class="btn btn-danger admin-action" type="submit"><i class="fa-solid fa-trash"></i></button></form></article><?php endforeach; ?><?php if (!$resources): ?><div class="empty-panel"><i class="fa-solid fa-toolbox"></i><strong>Aucune ressource admin</strong><span>Ajoutez le premier lien utile.</span></div><?php endif; ?></div></main></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
