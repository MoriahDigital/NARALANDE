<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$user_id = $_GET['id'] ?? $_SESSION['user_id'];
$current_user_id = $_SESSION['user_id'];
$is_own_profile = ($user_id == $current_user_id);

$phoneVisibleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone_visible'")->fetch();
if (!$phoneVisibleColumn) {
    $pdo->exec("ALTER TABLE users ADD phone_visible TINYINT(1) NOT NULL DEFAULT 0 AFTER phone");
}

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
    SELECT p.*, u.first_name, u.last_name, u.username, u.profile_photo, u.role,
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

$reaction_rows = $pdo->query("SELECT post_id, mood, COUNT(*) AS total FROM likes GROUP BY post_id, mood")->fetchAll();
$reactions_by_post = [];
foreach ($reaction_rows as $reaction) {
    $reactions_by_post[$reaction['post_id']][$reaction['mood']] = (int) $reaction['total'];
}
$reaction_people_rows = $pdo->query("SELECT l.post_id, l.mood, u.id AS user_id, u.first_name, u.last_name, u.username, u.profile_photo, u.role FROM likes l JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC")->fetchAll();
$reaction_people_by_post = [];
foreach ($reaction_people_rows as $reaction) {
    $reaction_people_by_post[$reaction['post_id']][] = $reaction;
}

// Fetch comments for these posts
$comments_stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name, u.profile_photo, u.role
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
                if (strlen($data) > MAX_UPLOAD_SIZE) {
                    $error = "La photo de profil ne doit pas dépasser 5 Mo.";
                } else {
                    $new_name = 'profile_' . $user_id . '_' . time() . '.' . $type;
                    if (file_put_contents($upload_dir_profiles . $new_name, $data)) {
                        $profile_photo = $new_name;
                        $posted_profile = true;
                    }
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
                if (strlen($data) > MAX_UPLOAD_SIZE) {
                    $error = "La photo de couverture ne doit pas dépasser 5 Mo.";
                } else {
                    $new_name = 'cover_' . $user_id . '_' . time() . '.' . $type;
                    if (file_put_contents($upload_dir_covers . $new_name, $data)) {
                        $cover_photo = $new_name;
                        $posted_cover = true;
                    }
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

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    
    <main class="profile-main">
        <!-- Cover Photo -->
        <div class="profile-cover" style="background-image: url('<?= BASE_URL ?>uploads/covers/<?= htmlspecialchars($profile_user['cover_photo']) ?>');">
            <?php if($is_own_profile): ?>
                <label for="cover-upload" style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 5px; transition: background 0.3s;">
                    <i class="fa-solid fa-camera"></i> Modifier la couverture
                </label>
                <input type="file" id="cover-upload" style="display: none;" accept="image/*">
            <?php endif; ?>
        </div>
        
        <div class="profile-body">
            <!-- Profile Photo -->
            <div class="profile-photo-wrap">
                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($profile_user['profile_photo']) ?>" alt="Photo de profil" class="profile-photo">
                <?php if($is_own_profile): ?>
                    <label for="profile-upload" style="position: absolute; bottom: 10px; right: 10px; background: var(--color-primary); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 0.2s;">
                        <i class="fa-solid fa-camera" style="font-size: 18px;"></i>
                    </label>
                    <input type="file" id="profile-upload" style="display: none;" accept="image/*">
                <?php endif; ?>
            </div>
            
            <div class="profile-identity-row">
                <div>
                    <span class="eyebrow">Profil membre</span>
                    <h2 class="profile-name"><?= htmlspecialchars(displayFullName($profile_user)) ?></h2>
                    <p class="profile-handle">@<?= htmlspecialchars(displayUsername($profile_user)) ?></p>
                    
                    <?php if($is_own_profile || ($friendship && $friendship['status'] === 'accepted')): ?>
                        <p><?= nl2br(htmlspecialchars($profile_user['bio'] ?? '')) ?></p>
                        
                        <?php if($is_own_profile || !empty($profile_user['phone_visible'])): ?>
                            <?php if(!empty($profile_user['phone'])): ?>
                                <p style="color: #444; margin-top: 10px; font-size: 14px;">
                                    <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($profile_user['phone']) ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="profile-stats">
                            <span><strong id="friends-count"><?= $friendsCount ?></strong> Amis</span>
                            <span><strong><?= count($posts) ?></strong> Publications</span>
                        </div>
                    <?php else: ?>
                        <div style="background: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 8px; font-size: 14px; display: inline-block; margin-top: 10px;">
                            <i class="fa-solid fa-lock"></i> Ce compte est privé. Ajoutez <?= htmlspecialchars($profile_user['first_name']) ?> en ami pour voir plus d'informations.
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if(!$is_own_profile): ?>
                    <div class="profile-actions">
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
            
            <div class="profile-content-section">
                <?php if($is_own_profile): ?>
                    <div class="profile-section-heading"><div><span class="section-kicker">Personnalisation</span><h3>Modifier mon profil</h3></div><i class="fa-solid fa-pen-to-square"></i></div>
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
                            <input type="checkbox" name="phone_visible" id="phone_visible" value="1" <?= !empty($profile_user['phone_visible']) ? 'checked' : '' ?>>
                            <label for="phone_visible" style="margin-bottom: 0; font-weight: 400;">Afficher mon numéro aux autres membres</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Enregistrer les modifications</button>
                    </form>
                <?php endif; ?>

                <?php if($is_own_profile || ($friendship && $friendship['status'] === 'accepted')): ?>
                    <div class="profile-posts-section">
                        <div class="profile-section-heading"><div><span class="section-kicker">Activité</span><h3>Publications de <?= htmlspecialchars($profile_user['first_name']) ?></h3></div><i class="fa-solid fa-newspaper"></i></div>
                        
                        <div class="feed">
                            <?php foreach($posts as $post): ?>
                            <div id="post-<?= $post['id'] ?>" class="profile-post-card">
                                <!-- Post Header -->
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($post['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #eee;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: var(--color-text);">
                                            <?= htmlspecialchars(displayFullName($post)) ?>
                                        </div>
                                        <div style="font-size: 12px; color: #666;">@<?= htmlspecialchars(displayUsername($post)) ?> • <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
                                    </div>
                                    <?php if($is_own_profile): ?>
                                    <div class="post-menu-wrap">
                                        <button type="button" class="post-menu-toggle" onclick="togglePostMenu(<?= $post['id'] ?>)" aria-label="Options"><i class="fa-solid fa-ellipsis"></i></button>
                                        <div id="post-menu-<?= $post['id'] ?>" class="post-menu-dropdown" hidden>
                                            <button type="button" onclick="deletePost(<?= $post['id'] ?>)"><i class="fa-solid fa-trash-can"></i> Supprimer</button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Post Content -->
                                <div style="margin-bottom: 15px;">
                                    <p style="white-space: pre-wrap;"><?= htmlspecialchars($post['content']) ?></p>
                                    <?php if($post['image']): ?>
                                        <img src="<?= BASE_URL ?>uploads/posts/<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="profile-post-image" onclick="openLightbox(this.src)">
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Like/Comment Counts -->
                                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #666; margin-bottom: 10px;">
                                    <?php $comments_for_post = $comments_by_post[$post['id']] ?? []; ?>
                                    <button type="button" class="count-link" id="like-count-<?= $post['id'] ?>" onclick="showPostPeople('Réactions', <?= htmlspecialchars(json_encode($reaction_people_by_post[$post['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>)"><?= $post['like_count'] ?> J'aime</button>
                                    <button type="button" class="count-link" onclick="showPostPeople('Commentaires', <?= htmlspecialchars(json_encode($comments_for_post), ENT_QUOTES, 'UTF-8') ?>)"><?= count($comments_for_post) ?> Commentaires</button>
                                </div>
                                <div class="reaction-summary" aria-label="Réactions par humeur">
                                    <?php $reaction_icons = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'sad' => '😢', 'angry' => '😡']; ?>
                                    <?php foreach ($reaction_icons as $mood => $icon): ?>
                                        <?php if (!empty($reactions_by_post[$post['id']][$mood])): ?><span title="<?= $mood ?>"><?= $icon ?> <b><?= $reactions_by_post[$post['id']][$mood] ?></b></span><?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Interactions -->
                                <div class="feed-actions">
                                    <div class="reaction-wrap"><button type="button" onclick="toggleReactionMenu(<?= $post['id'] ?>)" id="like-btn-<?= $post['id'] ?>" class="action-btn"><span class="reaction-icon"><?= $post['user_liked'] ? '👍' : '🙂' ?></span> <span class="reaction-label"><?= $post['user_liked'] ? 'Réagi' : 'Humeur' ?></span></button><div id="reaction-menu-<?= $post['id'] ?>" class="reaction-menu" hidden><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'like')">👍</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'love')">❤️</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'haha')">😂</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'wow')">😮</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'sad')">😢</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'angry')">😡</button></div></div>
                                    <button type="button" onclick="toggleComments(<?= $post['id'] ?>)" class="action-btn"><i class="fa-solid fa-comment"></i> Commentaires</button>
                                    <button onclick="sharePost(<?= $post['id'] ?>)" style="background: none; border: none; cursor: pointer; color: #666; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                        Partager
                                    </button>
                                </div>

                                <!-- Comments Section -->
                                <div id="comments-<?= $post['id'] ?>" class="comments-area">
                                    <?php foreach($comments_for_post as $c): ?>
                                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($c['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                                        <div style="background: #f0f2f5; padding: 8px 12px; border-radius: 15px; font-size: 14px; max-width: calc(100% - 40px);">
                                            <strong><?= htmlspecialchars(displayFullName($c)) ?></strong>
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

<div id="people-modal" class="people-modal" hidden>
    <div class="people-modal-backdrop" onclick="closePeopleModal()"></div>
    <section class="people-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="people-modal-title">
        <div class="people-modal-header"><h2 id="people-modal-title">Interactions</h2><button type="button" onclick="closePeopleModal()" class="people-modal-close" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button></div>
        <div id="people-modal-list" class="people-modal-list"></div>
    </section>
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
function showPostPeople(title, people) {
    const modal = document.getElementById('people-modal');
    const list = document.getElementById('people-modal-list');
    document.getElementById('people-modal-title').textContent = title;
    if (!people.length) {
        list.innerHTML = '<div class="people-empty">Aucune interaction pour le moment.</div>';
    } else {
        const moods = { like: '👍', love: '❤️', haha: '😂', wow: '😮', sad: '😢', angry: '😡' };
        list.innerHTML = people.map(person => { const name = person.role === 'admin' ? 'Naralandé' : (person.first_name + ' ' + person.last_name); const username = person.role === 'admin' ? 'naralande_officiel' : (person.username || ''); return `<a class="people-modal-item" href="<?= BASE_URL ?>users/profile.php?id=${person.user_id}"><img src="<?= BASE_URL ?>uploads/profiles/${encodeURIComponent(person.profile_photo || 'default_profile.png')}" alt=""><span><strong>${escapePeopleHtml(name)}</strong><small>@${escapePeopleHtml(username)}</small></span>${person.mood ? `<b class="people-mood">${moods[person.mood] || '🙂'}</b>` : ''}</a>`; }).join('');
    }
    modal.hidden = false;
    document.body.classList.add('modal-open');
}
function closePeopleModal() { document.getElementById('people-modal').hidden = true; document.body.classList.remove('modal-open'); }
function escapePeopleHtml(value) { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; }

function toggleReactionMenu(postId) {
    const menu = document.getElementById('reaction-menu-' + postId);
    menu.hidden = !menu.hidden;
}

function sendReaction(postId, mood) {
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('mood', mood);

    fetch('<?= BASE_URL ?>posts/like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const btn = document.getElementById('like-btn-' + postId);
        const countSpan = document.getElementById('like-count-' + postId);
        const icon = btn.querySelector('.reaction-icon');
        const label = btn.querySelector('.reaction-label');
        const moods = { like: ['👍', 'J’aime'], love: ['❤️', 'J’adore'], haha: ['😂', 'Haha'], wow: ['😮', 'Waouh'], sad: ['😢', 'Triste'], angry: ['😡', 'En colère'] };
        
        if(data.status !== 'unliked') {
            btn.style.color = 'var(--color-primary)';
            icon.textContent = moods[mood][0];
            label.textContent = moods[mood][1];
        } else {
            btn.style.color = '#666';
            icon.textContent = '🙂';
            label.textContent = 'Humeur';
        }
        countSpan.innerText = data.count + " J'aime";
        document.getElementById('reaction-menu-' + postId).hidden = true;
    })
    .catch(error => console.error('Error:', error));
}

function toggleComments(postId) {
    const area = document.getElementById('comments-' + postId);
    area.hidden = !area.hidden;
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
function toggleReactionMenu(postId) {
    const menu = document.getElementById('reaction-menu-' + postId);
    menu.hidden = !menu.hidden;
}

function sendReaction(postId, mood) {
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('mood', mood);

    fetch('<?= BASE_URL ?>posts/like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const btn = document.getElementById('like-btn-' + postId);
        const countSpan = document.getElementById('like-count-' + postId);
        const icon = btn.querySelector('.reaction-icon');
        const label = btn.querySelector('.reaction-label');
        const moods = { like: ['👍', 'J’aime'], love: ['❤️', 'J’adore'], haha: ['😂', 'Haha'], wow: ['😮', 'Waouh'], sad: ['😢', 'Triste'], angry: ['😡', 'En colère'] };
        
        if(data.status !== 'unliked') {
            btn.style.color = 'var(--color-primary)';
            icon.textContent = moods[mood][0];
            label.textContent = moods[mood][1];
        } else {
            btn.style.color = '#666';
            icon.textContent = '🙂';
            label.textContent = 'Humeur';
        }
        countSpan.innerText = data.count + " J'aime";
        document.getElementById('reaction-menu-' + postId).hidden = true;
    })
    .catch(error => console.error('Error:', error));
}

function toggleComments(postId) {
    const area = document.getElementById('comments-' + postId);
    area.hidden = !area.hidden;
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

<div id="image-lightbox" class="lightbox-modal" onclick="if(event.target===this)closeLightbox()">
    <button type="button" class="lightbox-close" onclick="closeLightbox()" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>
    <img id="lightbox-img" src="" alt="Image en plein écran">
</div>

<script>
function openLightbox(src) {
    const modal = document.getElementById('image-lightbox');
    const img = document.getElementById('lightbox-img');
    img.src = src;
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    const modal = document.getElementById('image-lightbox');
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});

/* Post menu */
function togglePostMenu(postId) {
    document.querySelectorAll('.post-menu-dropdown').forEach(m => {
        if (m.id !== 'post-menu-' + postId) m.hidden = true;
    });
    const menu = document.getElementById('post-menu-' + postId);
    menu.hidden = !menu.hidden;
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.post-menu-wrap')) {
        document.querySelectorAll('.post-menu-dropdown').forEach(m => m.hidden = true);
    }
});

function deletePost(postId) {
    if (!confirm('Voulez-vous vraiment supprimer cette publication ? Cette action est irréversible.')) return;

    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('<?= BASE_URL ?>posts/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('post-' + postId);
            if (card) {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 300);
            }
        } else {
            alert(data.message || 'Erreur lors de la suppression.');
        }
    })
    .catch(() => alert("Une erreur s'est produite."));
}
</script>
