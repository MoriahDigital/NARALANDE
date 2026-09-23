<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// Check if user is admin
$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->execute([$user_id]);
$current_role = $roleStmt->fetchColumn();

if ($current_role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Handle form submission (Add Ad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $link = $_POST['link'];
    $image = null;

    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        if (!validateUploadSize($_FILES['ad_image'])) {
            $upload_error = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/uploads/ads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $tmp_name = $_FILES['ad_image']['tmp_name'];
            $filename = basename($_FILES['ad_image']['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                $new_name = 'ad_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
            }
        }
    }

    if (!empty($title) && !empty($link) && $image) {
        $stmt = $pdo->prepare("INSERT INTO ads (title, link, image) VALUES (?, ?, ?)");
        $stmt->execute([$title, $link, $image]);
        header('Location: ads.php?success=1');
        exit;
    }
}

// Handle Edit Ad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['edit_id'];
    $title = $_POST['title'];
    $link = $_POST['link'];
    $image = null;

    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        if (!validateUploadSize($_FILES['ad_image'])) {
            $upload_error = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/uploads/ads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $tmp_name = $_FILES['ad_image']['tmp_name'];
            $filename = basename($_FILES['ad_image']['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                $new_name = 'ad_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
            }
        }
    }

    if (!empty($title) && !empty($link)) {
        if ($image) {
            $stmt = $pdo->prepare("UPDATE ads SET title = ?, link = ?, image = ? WHERE id = ?");
            $stmt->execute([$title, $link, $image, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE ads SET title = ?, link = ? WHERE id = ?");
            $stmt->execute([$title, $link, $id]);
        }
        header('Location: ads.php?success_edit=1');
        exit;
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['toggle_status'] === 'active' ? 'active' : 'inactive';
    $stmt = $pdo->prepare("UPDATE ads SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    header('Location: ads.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ads.php');
    exit;
}

// Fetch all ads
$stmt = $pdo->query("SELECT * FROM ads ORDER BY created_at DESC");
$ads = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-menu">
            <a href="<?= BASE_URL ?>admin/index.php"><i class="fa-solid fa-chart-line"></i> Tableau de bord</a>
            <a href="<?= BASE_URL ?>admin/users.php"><i class="fa-solid fa-users"></i> Utilisateurs</a>
            <a href="<?= BASE_URL ?>admin/posts.php"><i class="fa-solid fa-newspaper"></i> Publications</a>
            <a href="<?= BASE_URL ?>admin/messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            <a href="<?= BASE_URL ?>admin/resources.php"><i class="fa-solid fa-toolbox"></i> Ressources</a>
            <a href="<?= BASE_URL ?>admin/reports.php"><i class="fa-solid fa-flag"></i> Signalements</a>
            <a href="<?= BASE_URL ?>ads.php" class="is-active"><i class="fa-solid fa-bullhorn"></i> Publicités</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div><span class="eyebrow">Communication</span><h1>Gestion des publicités</h1><p class="admin-subtitle">Pilotez les campagnes visibles dans la communauté.</p></div>
            <button onclick="document.getElementById('add-form').style.display='block'" class="btn btn-primary">Créer une campagne</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Publicité créée avec succès !</div>
        <?php endif; ?>

        <!-- Form to add Ad -->
        <div id="add-form" style="display: none; background: #f9fbf9; padding: 20px; border-radius: 8px; border: 1px solid #e0e6e3; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px; font-size: 18px;">Créer une nouvelle publicité</h3>
            <form method="POST" action="ads.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Titre de la publicité (Interne)</label>
                    <input type="text" name="title" class="form-control" required placeholder="Ex: Campagne Orange Guinée">
                </div>
                <div class="form-group">
                    <label>Lien de redirection (URL)</label>
                    <input type="url" name="link" class="form-control" required placeholder="https://www.exemple.com">
                </div>
                <div class="form-group">
                    <label>Image publicitaire (Format carré ou rectangulaire)</label>
                    <input type="file" name="ad_image" class="form-control" accept="image/*" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Publier la publicité</button>
                </div>
            </form>
        </div>

        <!-- List Ads -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach($ads as $ad): ?>
                <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; position: relative; opacity: <?= $ad['status'] === 'inactive' ? '0.6' : '1' ?>;">
                    <div style="position: absolute; top: 10px; right: 10px; background: <?= $ad['status'] === 'active' ? '#22c55e' : '#ef4444' ?>; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                        <?= $ad['status'] === 'active' ? 'Active' : 'Inactive' ?>
                    </div>
                    
                    <div style="height: 150px; background: #f5f5f5;">
                        <img src="<?= BASE_URL ?>uploads/ads/<?= htmlspecialchars($ad['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <div style="padding: 15px;">
                        <h3 style="margin-bottom: 5px; font-size: 16px; color: var(--color-text);"><?= htmlspecialchars($ad['title']) ?></h3>
                        <a href="<?= htmlspecialchars($ad['link']) ?>" target="_blank" style="font-size: 13px; color: var(--color-primary); word-break: break-all; margin-bottom: 15px; display: inline-block;">
                            <?= htmlspecialchars($ad['link']) ?>
                        </a>
                        
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 10px;">
                            <?php if($ad['status'] === 'active'): ?>
                                <a href="ads.php?toggle_status=inactive&id=<?= $ad['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px;">Désactiver</a>
                            <?php else: ?>
                                <a href="ads.php?toggle_status=active&id=<?= $ad['id'] ?>" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px;">Activer</a>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #3b82f6; border: 1px solid #3b82f6;" onclick="editAd(<?= $ad['id'] ?>, '<?= htmlspecialchars(addslashes($ad['title'])) ?>', '<?= htmlspecialchars(addslashes($ad['link'])) ?>')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="ads.php?delete=<?= $ad['id'] ?>" class="btn" style="padding: 4px 10px; font-size: 12px; color: #ef4444; border: 1px solid #ef4444;" onclick="return confirm('Supprimer cette publicité ?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($ads)): ?>
                <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">
                    Aucune publicité n'a été créée pour le moment.
                </div>
            <?php endif; ?>
        </div>
        <!-- Form to edit Ad -->
        <div id="edit-form" style="display: none; background: #f9fbf9; padding: 20px; border-radius: 8px; border: 1px solid #3b82f6; margin-top: 30px;">
            <h3 style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;">Modifier la publicité</h3>
            <form method="POST" action="ads.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="edit_id" id="edit-id">
                <div class="form-group">
                    <label>Titre de la publicité (Interne)</label>
                    <input type="text" name="title" id="edit-title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Lien de redirection (URL)</label>
                    <input type="url" name="link" id="edit-link" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nouvelle Image (Laissez vide pour conserver l'actuelle)</label>
                    <input type="file" name="ad_image" class="form-control" accept="image/*">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-form').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary" style="background: #3b82f6;">Enregistrer</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function editAd(id, title, link) {
    document.getElementById('edit-form').style.display = 'block';
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-link').value = link;
    document.getElementById('edit-form').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
