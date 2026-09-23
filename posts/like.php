<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $post_id = (int) $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $mood = $_POST['mood'] ?? 'like';
    $allowed_moods = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
    if (!in_array($mood, $allowed_moods, true)) $mood = 'like';

    $likeColumns = $pdo->query("SHOW COLUMNS FROM likes")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('mood', $likeColumns, true)) {
        $pdo->exec("ALTER TABLE likes ADD mood VARCHAR(20) NOT NULL DEFAULT 'like' AFTER post_id");
    }

    // Check if like exists
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    
    $existingLike = $stmt->fetch();
    if ($existingLike) {
        $currentMoodStmt = $pdo->prepare("SELECT mood FROM likes WHERE user_id = ? AND post_id = ?");
        $currentMoodStmt->execute([$user_id, $post_id]);
        $currentMood = $currentMoodStmt->fetchColumn() ?: 'like';
        if ($currentMood !== $mood) {
            $upd = $pdo->prepare("UPDATE likes SET mood = ? WHERE user_id = ? AND post_id = ?");
            $upd->execute([$mood, $user_id, $post_id]);
            $status = 'updated';
        } else {
            // Remove the current reaction.
            $del = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $del->execute([$user_id, $post_id]);
            $status = 'unliked';
        }
    } else {
        $ins = $pdo->prepare("INSERT INTO likes (user_id, post_id, mood) VALUES (?, ?, ?)");
        $ins->execute([$user_id, $post_id, $mood]);
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
    echo json_encode(['status' => $status, 'count' => $count, 'mood' => $mood]);
    exit;
}
header('HTTP/1.1 400 Bad Request');
