<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = (int) $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Check if like exists
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    
    if ($stmt->fetch()) {
        // Unlike
        $del = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $del->execute([$user_id, $post_id]);
        $status = 'unliked';
    } else {
        // Like
        $ins = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $ins->execute([$user_id, $post_id]);
        $status = 'liked';

        // Add Notification
        $getPost = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $getPost->execute([$post_id]);
        $postOwner = $getPost->fetchColumn();
        if ($postOwner && $postOwner != $user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id) VALUES (?, ?, 'like', ?)")
                ->execute([$postOwner, $user_id, $post_id]);
        }
    }

    // Get new count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $countStmt->execute([$post_id]);
    $count = $countStmt->fetch()['count'];

    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'count' => $count]);
    exit;
}
header('HTTP/1.1 400 Bad Request');
