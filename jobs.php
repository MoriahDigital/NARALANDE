<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];

// Fetch user role
$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->execute([$user_id]);
$current_role = $roleStmt->fetchColumn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_role === 'admin') {
    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'add') {
    $title = $_POST['title'];
    $company = $_POST['company'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];
    $description = $_POST['description'];
    $image = null;

    if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/jobs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $tmp_name = $_FILES['job_image']['tmp_name'];
        $filename = basename($_FILES['job_image']['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $new_name = 'job_' . time() . '.' . $ext;
            if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                $image = $new_name;
            }
        }
    }

    if (!empty($title) && !empty($company)) {
        $stmt = $pdo->prepare("INSERT INTO jobs (user_id, title, company, location, salary, description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $company, $location, $salary, $description, $image]);
        
        // Auto-post to newsfeed
        $post_content = "a publié une nouvelle offre d'emploi : " . $title . " chez " . $company;
        $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $postStmt->execute([$user_id, $post_content]);
        
        header('Location: jobs.php?success=1');
        exit;
    }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['edit_id'];
        $title = $_POST['title'];
        $company = $_POST['company'];
        $location = $_POST['location'];
        $salary = $_POST['salary'];
        $description = $_POST['description'];
        $image = null;
        
        if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/jobs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $tmp_name = $_FILES['job_image']['tmp_name'];
            $filename = basename($_FILES['job_image']['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_name = 'job_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
            }
        }
        
        if (!empty($title) && !empty($company)) {
            if ($image) {
                $stmt = $pdo->prepare("UPDATE jobs SET title = ?, company = ?, location = ?, salary = ?, description = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $company, $location, $salary, $description, $image, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE jobs SET title = ?, company = ?, location = ?, salary = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $company, $location, $salary, $description, $id]);
            }
            header('Location: jobs.php?success_edit=1');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $current_role === 'admin') {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: jobs.php');
    exit;
}

// Fetch jobs
$stmt = $pdo->query("SELECT j.*, u.first_name, u.last_name, u.profile_photo FROM jobs j JOIN users u ON j.user_id = u.id ORDER BY j.created_at DESC");
$jobs = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: var(--color-primary-dark);"><i class="fa-solid fa-briefcase"></i> Offres d'Emploi</h2>
            <?php if($current_role === 'admin'): ?>
                <button onclick="document.getElementById('add-form').style.display='block'" class="btn btn-primary">Publier une offre</button>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Offre d'emploi publiée avec succès !</div>
        <?php endif; ?>

        <?php if($current_role === 'admin'): ?>
        <!-- Form to add job -->
        <div id="add-form" style="display: none; background: #f9fbf9; padding: 20px; border-radius: 8px; border: 1px solid #e0e6e3; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px; font-size: 18px;">Créer une offre d'emploi</h3>
            <form method="POST" action="jobs.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Titre du poste</label>
                    <input type="text" name="title" class="form-control" required placeholder="Ex: Développeur Web, Comptable...">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Nom de l'entreprise</label>
                        <input type="text" name="company" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Lieu (Ville, Pays ou Télétravail)</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Salaire proposé (Optionnel)</label>
                    <input type="text" name="salary" class="form-control" placeholder="Ex: 2000€ net, à débattre...">
                </div>
                <div class="form-group">
                    <label>Description du poste (Missions, profil recherché)</label>
                    <textarea name="description" class="form-control" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <label>Image ou Logo de l'entreprise (Optionnel)</label>
                    <input type="file" name="job_image" class="form-control" accept="image/*">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Publier l'offre</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- List Jobs -->
        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <?php foreach($jobs as $job): ?>
                <div style="border: 1px solid #eee; border-radius: 8px; padding: 20px; transition: box-shadow 0.2s;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <?php if($job['image']): ?>
                                <div style="width: 80px; height: 80px; border-radius: 8px; overflow: hidden; flex-shrink: 0; border: 1px solid #eee;">
                                    <img src="<?= BASE_URL ?>uploads/jobs/<?= htmlspecialchars($job['image']) ?>" style="width: 100%; height: 100%; object-fit: contain; background: #fff;">
                                </div>
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #f0f8f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--color-primary); font-size: 32px; flex-shrink: 0;">
                                    <i class="fa-regular fa-building"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h3 style="margin-bottom: 5px; color: var(--color-primary-dark); font-size: 18px;"><?= htmlspecialchars($job['title']) ?></h3>
                                <div style="color: #444; font-weight: 600; font-size: 15px; margin-bottom: 5px;">
                                    <i class="fa-regular fa-building"></i> <?= htmlspecialchars($job['company']) ?>
                                </div>
                                <div style="display: flex; gap: 15px; color: #666; font-size: 14px;">
                                    <?php if($job['location']): ?><span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($job['location']) ?></span><?php endif; ?>
                                    <?php if($job['salary']): ?><span><i class="fa-solid fa-money-bill-wave"></i> <?= htmlspecialchars($job['salary']) ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if($current_role === 'admin'): ?>
                                <button onclick="editJob(<?= $job['id'] ?>, '<?= htmlspecialchars(addslashes($job['title'])) ?>', '<?= htmlspecialchars(addslashes($job['company'])) ?>', '<?= htmlspecialchars(addslashes($job['location'])) ?>', '<?= htmlspecialchars(addslashes($job['salary'])) ?>', `<?= htmlspecialchars(addslashes($job['description'])) ?>`)" class="btn btn-secondary" style="padding: 6px 15px; font-size: 14px; color: #3b82f6; border-color: #3b82f6;"><i class="fa-solid fa-pen"></i> Modifier</button>
                                <a href="jobs.php?delete=<?= $job['id'] ?>" class="btn btn-secondary" style="padding: 6px 15px; font-size: 14px; color: #ef4444; border-color: #ef4444;" onclick="return confirm('Supprimer cette offre ?');"><i class="fa-solid fa-trash"></i></a>
                            <?php else: ?>
                                <button class="btn btn-primary" style="padding: 6px 15px; font-size: 14px;">Postuler</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p style="color: #555; font-size: 14px; line-height: 1.6; margin-bottom: 15px; background: #fafafa; padding: 15px; border-radius: 6px;">
                        <?= nl2br(htmlspecialchars($job['description'])) ?>
                    </p>
                    
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: #888;">
                        <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($job['profile_photo']) ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                        Offre publiée par <?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?> le <?= date('d/m/Y à H:i', strtotime($job['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($jobs)): ?>
                <p style="text-align: center; color: #888; padding: 40px;">Aucune offre d'emploi n'est disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if($current_role === 'admin'): ?>
<!-- Form to edit job -->
<div id="edit-form" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 600px; width: 100%;">
        <h3 style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;">Modifier l'offre d'emploi</h3>
        <form method="POST" action="jobs.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="edit_id" id="edit-id">
            <div class="form-group">
                <label>Titre du poste</label>
                <input type="text" name="title" id="edit-title" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Nom de l'entreprise</label>
                    <input type="text" name="company" id="edit-company" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Lieu</label>
                    <input type="text" name="location" id="edit-location" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Salaire proposé</label>
                <input type="text" name="salary" id="edit-salary" class="form-control">
            </div>
            <div class="form-group">
                <label>Description du poste</label>
                <textarea name="description" id="edit-description" class="form-control" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label>Nouvelle Image ou Logo (Optionnel)</label>
                <input type="file" name="job_image" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-form').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary" style="background: #3b82f6;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editJob(id, title, company, location, salary, description) {
    document.getElementById('edit-form').style.display = 'flex';
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-company').value = company;
    document.getElementById('edit-location').value = location;
    document.getElementById('edit-salary').value = salary;
    document.getElementById('edit-description').value = description;
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
