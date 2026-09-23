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
        $comment_id = $pdo->lastInsertId();

        // Add Notification
        $getPost = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $getPost->execute([$post_id]);
        $postOwner = $getPost->fetchColumn();
        if ($postOwner && $postOwner != $user_id) {
            $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id) VALUES (?, ?, 'comment', ?)")
                ->execute([$postOwner, $user_id, $post_id]);
        }

        // If AJAX request, return JSON
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            $userStmt = $pdo->prepare("SELECT first_name, last_name, profile_photo, role FROM users WHERE id = ?");
            $userStmt->execute([$user_id]);
            $commentUser = $userStmt->fetch();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $comment_id,
                    'content' => $content,
                    'user_id' => $user_id,
                    'first_name' => $commentUser['first_name'],
                    'last_name' => $commentUser['last_name'],
                    'profile_photo' => $commentUser['profile_photo'] ?? 'default_profile.png',
                    'role' => $commentUser['role']
                ]
            ]);
            exit;
        }
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'dashboard.php'));
exit;
