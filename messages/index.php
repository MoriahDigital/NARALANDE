<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];

// Get unique contacts
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.profile_photo, u.username, u.role
    FROM users u
    JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
");
$stmt->execute([$user_id, $user_id, $user_id]);
$contacts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="surface-main">
        <header class="page-heading"><div><span class="eyebrow">Échanges privés</span><h1><i class="fa-solid fa-comments"></i> Messagerie</h1><p>Retrouvez vos conversations et restez proche de votre réseau.</p></div><span class="heading-badge"><?= count($contacts) ?> contact<?= count($contacts) > 1 ? 's' : '' ?></span></header>
        
        <div class="list-stack">
            <?php foreach($contacts as $contact): ?>
                <a href="<?= BASE_URL ?>messages/conversation.php?id=<?= $contact['id'] ?>" class="menu-list-item">
                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($contact['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="menu-avatar">
                    <div>
                        <strong><?= htmlspecialchars(displayFullName($contact)) ?></strong>
                        <small>@<?= htmlspecialchars(displayUsername($contact)) ?></small>
                    </div>
                    <i class="fa-solid fa-chevron-right menu-arrow"></i>
                </a>
            <?php endforeach; ?>
            
            <?php if(empty($contacts)): ?>
                <div class="empty-panel"><i class="fa-regular fa-message"></i><strong>Votre messagerie est vide</strong><span>Visitez le profil d’un membre pour démarrer une conversation.</span></div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
