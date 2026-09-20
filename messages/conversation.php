<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];
$other_user_id = (int) ($_GET['id'] ?? 0);

if (!$other_user_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$other_user_id]);
$other_user = $stmt->fetch();

if (!$other_user) {
    die("Utilisateur introuvable.");
}

// Check friendship
$friendCheck = $pdo->prepare("SELECT id FROM follows WHERE ((follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)) AND status = 'accepted'");
$friendCheck->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
$is_friend = $friendCheck->fetch();

// Send message
if ($is_friend && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        $ins = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $ins->execute([$user_id, $other_user_id, $content]);
            
            
        header("Location: conversation.php?id=$other_user_id");
        exit;
    }
}

// Mark messages as read
if ($is_friend) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
        ->execute([$other_user_id, $user_id]);
}

// Fetch messages
$msgStmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$msgStmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
$messages = $msgStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: calc(100vh - 100px);">
        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
            <a href="index.php" style="color: var(--color-primary); font-size: 24px; text-decoration: none;">&larr;</a>
            <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($other_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            <div style="font-weight: 600; font-size: 18px;"><?= htmlspecialchars($other_user['first_name'] . ' ' . $other_user['last_name']) ?></div>
        </div>
        
        <div style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: #f9f9f9;">
            <?php if (!$is_friend): ?>
                <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center; margin: auto;">
                    <i class="fa-solid fa-lock" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                    Vous devez être amis avec <?= htmlspecialchars($other_user['first_name']) ?> pour échanger des messages.
                </div>
            <?php else: ?>
                <?php foreach($messages as $msg): ?>
                    <?php $is_mine = ($msg['sender_id'] == $user_id); ?>
                    <div style="display: flex; flex-direction: column; <?= $is_mine ? 'align-items: flex-end;' : 'align-items: flex-start;' ?>">
                        <div style="max-width: 70%; padding: 10px 15px; border-radius: 20px; <?= $is_mine ? 'background: var(--color-primary); color: #fff; border-bottom-right-radius: 5px;' : 'background: #fff; border: 1px solid #eee; border-bottom-left-radius: 5px;' ?>">
                            <?= nl2br(htmlspecialchars($msg['content'])) ?>
                        </div>
                        <div style="font-size: 11px; color: #999; margin-top: 5px;"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($messages)): ?>
                    <p style="text-align: center; color: #999; margin-top: auto; margin-bottom: auto;">Envoyez votre premier message !</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($is_friend): ?>
        <div style="padding: 15px; border-top: 1px solid #eee; background: #fff;">
            <form method="POST" action="conversation.php?id=<?= $other_user_id ?>" style="display: flex; gap: 10px;">
                <input type="text" name="content" class="form-control" placeholder="Écrivez un message..." style="flex: 1; border-radius: 20px;" required autofocus autocomplete="off">
                <button type="submit" class="btn btn-primary" style="border-radius: 20px;">Envoyer</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
    // Scroll to bottom of chat
    const chatBox = document.querySelector('main > div:nth-child(2)');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
