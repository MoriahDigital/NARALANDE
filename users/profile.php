<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_GET['id'] ?? $_SESSION['user_id'];
$current_user_id = $_SESSION['user_id'];
$is_own_profile = ($user_id == $current_user_id);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    die("Utilisateur non trouvé.");
}

// Fetch friends count
$friendsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE status = 'accepted' AND (follower_id = ? OR following_id = ?)");
$friendsStmt->execute([$user_id, $user_id]);
$friendsCount = $friendsStmt->fetch()['count'];

// Check friendship status
$friendship = null;
if (!$is_own_profile) {
    $friendCheck = $pdo->prepare("SELECT follower_id, following_id, status FROM follows WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)");
    $friendCheck->execute([$current_user_id, $user_id, $user_id, $current_user_id]);
    $friendship = $friendCheck->fetch();
}

// Fetch posts for this user
$posts_query = "
    SELECT p.*, u.first_name, u.last_name, u.username, u.profile_photo,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC 
    LIMIT 50
";
$posts_stmt = $pdo->prepare($posts_query);
$posts_stmt->execute([$current_user_id, $user_id]);
$posts = $posts_stmt->fetchAll();

// Fetch comments for these posts
$comments_stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.profile_photo 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id IN (SELECT id FROM posts WHERE user_id = ?)
    ORDER BY c.created_at ASC
");
$comments_stmt->execute([$user_id]);
$all_comments = $comments_stmt->fetchAll();
$comments_by_post = [];
foreach($all_comments as $c) {
    $comments_by_post[$c['post_id']][] = $c;
}

$success = '';
$error = '';

if ($is_own_profile && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? $profile_user['bio'];
    $phone = $_POST['phone'] ?? $profile_user['phone'];
    $phone_visible = isset($_POST['phone_visible']) ? 1 : 0;
    
    $upload_dir_profiles = __DIR__ . '/../uploads/profiles/';
    $upload_dir_covers = __DIR__ . '/../uploads/covers/';
    
    $profile_photo = $profile_user['profile_photo'];
    $cover_photo = $profile_user['cover_photo'];
    $posted_profile = false;
    $posted_cover = false;
    
    // Handle cropped base64 profile photo
    if (!empty($_POST['cropped_profile'])) {
        $data = $_POST['cropped_profile'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            if ($data !== false) {
                $new_name = 'profile_' . $user_id . '_' . time() . '.' . $type;
                if (file_put_contents($upload_dir_profiles . $new_name, $data)) {
                    $profile_photo = $new_name;
                    $posted_profile = true;
                }
            }
        }
    }
    
    // Handle cropped base64 cover photo
    if (!empty($_POST['cropped_cover'])) {
        $data = $_POST['cropped_cover'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            if ($data !== false) {
                $new_name = 'cover_' . $user_id . '_' . time() . '.' . $type;
                if (file_put_contents($upload_dir_covers . $new_name, $data)) {
                    $cover_photo = $new_name;
                    $posted_cover = true;
                }
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET bio = ?, phone = ?, phone_visible = ?, profile_photo = ?, cover_photo = ? WHERE id = ?");
    if ($stmt->execute([$bio, $phone, $phone_visible, $profile_photo, $cover_photo, $user_id])) {
        // Auto-post photo changes
        if ($posted_profile) {
            $post_img_name = 'post_' . $user_id . '_' . time() . '_p.jpg';
            copy($upload_dir_profiles . $profile_photo, __DIR__ . '/../uploads/posts/' . $post_img_name);
            $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $postStmt->execute([$user_id, "a mis à jour sa photo de profil.", $post_img_name]);
        }
        if ($posted_cover) {
            $post_img_name = 'post_' . $user_id . '_' . time() . '_c.jpg';
            copy($upload_dir_covers . $cover_photo, __DIR__ . '/../uploads/posts/' . $post_img_name);
            $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $postStmt->execute([$user_id, "a mis à jour sa photo de couverture.", $post_img_name]);
        }
        
        $success = "Profil mis à jour avec succès.";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $profile_user = $stmt->fetch();
    } else {
        $error = "Erreur lors de la mise à jour.";
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
        <!-- Cover Photo -->
        <div style="height: 200px; background-color: #ddd; background-image: url('<?= BASE_URL ?>uploads/covers/<?= htmlspecialchars($profile_user['cover_photo']) ?>'); background-size: cover; background-position: center; position: relative;">
            <?php if($is_own_profile): ?>
                <label for="cover-upload" style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 5px; transition: background 0.3s;">
                    <i class="fa-solid fa-camera"></i> Modifier la couverture
                </label>
                <input type="file" id="cover-upload" style="display: none;" accept="image/*">
            <?php endif; ?>
        </div>
        
        <div style="padding: 20px; position: relative;">
            <!-- Profile Photo -->
            <div style="position: absolute; top: -80px; left: 20px; width: 160px; height: 160px;">
                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($profile_user['profile_photo']) ?>" alt="Photo de profil" style="width: 100%; height: 100%; border-radius: 50%; border: 4px solid #fff; object-fit: cover; background-color: #eee;">
                <?php if($is_own_profile): ?>
                    <label for="profile-upload" style="position: absolute; bottom: 10px; right: 10px; background: var(--color-primary); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 0.2s;">
                        <i class="fa-solid fa-camera" style="font-size: 18px;"></i>
                    </label>
                    <input type="file" id="profile-upload" style="display: none;" accept="image/*">
                <?php endif; ?>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-left: 190px;">
                <div>
                    <h2 style="margin-bottom: 5px;"><?= htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']) ?></h2>
                    <p style="color: #666; margin-bottom: 10px;">@<?= htmlspecialchars($profile_user['username']) ?></p>
                    
                    <?php if($is_own_profile || ($friendship && $friendship['status'] === 'accepted')): ?>
                        <p><?= nl2br(htmlspecialchars($profile_user['bio'] ?? '')) ?></p>
                        
                        <?php if($is_own_profile || $profile_user['phone_visible']): ?>
                            <?php if(!empty($profile_user['phone'])): ?>
                                <p style="color: #444; margin-top: 10px; font-size: 14px;">
                                    <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($profile_user['phone']) ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 20px; margin-top: 15px; font-weight: 500;">
                            <span><span id="friends-count"><?= $friendsCount ?></span> Amis</span>
                        </div>
                    <?php else: ?>
                        <div style="background: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 8px; font-size: 14px; display: inline-block; margin-top: 10px;">
                            <i class="fa-solid fa-lock"></i> Ce compte est privé. Ajoutez <?= htmlspecialchars($profile_user['first_name']) ?> en ami pour voir plus d'informations.
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if(!$is_own_profile): ?>
                    <div style="display: flex; gap: 10px;">
                        <?php if(!$friendship): ?>
                            <button onclick="friendAction(<?= $user_id ?>, 'add')" class="btn btn-primary">Ajouter aux amis</button>
                        <?php elseif($friendship['status'] === 'pending' && $friendship['follower_id'] == $current_user_id): ?>
                            <button onclick="friendAction(<?= $user_id ?>, 'cancel')" class="btn btn-secondary">Demande envoyée (Annuler)</button>
                        <?php elseif($friendship['status'] === 'pending' && $friendship['following_id'] == $current_user_id): ?>
                            <button onclick="friendAction(<?= $user_id ?>, 'accept')" class="btn btn-primary">Accepter</button>
                            <button onclick="friendAction(<?= $user_id ?>, 'reject')" class="btn btn-secondary">Refuser</button>
                        <?php elseif($friendship['status'] === 'accepted'): ?>
                            <button onclick="friendAction(<?= $user_id ?>, 'remove')" class="btn btn-secondary">Amis (Retirer)</button>
                        <?php endif; ?>
                        
                        <a href="<?= BASE_URL ?>messages/conversation.php?id=<?= $user_id ?>" class="btn btn-secondary" style="border: 1px solid var(--color-primary-dark);">
                            Message
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <?php if($is_own_profile): ?>
                    <h3>Modifier mon profil</h3>
                    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
                    <?php if($error) echo "<div class='alert alert-error'>$error</div>"; ?>
                    
                    <form method="POST" action="profile.php" style="margin-top: 15px;">
                        <div class="form-group">
                            <label for="bio">Biographie</label>
                            <textarea name="bio" id="bio" class="form-control" rows="3"><?= htmlspecialchars($profile_user['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="phone">Téléphone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($profile_user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="phone_visible" id="phone_visible" value="1" <?= $profile_user['phone_visible'] ? 'checked' : '' ?>>
                            <label for="phone_visible" style="margin-bottom: 0; font-weight: 400;">Rendre mon numéro de téléphone public</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Enregistrer les modifications</button>
                    </form>
                <?php endif; ?>

                <?php if($is_own_profile || ($friendship && $friendship['status'] === 'accepted')): ?>
                    <div style="margin-top: 40px;">
                        <h3 style="margin-bottom: 20px;">Publications de <?= htmlspecialchars($profile_user['first_name']) ?></h3>
                        
                        <div class="feed">
                            <?php foreach($posts as $post): ?>
                            <div id="post-<?= $post['id'] ?>" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 20px;">
                                <!-- Post Header -->
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($post['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #eee;">
                                    <div>
                                        <div style="font-weight: 600; color: var(--color-text);">
                                            <?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?>
                                        </div>
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
                                        <input type="hidden" name="return_url" value="<?= BASE_URL ?>users/profile.php?id=<?= $user_id ?>">
                                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($profile_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                        <input type="text" name="content" id="comment-box-<?= $post['id'] ?>" placeholder="Écrire un commentaire..." style="flex: 1; border: 1px solid #ddd; border-radius: 15px; padding: 8px 15px; outline: none;" required>
                                        <button type="submit" style="background: none; border: none; color: var(--color-primary); font-weight: 600; cursor: pointer;">Envoyer</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if(empty($posts)): ?>
                                <p style="text-align: center; color: #666; background: #f9f9f9; padding: 20px; border-radius: 8px;">Cet utilisateur n'a pas encore publié.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="color: #888; text-align: center; margin-top: 40px; font-style: italic;">Les publications sont masquées.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Hidden forms for cropped image submissions -->
<form method="POST" action="profile.php" id="cropped-form" style="display: none;">
    <input type="hidden" name="cropped_profile" id="cropped_profile_input">
    <input type="hidden" name="cropped_cover" id="cropped_cover_input">
</form>

<!-- Modal for Cropping -->
<div id="cropper-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 20px; border-radius: 12px; width: 90%; max-width: 600px;">
        <h3 style="margin-bottom: 15px;">Ajuster l'image</h3>
        <div style="max-height: 60vh; overflow: hidden;">
            <img id="cropper-image" src="" style="max-width: 100%; display: block;">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button onclick="closeCropper()" class="btn btn-secondary">Annuler</button>
            <button onclick="saveCroppedImage()" class="btn btn-primary">Valider</button>
        </div>
    </div>
</div>

<?php if(!$is_own_profile): ?>
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

function friendAction(userId, action) {
    const formData = new FormData();
    formData.append('target_id', userId);
    formData.append('action', action);

    fetch('<?= BASE_URL ?>users/follow.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload(); // Refresh to update UI states cleanly
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
<?php else: ?>
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

    let cropper = null;
    let currentUploadType = ''; // 'profile' or 'cover'

    function openCropper(file, type) {
        currentUploadType = type;
        const reader = new FileReader();
        reader.onload = function(e) {
            const image = document.getElementById('cropper-image');
            image.src = e.target.result;
            
            document.getElementById('cropper-modal').style.display = 'flex';
            
            if (cropper) {
                cropper.destroy();
            }
            
            cropper = new Cropper(image, {
                aspectRatio: type === 'profile' ? 1 : 16/5,
                viewMode: 1,
                autoCropArea: 1,
            });
        };
        reader.readAsDataURL(file);
    }

    function closeCropper() {
        document.getElementById('cropper-modal').style.display = 'none';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        document.getElementById('profile-upload').value = '';
        document.getElementById('cover-upload').value = '';
    }

    function saveCroppedImage() {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: currentUploadType === 'profile' ? 400 : 1200,
            height: currentUploadType === 'profile' ? 400 : 375
        });
        
        const base64Image = canvas.toDataURL('image/jpeg', 0.9);
        
        if (currentUploadType === 'profile') {
            document.getElementById('cropped_profile_input').value = base64Image;
        } else {
            document.getElementById('cropped_cover_input').value = base64Image;
        }
        
        document.getElementById('cropped-form').submit();
    }

    document.getElementById('profile-upload')?.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            openCropper(e.target.files[0], 'profile');
        }
    });

    document.getElementById('cover-upload')?.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            openCropper(e.target.files[0], 'cover');
        }
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
