<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];

// Get unique contacts
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.profile_photo, u.username
    FROM users u
    JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
");
$stmt->execute([$user_id, $user_id, $user_id]);
$contacts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Messagerie</h2>
        
        <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
            <?php foreach($contacts as $contact): ?>
                <a href="<?= BASE_URL ?>messages/conversation.php?id=<?= $contact['id'] ?>" style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 1px solid #eee; border-radius: 8px; text-decoration: none; color: inherit;">
                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($contact['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <div style="font-weight: 600; font-size: 16px;"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></div>
                        <div style="font-size: 13px; color: #666;">@<?= htmlspecialchars($contact['username']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
            
            <?php if(empty($contacts)): ?>
                <p style="color: #666; text-align: center; padding: 20px;">Vous n'avez aucune conversation. Allez sur le profil d'un utilisateur pour lui envoyer un message.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
