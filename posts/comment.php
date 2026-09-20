<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['content'])) {
    $post_id = (int) $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $content = trim($_POST['content']);

    if (!empty($content)) {
        $ins = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
        $ins->execute([$user_id, $post_id, $content]);

        // Add Notification
        $getPost = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $getPost->execute([$post_id]);
        $postOwner = $getPost->fetchColumn();
        if ($postOwner && $postOwner != $user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id) VALUES (?, ?, 'comment', ?)")
                ->execute([$postOwner, $user_id, $post_id]);
        }
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'dashboard.php'));
exit;
