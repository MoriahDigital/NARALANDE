<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];
$other_user_id = (int) ($_GET['id'] ?? 0);

$messageColumns = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('image', $messageColumns, true)) {
    $pdo->exec("ALTER TABLE messages ADD image VARCHAR(255) DEFAULT NULL AFTER content");
}
if (!in_array('audio', $messageColumns, true)) {
    $pdo->exec("ALTER TABLE messages ADD audio VARCHAR(255) DEFAULT NULL AFTER image");
}

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
$other_display_name = displayFullName($other_user);

// Check friendship
$friendCheck = $pdo->prepare("SELECT id FROM follows WHERE ((follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)) AND status = 'accepted'");
$friendCheck->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
$is_friend = $friendCheck->fetch();

// Send a text, photo, or voice message.
if ($is_friend && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $image = null;
    $audio = null;
    $media_dir = __DIR__ . '/../uploads/messages/';
    if (!is_dir($media_dir)) mkdir($media_dir, 0777, true);

    if (isset($_FILES['message_image']) && $_FILES['message_image']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['message_image']['name'], PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && $_FILES['message_image']['size'] <= 8 * 1024 * 1024) {
            $image = 'message_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
            if (!move_uploaded_file($_FILES['message_image']['tmp_name'], $media_dir . $image)) $image = null;
        }
    }

    if (isset($_FILES['message_audio']) && $_FILES['message_audio']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['message_audio']['name'], PATHINFO_EXTENSION));
        if (in_array($extension, ['webm', 'ogg', 'mp3', 'wav', 'm4a'], true) && $_FILES['message_audio']['size'] <= 12 * 1024 * 1024) {
            $audio = 'voice_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
            if (!move_uploaded_file($_FILES['message_audio']['tmp_name'], $media_dir . $audio)) $audio = null;
        }
    }

    if (!empty($content) || $image || $audio) {
        $ins = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, image, audio) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$user_id, $other_user_id, $content, $image, $audio]);
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

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="conversation-main">
        <div class="conversation-header">
            <a href="index.php" class="conversation-back" aria-label="Retour"><i class="fa-solid fa-arrow-left"></i></a>
            <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($other_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="menu-avatar small-avatar">
            <div><strong><?= htmlspecialchars($other_display_name) ?></strong><small>@<?= htmlspecialchars(displayUsername($other_user)) ?></small></div>
        </div>
        
        <div class="conversation-messages">
            <?php if (!$is_friend): ?>
                <div class="conversation-locked">
                    <i class="fa-solid fa-lock" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                    Vous devez être amis avec <?= htmlspecialchars($other_user['first_name']) ?> pour échanger des messages.
                </div>
            <?php else: ?>
                <?php foreach($messages as $msg): ?>
                    <?php $is_mine = ($msg['sender_id'] == $user_id); ?>
                    <div class="message-row <?= $is_mine ? 'is-mine' : '' ?>">
                            <div class="message-bubble">
                                <?php if (!empty($msg['content'])): ?>
                                    <div><?= nl2br(htmlspecialchars($msg['content'])) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($msg['image'])): ?>
                                    <img class="message-image" src="<?= BASE_URL ?>uploads/messages/<?= htmlspecialchars($msg['image']) ?>" alt="Photo envoyée">
                                <?php endif; ?>
                                <?php if (!empty($msg['audio'])): ?>
                                    <audio class="message-audio" controls preload="metadata"><source src="<?= BASE_URL ?>uploads/messages/<?= htmlspecialchars($msg['audio']) ?>"></audio>
                                <?php endif; ?>
                        </div>
                        <div class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($messages)): ?>
                    <p class="conversation-empty">Envoyez votre premier message !</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($is_friend): ?>
        <div class="conversation-composer">
            <form method="POST" action="conversation.php?id=<?= $other_user_id ?>" enctype="multipart/form-data">
                    <input type="text" name="content" class="form-control" placeholder="Écrivez un message..." autofocus autocomplete="off">
                    <label for="message_image" class="message-upload" title="Ajouter une photo"><i class="fa-solid fa-image"></i></label>
                    <input type="file" name="message_image" id="message_image" accept="image/*" hidden>
                    <input type="file" name="message_audio" id="message_audio" accept="audio/*" hidden>
                    <button type="button" id="voice-button" class="message-upload" title="Enregistrer un vocal"><i class="fa-solid fa-microphone"></i></button>
                <button type="submit" class="btn btn-primary" aria-label="Envoyer"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            <div id="media-preview" class="media-preview" hidden></div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
    // Scroll to bottom of chat
    const chatBox = document.querySelector('main > div:nth-child(2)');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

<script>
const voiceButton = document.getElementById('voice-button');
const audioInput = document.getElementById('message_audio');
const mediaPreview = document.getElementById('media-preview');
let recorder;
let recordedChunks = [];

voiceButton?.addEventListener('click', async () => {
    if (recorder?.state === 'recording') {
        recorder.stop();
        return;
    }
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        alert('Votre navigateur ne prend pas en charge les messages vocaux.');
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        recorder = new MediaRecorder(stream);
        recordedChunks = [];
        recorder.ondataavailable = event => recordedChunks.push(event.data);
        recorder.onstart = () => {
            voiceButton.classList.add('is-recording');
            voiceButton.innerHTML = '<i class="fa-solid fa-stop"></i>';
            voiceButton.title = 'Arrêter l’enregistrement';
        };
        recorder.onstop = () => {
            stream.getTracks().forEach(track => track.stop());
            const blob = new Blob(recordedChunks, { type: recorder.mimeType || 'audio/webm' });
            const extension = blob.type.includes('ogg') ? 'ogg' : 'webm';
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'vocal.' + extension, { type: blob.type }));
            audioInput.files = transfer.files;
            mediaPreview.hidden = false;
            mediaPreview.innerHTML = '<i class="fa-solid fa-microphone"></i> Vocal prêt à être envoyé';
            voiceButton.classList.remove('is-recording');
            voiceButton.innerHTML = '<i class="fa-solid fa-microphone"></i>';
            voiceButton.title = 'Enregistrer un vocal';
        };
        recorder.start();
    } catch (error) {
        alert('Autorisez le microphone pour enregistrer un vocal.');
    }
});

document.getElementById('message_image')?.addEventListener('change', event => {
    if (event.target.files[0]) {
        mediaPreview.hidden = false;
        mediaPreview.innerHTML = '<i class="fa-solid fa-image"></i> ' + event.target.files[0].name;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
