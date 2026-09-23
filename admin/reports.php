<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['status'])) {
    $status = in_array($_POST['status'], ['pending', 'reviewed', 'resolved'], true) ? $_POST['status'] : 'reviewed';
    $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?")->execute([$status, (int)$_POST['report_id']]);
    header('Location: reports.php');
    exit;
}

$reports = $pdo->query("SELECT r.*, reporter.username AS reporter_username, reported.username AS reported_username, p.content AS post_content FROM reports r JOIN users reporter ON reporter.id = r.reporter_id LEFT JOIN users reported ON reported.id = r.reported_user_id LEFT JOIN posts p ON p.id = r.post_id ORDER BY r.created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
    <aside class="sidebar"><div class="sidebar-menu">
        <a href="index.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="users.php"><i class="fa-solid fa-users"></i> Utilisateurs</a>
        <a href="posts.php"><i class="fa-solid fa-newspaper"></i> Publications</a>
        <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
        <a href="resources.php"><i class="fa-solid fa-toolbox"></i> Ressources</a>
        <a href="reports.php" class="is-active"><i class="fa-solid fa-flag"></i> Signalements</a>
        <a href="../ads.php"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
    </div></aside>
    <main class="admin-main">
        <div class="admin-heading"><div><span class="eyebrow">Sécurité</span><h1>Signalements</h1><p>Examinez les contenus signalés et gardez une trace de vos décisions.</p></div></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Signalé par</th><th>Motif</th><th>Contenu</th><th>Statut</th><th>Décision</th></tr></thead><tbody>
        <?php foreach ($reports as $report): ?>
            <tr><td>@<?= htmlspecialchars($report['reporter_username']) ?></td><td><?= htmlspecialchars($report['reason']) ?></td><td><?= htmlspecialchars(mb_strimwidth($report['post_content'] ?? $report['description'] ?? 'Aucun contenu', 0, 55, '...')) ?></td><td><span class="status-pill status-<?= $report['status'] === 'resolved' ? 'active' : 'suspended' ?>"><?= htmlspecialchars($report['status']) ?></span></td><td><form method="POST"><input type="hidden" name="report_id" value="<?= $report['id'] ?>"><select name="status" class="form-control" onchange="this.form.submit()"><option value="pending" <?= $report['status'] === 'pending' ? 'selected' : '' ?>>En attente</option><option value="reviewed" <?= $report['status'] === 'reviewed' ? 'selected' : '' ?>>Examiné</option><option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Résolu</option></select></form></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>