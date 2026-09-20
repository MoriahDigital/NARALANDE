<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// Mark all as read when visiting
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.first_name, u.last_name, u.profile_photo 
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

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2>Notifications</h2>
        
        <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 15px;">
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
                <div style="display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #eee; <?= $notif['is_read'] ? '' : 'background-color: #f0f8ff;' ?>">
                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($notif['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <a href="<?= BASE_URL ?>users/profile.php?id=<?= $notif['sender_id'] ?>" style="font-weight: 600; color: var(--color-text);">
                            <?= htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']) ?>
                        </a>
                        <?= $text ?>
                        <?php if ($notif['type'] == 'follow' && in_array($notif['sender_id'], $pendingRequests)): ?>
                            <div style="margin-top: 10px; display: flex; gap: 10px;" id="follow-actions-<?= $notif['sender_id'] ?>">
                                <button onclick="handleFollow(<?= $notif['sender_id'] ?>, 'accept')" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px;">Accepter</button>
                                <button onclick="handleFollow(<?= $notif['sender_id'] ?>, 'reject')" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px;">Refuser</button>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($notifications)): ?>
                <p style="color: #666; text-align: center; padding: 20px;">Vous n'avez aucune notification.</p>
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
