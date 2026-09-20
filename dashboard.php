<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Handle Post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    $image = null;

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/posts/';
        $tmp_name = $_FILES['post_image']['tmp_name'];
        $name = basename($_FILES['post_image']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $new_name = 'post_' . $user_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                $image = $new_name;
            }
        }
    }

    if (!empty($content) || $image) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $content, $image]);
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch posts (only self and accepted friends)
$posts_query = "
    SELECT p.*, u.first_name, u.last_name, u.username, u.profile_photo,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id = ? OR p.user_id IN (
        SELECT following_id FROM follows WHERE follower_id = ? AND status = 'accepted'
        UNION
        SELECT follower_id FROM follows WHERE following_id = ? AND status = 'accepted'
    )
    ORDER BY p.created_at DESC 
    LIMIT 50
";
$posts_stmt = $pdo->prepare($posts_query);
$posts_stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$posts = $posts_stmt->fetchAll();

// Fetch comments
$comments_stmt = $pdo->query("
    SELECT c.*, u.first_name, u.last_name, u.profile_photo 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at ASC
");
$all_comments = $comments_stmt->fetchAll();
// Group comments by post
$comments_by_post = [];
foreach($all_comments as $c) {
    $comments_by_post[$c['post_id']][] = $c;
}

// Fetch Friend Suggestions
$suggestions_query = "
    SELECT u.id, u.first_name, u.last_name, u.username, u.profile_photo
    FROM users u
    WHERE u.id != ? 
    AND u.id NOT IN (
        SELECT following_id FROM follows WHERE follower_id = ?
        UNION
        SELECT follower_id FROM follows WHERE following_id = ?
    )
    ORDER BY RAND() LIMIT 4
";
$sugg_stmt = $pdo->prepare($suggestions_query);
$sugg_stmt->execute([$user_id, $user_id, $user_id]);
$suggestions = $sugg_stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main style="flex: 1; display: flex; flex-direction: column; gap: 20px;">
        
        <!-- Post creation form -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($current_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; background-color: #eee;">
                    <textarea name="content" placeholder="Que voulez-vous partager, <?= htmlspecialchars($current_user['first_name']) ?> ?" style="flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 15px; resize: none; font-family: inherit;"></textarea>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                        <label for="post_image_upload" style="cursor: pointer; color: var(--color-primary); font-weight: 500; display: flex; align-items: center; gap: 8px; padding: 5px 10px; border-radius: 20px; transition: background 0.2s;">
                            <i class="fa-solid fa-image" style="font-size: 18px;"></i> Ajouter une photo
                        </label>
                        <input type="file" name="post_image" id="post_image_upload" accept="image/*" style="display: none;" onchange="document.getElementById('image-name-display').innerText = this.files[0] ? this.files[0].name : ''">
                        <span id="image-name-display" style="font-size: 13px; color: #666;"></span>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 24px;">Publier</button>
                </div>
            </form>
        </div>

        <!-- Posts Feed -->
        <div class="feed">
            <?php foreach($posts as $post): ?>
            <div id="post-<?= $post['id'] ?>" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <!-- Post Header -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <a href="<?= BASE_URL ?>users/profile.php?id=<?= $post['user_id'] ?>">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($post['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #eee;">
                    </a>
                    <div>
                        <a href="<?= BASE_URL ?>users/profile.php?id=<?= $post['user_id'] ?>" style="font-weight: 600; color: var(--color-text); text-decoration: none;">
                            <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?>
                        </a>
                        <div style="font-size: 12px; color: #666;">@<?= htmlspecialchars($post['username']) ?> • <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
                    </div>
                </div>
                
                <!-- Post Content -->
                <div style="margin-bottom: 15px;">
                    <p style="white-space: pre-wrap;"><?= htmlspecialchars($post['content']) ?></p>
                    <?php if($post['image']): ?>
                        <img src="<?= BASE_URL ?>uploads/posts/<?= htmlspecialchars($post['image']) ?>" alt="Post image" style="max-width: 100%; border-radius: 8px; margin-top: 10px;">
                    <?php endif; ?>
                </div>
                
                <!-- Like/Comment Counts -->
                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 10px;">
                    <span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?> J'aime</span>
                    <?php $comments_for_post = $comments_by_post[$post['id']] ?? []; ?>
                    <span><?= count($comments_for_post) ?> Commentaires</span>
                </div>

                <!-- Interactions -->
                <div style="display: flex; gap: 10px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 5px 0; color: #666; font-size: 13px; margin-bottom: 10px;">
                    <button onclick="toggleLike(<?= $post['id'] ?>)" id="like-btn-<?= $post['id'] ?>" style="background: none; border: none; cursor: pointer; color: <?= $post['user_liked'] ? 'var(--color-primary)' : '#666' ?>; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                        J'aime
                    </button>
                    <button onclick="document.getElementById('comment-box-<?= $post['id'] ?>').focus()" style="background: none; border: none; cursor: pointer; color: #666; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                        Commenter
                    </button>
                    <button onclick="sharePost(<?= $post['id'] ?>)" style="background: none; border: none; cursor: pointer; color: #666; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                        Partager
                    </button>
                </div>

                <!-- Comments Section -->
                <div>
                    <?php foreach($comments_for_post as $c): ?>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($c['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                        <div style="background: #f0f2f5; padding: 8px 12px; border-radius: 15px; font-size: 14px; max-width: calc(100% - 40px);">
                            <strong><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></strong>
                            <p style="margin-top: 2px;"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Add Comment -->
                    <form method="POST" action="<?= BASE_URL ?>posts/comment.php" style="display: flex; gap: 10px; margin-top: 10px;">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($current_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                        <input type="text" name="content" id="comment-box-<?= $post['id'] ?>" placeholder="Écrire un commentaire..." style="flex: 1; border: 1px solid #ddd; border-radius: 15px; padding: 8px 15px; outline: none;" required>
                        <button type="submit" style="background: none; border: none; color: var(--color-primary); font-weight: 600; cursor: pointer;">Envoyer</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($posts)): ?>
                <p style="text-align: center; color: #666; background: #fff; padding: 20px; border-radius: 8px;">Aucune publication à afficher. Ajoutez des amis pour voir leurs publications !</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Right Sidebar for Suggestions -->
    <aside style="width: 300px; display: flex; flex-direction: column; gap: 20px;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="font-size: 16px; margin-bottom: 15px; color: var(--color-text);">Suggestions d'amis</h3>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach($suggestions as $sugg): ?>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <a href="<?= BASE_URL ?>users/profile.php?id=<?= $sugg['id'] ?>">
                            <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($sugg['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #eee;">
                        </a>
                        <div style="font-size: 14px;">
                            <a href="<?= BASE_URL ?>users/profile.php?id=<?= $sugg['id'] ?>" style="font-weight: 600; color: var(--color-text); text-decoration: none; display: block;">
                                <?= htmlspecialchars($sugg['first_name'] . ' ' . $sugg['last_name']) ?>
                            </a>
                            <span style="color: #888; font-size: 12px;">@<?= htmlspecialchars($sugg['username']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($suggestions)): ?>
                    <p style="font-size: 13px; color: #888; text-align: center;">Aucune suggestion pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </aside>
</div>

<script>
function toggleLike(postId) {
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('<?= BASE_URL ?>posts/like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const btn = document.getElementById('like-btn-' + postId);
        const countSpan = document.getElementById('like-count-' + postId);
        
        if(data.status === 'liked') {
            btn.style.color = 'var(--color-primary)';
        } else {
            btn.style.color = '#666';
        }
        countSpan.innerText = data.count + " J'aime";
    })
    .catch(error => console.error('Error:', error));
}

function sharePost(postId) {
    if (confirm("Voulez-vous partager cette publication sur votre profil ?")) {
        const formData = new FormData();
        formData.append('post_id', postId);

        fetch('<?= BASE_URL ?>posts/share.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || "Erreur lors du partage.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Une erreur s'est produite.");
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
