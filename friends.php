<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = (int) $_SESSION['user_id'];
$friendsStmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.username, u.profile_photo, u.role, u.bio
    FROM users u
    JOIN follows f ON ((f.follower_id = ? AND f.following_id = u.id) OR (f.following_id = ? AND f.follower_id = u.id))
    WHERE f.status = 'accepted' AND u.id != ?
    ORDER BY u.first_name, u.last_name");
$friendsStmt->execute([$user_id, $user_id, $user_id]);
$friends = $friendsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="surface-main friends-main">
        <header class="page-heading"><div><span class="eyebrow">Votre réseau</span><h1><i class="fa-solid fa-user-group"></i> Mes amis</h1><p>Retrouvez les personnes avec qui vous êtes connecté.</p></div><span class="heading-badge"><?= count($friends) ?> ami<?= count($friends) > 1 ? 's' : '' ?></span></header>
        <?php if ($friends): ?>
            <div class="friends-grid">
                <?php foreach ($friends as $friend): ?>
                    <article class="friend-card">
                        <a href="<?= BASE_URL ?>users/profile.php?id=<?= $friend['id'] ?>" class="friend-card-profile"><img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($friend['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo de <?= htmlspecialchars(displayFullName($friend)) ?>"><span class="friend-online-dot"></span></a>
                        <div class="friend-card-info"><a href="<?= BASE_URL ?>users/profile.php?id=<?= $friend['id'] ?>" class="friend-name"><?= htmlspecialchars(displayFullName($friend)) ?></a><small>@<?= htmlspecialchars(displayUsername($friend)) ?></small><p><?= htmlspecialchars($friend['bio'] ?: 'Membre de la communauté Naralandé.') ?></p></div>
                        <div class="friend-card-actions"><a href="<?= BASE_URL ?>users/profile.php?id=<?= $friend['id'] ?>" class="btn btn-secondary btn-small"><i class="fa-regular fa-user"></i> Profil</a><a href="<?= BASE_URL ?>messages/conversation.php?id=<?= $friend['id'] ?>" class="btn btn-primary btn-small" aria-label="Écrire à <?= htmlspecialchars(displayFirstName($friend)) ?>"><i class="fa-regular fa-message"></i></a></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-panel friends-empty"><i class="fa-solid fa-user-plus"></i><strong>Votre liste d’amis est vide</strong><span>Utilisez Recherche pour trouver des membres et leur envoyer une demande.</span><a href="<?= BASE_URL ?>search.php" class="btn btn-primary btn-small">Trouver des amis</a></div>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
