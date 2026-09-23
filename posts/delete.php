<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int) ($_POST['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Publication introuvable.']);
    exit;
}

try {
    // Verify the post belongs to the current user (or user is admin)
    $stmt = $pdo->prepare("SELECT p.id, p.user_id, p.image, u.role FROM posts p JOIN users u ON u.id = ? WHERE p.id = ?");
    $stmt->execute([$user_id, $post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Publication introuvable.']);
        exit;
    }

    if ($post['user_id'] != $user_id && $post['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres publications.']);
        exit;
    }

    // Delete the image file if it exists
    if (!empty($post['image'])) {
        $image_path = __DIR__ . '/../uploads/posts/' . $post['image'];
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }

    // Delete the post (CASCADE will handle likes, comments, shares, notifications)
    $del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $del->execute([$post_id]);

    echo json_encode(['success' => true, 'message' => 'Publication supprimée avec succès.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
}
