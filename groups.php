<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = (int) $_SESSION['user_id'];

$pdo->exec("CREATE TABLE IF NOT EXISTS friend_groups (
    id INT NOT NULL AUTO_INCREMENT,
    creator_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    visibility ENUM('public','private') NOT NULL DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS friend_group_members (
    id INT NOT NULL AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner','member') NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_group_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES friend_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $group_id = (int) ($_POST['group_id'] ?? 0);

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $visibility = ($_POST['visibility'] ?? 'public') === 'private' ? 'private' : 'public';
        if ($name !== '') {
            $create = $pdo->prepare("INSERT INTO friend_groups (creator_id, name, description, visibility) VALUES (?, ?, ?, ?)");
            $create->execute([$user_id, $name, $description, $visibility]);
            $new_group_id = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO friend_group_members (group_id, user_id, role) VALUES (?, ?, 'owner')")->execute([$new_group_id, $user_id]);
        }
    } elseif (in_array($action, ['join', 'leave'], true) && $group_id > 0) {
        if ($action === 'join') {
            $group = $pdo->prepare("SELECT visibility FROM friend_groups WHERE id = ?");
            $group->execute([$group_id]);
            if ($group->fetchColumn() === 'public') {
                $pdo->prepare("INSERT IGNORE INTO friend_group_members (group_id, user_id) VALUES (?, ?)")->execute([$group_id, $user_id]);
            }
        } else {
            $pdo->prepare("DELETE FROM friend_group_members WHERE group_id = ? AND user_id = ? AND role != 'owner'")->execute([$group_id, $user_id]);
        }
    }
    header('Location: groups.php');
    exit;
}

$groupsStmt = $pdo->prepare("SELECT g.*, u.first_name, u.last_name,
    (SELECT COUNT(*) FROM friend_group_members gm WHERE gm.group_id = g.id) AS member_count,
    EXISTS(SELECT 1 FROM friend_group_members mine WHERE mine.group_id = g.id AND mine.user_id = ?) AS is_member,
    EXISTS(SELECT 1 FROM friend_group_members owner WHERE owner.group_id = g.id AND owner.user_id = ? AND owner.role = 'owner') AS is_owner
    FROM friend_groups g JOIN users u ON u.id = g.creator_id
    WHERE g.visibility = 'public' OR g.creator_id = ? OR EXISTS(SELECT 1 FROM friend_group_members visible WHERE visible.group_id = g.id AND visible.user_id = ?)
    ORDER BY is_member DESC, g.created_at DESC");
$groupsStmt->execute([$user_id, $user_id, $user_id, $user_id]);
$groups = $groupsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="surface-main groups-main">
        <header class="page-heading">
            <div><span class="eyebrow">Cercles de proximité</span><h1><i class="fa-solid fa-user-group"></i> Groupes d'amis</h1><p>Créez des espaces autour d'un projet, d'une passion ou d'une ambition commune.</p></div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('group-create').hidden = false"><i class="fa-solid fa-plus"></i> Créer un groupe</button>
        </header>

        <section class="group-create-panel" id="group-create" hidden>
            <div class="group-panel-heading"><div><span class="section-kicker">Nouveau cercle</span><h2>Donner une maison à vos idées</h2></div><button type="button" class="icon-close" onclick="document.getElementById('group-create').hidden = true" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button></div>
            <form method="POST" class="group-form">
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label for="group-name">Nom du groupe</label><input class="form-control" id="group-name" name="name" maxlength="100" placeholder="Ex. Entrepreneurs de Conakry" required></div>
                <div class="form-group"><label for="group-description">Description</label><textarea class="form-control" id="group-description" name="description" rows="3" placeholder="Quel est le but de ce groupe ?"></textarea></div>
                <div class="group-form-footer"><select class="form-control" name="visibility" aria-label="Visibilité"><option value="public">Groupe public</option><option value="private">Groupe privé</option></select><button class="btn btn-primary" type="submit">Créer le cercle</button></div>
            </form>
        </section>

        <div class="groups-grid">
            <?php foreach ($groups as $group): ?>
                <article class="group-card <?= $group['is_member'] ? 'is-member' : '' ?>">
                    <div class="group-card-top"><span class="group-mark"><i class="fa-solid fa-people-group"></i></span><span class="group-visibility"><i class="fa-solid fa-<?= $group['visibility'] === 'private' ? 'lock' : 'globe' ?>"></i> <?= $group['visibility'] === 'private' ? 'Privé' : 'Public' ?></span></div>
                    <h2><?= htmlspecialchars($group['name']) ?></h2>
                    <p><?= htmlspecialchars($group['description'] ?: 'Un cercle pour échanger, apprendre et avancer ensemble.') ?></p>
                    <div class="group-meta"><span><i class="fa-solid fa-users"></i> <?= (int) $group['member_count'] ?> membre<?= $group['member_count'] > 1 ? 's' : '' ?></span><small>par <?= htmlspecialchars($group['first_name']) ?></small></div>
                    <?php if ($group['is_member']): ?><form method="POST"><input type="hidden" name="action" value="leave"><input type="hidden" name="group_id" value="<?= $group['id'] ?>"><button class="btn btn-secondary group-action" type="submit" <?= $group['is_owner'] ? 'disabled title="Le propriétaire reste dans son groupe"' : '' ?>><i class="fa-solid fa-check"></i> <?= $group['is_owner'] ? 'Propriétaire' : 'Membre' ?></button></form><?php elseif ($group['visibility'] === 'public'): ?><form method="POST"><input type="hidden" name="action" value="join"><input type="hidden" name="group_id" value="<?= $group['id'] ?>"><button class="btn btn-primary group-action" type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Rejoindre</button></form><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$groups): ?><div class="empty-panel"><i class="fa-solid fa-people-group"></i><strong>Aucun groupe pour le moment</strong><span>Créez le premier cercle de votre communauté.</span></div><?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
