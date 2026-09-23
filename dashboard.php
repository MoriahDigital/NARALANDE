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
        if (!validateUploadSize($_FILES['post_image'])) {
            $upload_error = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/uploads/posts/';
            $tmp_name = $_FILES['post_image']['tmp_name'];
            $name = basename($_FILES['post_image']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                $new_name = 'post_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
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
    SELECT p.*, u.first_name, u.last_name, u.username, u.profile_photo, u.role,
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

// Fetch comments
$comments_stmt = $pdo->query("
    SELECT c.*, u.first_name, u.last_name, u.profile_photo, u.role
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

<div class="page-shell">
    <div class="content-layout">
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="composer-card">
                <form method="POST" action="dashboard.php" enctype="multipart/form-data" class="composer-form">
                    <div class="composer-header">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($current_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="avatar" id="composer-avatar">
                        <textarea name="content" id="composer-textarea" class="composer-textarea" placeholder="Que voulez-vous partager, <?= htmlspecialchars($current_user['first_name']) ?> ?"></textarea>
                    </div>

                    <div class="composer-footer">
                        <div class="composer-quick-actions">
                            <label for="post_image_upload" class="composer-quick-action"><i class="fa-solid fa-image"></i> Photo</label>
                            <input type="file" name="post_image" id="post_image_upload" accept="image/*" style="display: none;" onchange="document.getElementById('image-name-display').innerText = this.files[0] ? this.files[0].name : ''">
                            <button type="button" class="composer-quick-action" data-composer-action="video"><i class="fa-solid fa-video"></i> Vidéo</button>
                            <button type="button" class="composer-quick-action" data-composer-action="poll"><i class="fa-solid fa-square-poll-vertical"></i> Sondage</button>
                            <span id="image-name-display" class="file-name"></span>
                        </div>
                        <button type="submit" class="btn btn-primary composer-submit" id="composer-submit"><i class="fa-solid fa-paper-plane"></i> Publier</button>
                    </div>
                </form>
            </div>

            <div class="feed">
                <?php foreach($posts as $post): ?>
                    <?php $comments_for_post = $comments_by_post[$post['id']] ?? []; ?>
                    <article id="post-<?= $post['id'] ?>" class="feed-card">
                        <div class="feed-header">
                            <a href="<?= BASE_URL ?>users/profile.php?id=<?= $post['user_id'] ?>">
                                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($post['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="avatar" style="width:40px;height:40px;">
                            </a>
                            <div class="feed-meta">
                                <a href="<?= BASE_URL ?>users/profile.php?id=<?= $post['user_id'] ?>" class="feed-author">
                                    <?= htmlspecialchars(displayFullName($post)) ?>
                                </a>
                                <div class="feed-timestamp">@<?= htmlspecialchars(displayUsername($post)) ?> • <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
                            </div>
                            <?php if($post['user_id'] == $user_id): ?>
                            <div class="post-menu-wrap">
                                <button type="button" class="post-menu-toggle" onclick="togglePostMenu(<?= $post['id'] ?>)" aria-label="Options"><i class="fa-solid fa-ellipsis"></i></button>
                                <div id="post-menu-<?= $post['id'] ?>" class="post-menu-dropdown" hidden>
                                    <button type="button" onclick="deletePost(<?= $post['id'] ?>)"><i class="fa-solid fa-trash-can"></i> Supprimer</button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="feed-body">
                            <p><?= htmlspecialchars($post['content']) ?></p>
                            <?php if($post['image']): ?>
                                <img src="<?= BASE_URL ?>uploads/posts/<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="feed-image" onclick="openLightbox(this.src)">
                            <?php endif; ?>
                        </div>

                        <div class="feed-stats">
                            <button type="button" class="count-link" id="like-count-<?= $post['id'] ?>" onclick="showPostPeople('Réactions', <?= htmlspecialchars(json_encode($reaction_people_by_post[$post['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>)"><?= $post['like_count'] ?> J'aime</button>
                            <button type="button" class="count-link" onclick="showPostPeople('Commentaires', <?= htmlspecialchars(json_encode($comments_for_post), ENT_QUOTES, 'UTF-8') ?>)"><?= count($comments_for_post) ?> Commentaires</button>
                        </div>
                        <div class="reaction-summary" aria-label="Réactions par humeur">
                            <?php $reaction_icons = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'sad' => '😢', 'angry' => '😡']; ?>
                            <?php foreach ($reaction_icons as $mood => $icon): ?>
                                <?php if (!empty($reactions_by_post[$post['id']][$mood])): ?><span title="<?= $mood ?>"><?= $icon ?> <b><?= $reactions_by_post[$post['id']][$mood] ?></b></span><?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="feed-actions">
                            <div class="reaction-wrap">
                                <button type="button" onclick="toggleReactionMenu(<?= $post['id'] ?>)" id="like-btn-<?= $post['id'] ?>" class="action-btn" style="color: <?= $post['user_liked'] ? 'var(--color-primary)' : '#475467' ?>;"><span class="reaction-icon"><?= $post['user_liked'] ? '👍' : '🙂' ?></span> <span class="reaction-label"><?= $post['user_liked'] ? 'Réagi' : 'Humeur' ?></span></button>
                                <div id="reaction-menu-<?= $post['id'] ?>" class="reaction-menu" hidden>
                                    <button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'like')">👍</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'love')">❤️</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'haha')">😂</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'wow')">😮</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'sad')">😢</button><button type="button" onclick="sendReaction(<?= $post['id'] ?>, 'angry')">😡</button>
                                </div>
                            </div>
                            <button type="button" onclick="toggleComments(<?= $post['id'] ?>)" class="action-btn" aria-expanded="true"><i class="fa-solid fa-comment"></i> Commentaires</button>
                            <button type="button" onclick="sharePost(<?= $post['id'] ?>)" class="action-btn">
                                <i class="fa-solid fa-share"></i> Partager
                            </button>
                        </div>

                        <div id="comments-<?= $post['id'] ?>" class="comments-area">
                        <div class="comment-list">
                            <?php foreach($comments_for_post as $c): ?>
                                <div class="comment-item">
                                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($c['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="avatar" style="width:30px;height:30px;">
                                    <div class="comment-bubble"><strong><?= htmlspecialchars(displayFullName($c)) ?></strong><p><?= nl2br(htmlspecialchars($c['content'])) ?></p></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form class="comment-form" data-post-id="<?= $post['id'] ?>" onsubmit="return submitComment(event, <?= $post['id'] ?>)">
                            <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($current_user['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="avatar" style="width:30px;height:30px;">
                            <input type="text" name="content" id="comment-box-<?= $post['id'] ?>" placeholder="Écrire un commentaire..." class="comment-input" required autocomplete="off">
                            <button type="submit" class="btn btn-primary" style="padding: 9px 16px;">Envoyer</button>
                        </form>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if(empty($posts)): ?>
                    <div class="empty-state">
                        Aucune publication à afficher pour le moment.<br>
                        Ajoutez des amis pour découvrir leurs actualités.
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <aside class="right-rail">
            <div class="suggestion-card">
                <h3 style="font-size: 16px; margin-bottom: 15px; color: var(--color-text);">Suggestions d'amis</h3>
                <div class="suggestion-list">
                    <?php foreach($suggestions as $sugg): ?>
                        <div class="suggestion-item">
                            <div class="suggestion-profile">
                                <a href="<?= BASE_URL ?>users/profile.php?id=<?= $sugg['id'] ?>">
                                    <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($sugg['profile_photo'] ?? 'default_profile.png') ?>" alt="Photo" class="avatar" style="width:40px;height:40px;">
                                </a>
                                <div>
                                    <a href="<?= BASE_URL ?>users/profile.php?id=<?= $sugg['id'] ?>" class="suggestion-name">
                                        <?= htmlspecialchars($sugg['first_name'] . ' ' . $sugg['last_name']) ?>
                                    </a>
                                    <span class="suggestion-handle">@<?= htmlspecialchars($sugg['username']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($suggestions)): ?>
                        <div class="suggestion-empty"><i class="fa-solid fa-user-plus"></i><strong>Votre réseau peut encore grandir</strong><span>Complétez votre profil et explorez la recherche pour recevoir de nouvelles suggestions.</span><a href="<?= BASE_URL ?>search.php">Découvrir des membres</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="people-modal" class="people-modal" hidden>
    <div class="people-modal-backdrop" onclick="closePeopleModal()"></div>
    <section class="people-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="people-modal-title">
        <div class="people-modal-header"><h2 id="people-modal-title">Interactions</h2><button type="button" onclick="closePeopleModal()" class="people-modal-close" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button></div>
        <div id="people-modal-list" class="people-modal-list"></div>
    </section>
</div>

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

        if (data.status !== 'unliked') {
            btn.style.color = 'var(--color-primary)';
            icon.textContent = moods[mood][0];
            label.textContent = moods[mood][1];
        } else {
            btn.style.color = '#475467';
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
    const button = area.previousElementSibling.querySelector('.action-btn:nth-child(2)');
    const hidden = area.hidden;
    area.hidden = !hidden;
    button.setAttribute('aria-expanded', String(hidden));
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

document.querySelectorAll('[data-composer-action]').forEach((button) => {
    button.addEventListener('click', () => {
        const textarea = document.querySelector('.composer-textarea');
        const action = button.dataset.composerAction;
        if (action === 'mood') textarea.value += (textarea.value ? ' ' : '') + '🙂';
        if (action === 'poll') textarea.value = (textarea.value ? textarea.value + '\n' : '') + 'Sondage : qu’en pensez-vous ?\n1. Oui\n2. Pas encore';
        if (action === 'video') alert('Le partage vidéo sera bientôt disponible. Vous pouvez déjà ajouter une photo ou un lien dans votre publication.');
        textarea.focus();
    });
});

/* Lightbox */
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

<div id="image-lightbox" class="lightbox-modal" onclick="if(event.target===this)closeLightbox()">
    <button type="button" class="lightbox-close" onclick="closeLightbox()" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>
    <img id="lightbox-img" src="" alt="Image en plein écran">
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
