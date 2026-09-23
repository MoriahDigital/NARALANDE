<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// Mark all as read when visiting
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.first_name, u.last_name, u.profile_photo, u.role
    FROM notifications n
    JOIN users u ON n.sender_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Fetch pending friend requests
$pendingStmt = $pdo->prepare("SELECT follower_id FROM follows WHERE following_id = ? AND status = 'pending'");
$pendingStmt->execute([$user_id]);
$pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="surface-main">
        <header class="page-heading"><div><span class="eyebrow">Votre activité</span><h1><i class="fa-solid fa-bell"></i> Notifications</h1><p>Suivez les réactions, les invitations et les nouvelles de votre réseau.</p></div><span class="heading-badge"><?= count($notifications) ?> récente<?= count($notifications) > 1 ? 's' : '' ?></span></header>
        
        <div class="notification-list">
            <?php foreach($notifications as $notif): ?>
                <?php 
                    $text = "";
                    if ($notif['type'] == 'like') $text = "a aimé votre publication.";
                    elseif ($notif['type'] == 'comment') $text = "a commenté votre publication.";
                    elseif ($notif['type'] == 'follow') $text = "a commencé à vous suivre.";
                    elseif ($notif['type'] == 'new_post') {
                        $snippet = strlen($notif['message']) > 80 ? substr($notif['message'], 0, 80) . '...' : $notif['message'];
                        // Since some content start with "a publié...", let's not double up on "a publié".
                        // If it starts with "a ", we can just use it. Otherwise, prefix it.
                        if (str_starts_with(strtolower($snippet), 'a ')) {
                            $text = htmlspecialchars($snippet);
                        } else {
                            $text = 'a publié : "' . htmlspecialchars($snippet) . '"';
                        }
                    }
                    else $text = htmlspecialchars($notif['message'] ?? "");
                ?>
                <div class="notification-item <?= $notif['is_read'] ? '' : 'is-unread' ?>">
                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($notif['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="menu-avatar small-avatar">
                    <div>
                        <a href="<?= BASE_URL ?>users/profile.php?id=<?= $notif['sender_id'] ?>" class="notification-author">
                            <?= htmlspecialchars(displayFullName($notif)) ?>
                        </a>
                        <?= $text ?>
                        <?php if ($notif['type'] == 'follow' && in_array($notif['sender_id'], $pendingRequests)): ?>
                            <div class="notification-actions" id="follow-actions-<?= $notif['sender_id'] ?>">
                                <button onclick="handleFollow(<?= $notif['sender_id'] ?>, 'accept')" class="btn btn-primary btn-small">Accepter</button>
                                <button onclick="handleFollow(<?= $notif['sender_id'] ?>, 'reject')" class="btn btn-secondary btn-small">Refuser</button>
                            </div>
                        <?php endif; ?>
                        <div class="notification-time"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($notifications)): ?>
                <div class="empty-panel"><i class="fa-regular fa-bell-slash"></i><strong>Aucune notification pour le moment</strong><span>Les nouvelles activités apparaîtront ici.</span></div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function handleFollow(targetId, action) {
    fetch('<?= BASE_URL ?>users/follow.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'target_id=' + targetId + '&action=' + action
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const actionsDiv = document.getElementById('follow-actions-' + targetId);
            if(action === 'accept') {
                actionsDiv.innerHTML = '<span style="color: #22c55e; font-size: 13px; font-weight: bold;"><i class="fa-solid fa-check"></i> Demande acceptée</span>';
            } else {
                actionsDiv.innerHTML = '<span style="color: #ef4444; font-size: 13px; font-weight: bold;"><i class="fa-solid fa-times"></i> Demande refusée</span>';
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
