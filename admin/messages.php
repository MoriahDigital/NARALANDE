<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

$admin_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['content'])) {
    $receiver_id = (int) $_POST['receiver_id'];
    $content = trim($_POST['content']);
    if ($receiver_id > 0 && $content !== '') {
        $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)")->execute([$admin_id, $receiver_id, $content]);
    }
    header('Location: messages.php');
    exit;
}

$messagesStmt = $pdo->prepare("SELECT m.id, m.sender_id, m.content, m.image, m.audio, m.is_read, m.created_at, u.first_name, u.last_name, u.username, u.profile_photo FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.receiver_id = ? ORDER BY m.created_at DESC");
$messagesStmt->execute([$admin_id]);
$messages = $messagesStmt->fetchAll();
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0")->execute([$admin_id]);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
    <aside class="sidebar"><div class="sidebar-menu">
        <a href="index.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
        <a href="users.php"><i class="fa-solid fa-users"></i> Utilisateurs</a>
        <a href="posts.php"><i class="fa-solid fa-newspaper"></i> Publications</a>
        <a href="messages.php" class="is-active"><i class="fa-solid fa-envelope"></i> Messages</a>
        <a href="resources.php"><i class="fa-solid fa-toolbox"></i> Ressources</a>
        <a href="reports.php"><i class="fa-solid fa-flag"></i> Signalements</a>
        <a href="../ads.php"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
    </div></aside>
    <main class="admin-main">
        <div class="admin-heading"><div><span class="eyebrow">Relation communauté</span><h1>Messages adressés à Naralandé</h1><p>Répondez aux membres directement depuis l’espace officiel.</p></div><span class="heading-badge"><?= count($messages) ?> message<?= count($messages) > 1 ? 's' : '' ?></span></div>
        <div class="admin-inbox">
            <?php foreach ($messages as $message): ?>
                <article class="admin-message <?= !$message['is_read'] ? 'is-new' : '' ?>">
                    <div class="admin-message-head"><img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($message['profile_photo'] ?? 'default_profile.png') ?>" alt=""><div><strong><?= htmlspecialchars(displayFullName($message)) ?></strong><small>@<?= htmlspecialchars(displayUsername($message)) ?> · <?= date('d/m/Y H:i', strtotime($message['created_at'])) ?></small></div><?php if (!$message['is_read']): ?><span class="new-label">Nouveau</span><?php endif; ?></div>
                    <p class="admin-message-content"><?= nl2br(htmlspecialchars($message['content'])) ?></p>
                    <form method="POST" class="admin-reply-form"><input type="hidden" name="receiver_id" value="<?= $message['sender_id'] ?>"><input class="form-control" type="text" name="content" placeholder="Répondre à <?= htmlspecialchars(displayFirstName($message)) ?>..." required><button class="btn btn-primary admin-action" type="submit"><i class="fa-solid fa-paper-plane"></i> Répondre</button></form>
                </article>
            <?php endforeach; ?>
            <?php if (!$messages): ?><div class="empty-panel"><i class="fa-regular fa-envelope-open"></i><strong>Aucun message adressé à Naralandé</strong><span>Les demandes des membres apparaîtront ici.</span></div><?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>