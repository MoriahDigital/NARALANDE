<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$target_id = $_POST['target_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$target_id || !$action) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    if ($action === 'add') {
        // Check if a request already exists
        $check = $pdo->prepare("SELECT id FROM follows WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)");
        $check->execute([$current_user_id, $target_id, $target_id, $current_user_id]);
        if (!$check->fetch()) {
            $ins = $pdo->prepare("INSERT INTO follows (follower_id, following_id, status) VALUES (?, ?, 'pending')");
            $ins->execute([$current_user_id, $target_id]);
            
            // Notify target user
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, sender_id, message) VALUES (?, 'follow', ?, ?)");
            $notif->execute([$target_id, $current_user_id, "vous a envoyé une demande d'ami."]);
        }
    } 
    elseif ($action === 'cancel') {
        // Cancel my pending request
        $del = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ? AND status = 'pending'");
        $del->execute([$current_user_id, $target_id]);
    }
    elseif ($action === 'accept') {
        // Accept pending request from target_id
        $upd = $pdo->prepare("UPDATE follows SET status = 'accepted' WHERE follower_id = ? AND following_id = ? AND status = 'pending'");
        $upd->execute([$target_id, $current_user_id]);
        
        // Notify them we accepted
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, sender_id, message) VALUES (?, 'follow', ?, ?)");
        $notif->execute([$target_id, $current_user_id, "a accepté votre demande d'ami."]);
    }
    elseif ($action === 'reject') {
        // Reject pending request from target_id
        $del = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ? AND status = 'pending'");
        $del->execute([$target_id, $current_user_id]);
    }
    elseif ($action === 'remove') {
        // Remove accepted friendship
        $del = $pdo->prepare("DELETE FROM follows WHERE ((follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)) AND status = 'accepted'");
        $del->execute([$current_user_id, $target_id, $target_id, $current_user_id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
