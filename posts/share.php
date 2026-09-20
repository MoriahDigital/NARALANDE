<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int) ($_POST['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['error' => 'Missing post ID']);
    exit;
}

try {
    // Check if already shared to prevent spamming the button
    $check = $pdo->prepare("SELECT id FROM shares WHERE user_id = ? AND post_id = ?");
    $check->execute([$user_id, $post_id]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà partagé cette publication.']);
        exit;
    }

    // Insert into shares table for tracking
    $ins = $pdo->prepare("INSERT INTO shares (user_id, post_id) VALUES (?, ?)");
    $ins->execute([$user_id, $post_id]);

    // Fetch original post details
    $stmt = $pdo->prepare("
        SELECT p.content, p.image, u.username, u.id as author_id
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $original_post = $stmt->fetch();

    if ($original_post) {
        if ($original_post['author_id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas partager votre propre publication.']);
            exit;
        }

        // Create the repost content
        $repost_content = "♻️ Partagé depuis @" . $original_post['username'] . "\n\n" . $original_post['content'];
        
        // Insert new post for the current user
        $new_post = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $new_post->execute([$user_id, $repost_content, $original_post['image']]);

        // Notify the original author
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, sender_id, post_id, message) VALUES (?, 'share', ?, ?, ?)");
        $notif->execute([$original_post['author_id'], $user_id, $post_id, "a partagé votre publication."]);

        echo json_encode(['success' => true, 'message' => 'Publication partagée sur votre profil !']);
    } else {
        echo json_encode(['error' => 'Post not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
