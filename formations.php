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
    $school_name = $_POST['school_name'];
    $location = $_POST['location'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $image = null;

    if (isset($_FILES['formation_image']) && $_FILES['formation_image']['error'] === UPLOAD_ERR_OK) {
        if (!validateUploadSize($_FILES['formation_image'])) {
            $upload_error = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/uploads/formations/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $tmp_name = $_FILES['formation_image']['tmp_name'];
            $filename = basename($_FILES['formation_image']['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                $new_name = 'form_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
            }
        }
    }

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO formations (user_id, title, school_name, location, type, description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $school_name, $location, $type, $description, $image]);
        
        // Auto-post to newsfeed
        $post_content = "a publié une nouvelle formation : " . $title . " (" . $school_name . ")";
        $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $postStmt->execute([$user_id, $post_content]);
        
        header('Location: formations.php?success=1');
        exit;
    }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['edit_id'];
        $title = $_POST['title'];
        $school_name = $_POST['school_name'];
        $location = $_POST['location'];
        $type = $_POST['type'];
        $description = $_POST['description'];
        $image = null;
        
        if (isset($_FILES['formation_image']) && $_FILES['formation_image']['error'] === UPLOAD_ERR_OK) {
            if (!validateUploadSize($_FILES['formation_image'])) {
                $upload_error = "L'image ne doit pas dépasser 5 Mo.";
            } else {
                $upload_dir = __DIR__ . '/uploads/formations/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $tmp_name = $_FILES['formation_image']['tmp_name'];
                $filename = basename($_FILES['formation_image']['name']);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                    $new_name = 'form_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $image = $new_name;
                    }
                }
            }
        }
        
        if (!empty($title)) {
            if ($image) {
                $stmt = $pdo->prepare("UPDATE formations SET title = ?, school_name = ?, location = ?, type = ?, description = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $school_name, $location, $type, $description, $image, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE formations SET title = ?, school_name = ?, location = ?, type = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $school_name, $location, $type, $description, $id]);
            }
            header('Location: formations.php?success_edit=1');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $current_role === 'admin') {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM formations WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: formations.php');
    exit;
}

// Fetch formations
$stmt = $pdo->query("SELECT f.*, u.first_name, u.last_name, u.profile_photo FROM formations f JOIN users u ON f.user_id = u.id ORDER BY f.created_at DESC");
$formations = $stmt->fetchAll();

// Mock Data
$mockData = [
    "publication_invitation" => [
        "title" => "🚀 Préparez votre avenir : Les inscriptions pour la rentrée sont ouvertes !",
        "body" => "Rejoignez notre campus d'excellence et donnez une nouvelle dimension à votre parcours académique. Nous vous offrons un cadre d'apprentissage innovant, des professeurs passionnés et des opportunités uniques pour bâtir la carrière de vos rêves. N'attendez plus, inscrivez-vous dès aujourd'hui et prenez votre avenir en main !",
        "filieres" => [
            [
                "name" => "Licence en Ingénierie Quantique Appliquée",
                "description" => "Apprenez à maîtriser les technologies de demain en explorant le calcul quantique et ses applications industrielles.",
                "duration" => "3 ans",
                "debouches" => "Ingénieur quantique, chercheur en technologies avancées"
            ],
            [
                "name" => "Master en Éco-Conception et Villes Durables",
                "description" => "Concevez les métropoles de demain avec une approche centrée sur l'écologie et l'innovation urbaine.",
                "duration" => "2 ans",
                "debouches" => "Urbaniste écologique, consultant en développement durable"
            ],
            [
                "name" => "Bachelor en Cyber-Psychologie",
                "description" => "Étudiez l'impact du numérique sur le comportement humain et la sécurité des données comportementales.",
                "duration" => "3 ans",
                "debouches" => "Cyber-psychologue, analyste en sécurité humaine"
            ]
        ]
    ]
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="surface-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div><span class="eyebrow">Catalogue éducatif</span><h1><i class="fa-solid fa-graduation-cap"></i> Formations & écoles</h1><p class="admin-subtitle">Comparez les parcours, organismes, formats et débouchés disponibles.</p></div>
            <?php if($current_role === 'admin'): ?>
                <button onclick="document.getElementById('add-form').style.display='block'" class="btn btn-primary">Proposer une formation</button>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Formation ajoutée avec succès !</div>
        <?php endif; ?>

        <?php if($current_role === 'admin'): ?>
        <!-- Form to add formation -->
        <div id="add-form" style="display: none; background: #f9fbf9; padding: 20px; border-radius: 8px; border: 1px solid #e0e6e3; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px; font-size: 18px;">Ajouter une formation ou une école</h3>
            <form method="POST" action="formations.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Titre de la formation</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Nom de l'école ou de l'organisme</label>
                        <input type="text" name="school_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Lieu (ou "En ligne")</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Type de formation</label>
                    <select name="type" class="form-control">
                        <option value="offline">Présentiel</option>
                        <option value="online">À distance (En ligne)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description (Prérequis, débouchés, durée...)</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Image d'illustration (Optionnel)</label>
                    <input type="file" name="formation_image" class="form-control" accept="image/*">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Publier</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Mock Data Section -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid var(--color-primary); margin-bottom: 20px;">
            <h2 style="color: var(--color-primary-dark); font-size: 22px; margin-bottom: 15px;">
                <?= htmlspecialchars($mockData['publication_invitation']['title']) ?>
            </h2>
            <p style="font-size: 15px; color: #444; line-height: 1.6; margin-bottom: 25px;">
                <?= htmlspecialchars($mockData['publication_invitation']['body']) ?>
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <?php foreach($mockData['publication_invitation']['filieres'] as $filiere): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px;">
                        <h3 style="color: var(--color-primary); font-size: 15px; margin-bottom: 10px;">
                            <i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars($filiere['name']) ?>
                        </h3>
                        <p style="font-size: 13px; color: #555; margin-bottom: 10px;">
                            <?= htmlspecialchars($filiere['description']) ?>
                        </p>
                        <div style="font-size: 12px; color: #64748b; display: flex; flex-direction: column; gap: 3px;">
                            <span><strong>Durée :</strong> <?= htmlspecialchars($filiere['duration']) ?></span>
                            <span><strong>Débouchés :</strong> <?= htmlspecialchars($filiere['debouches']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- List Formations from Database -->
        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <?php foreach($formations as $form): ?>
                <div style="border: 1px solid #eee; border-radius: 8px; padding: 20px; display: flex; gap: 20px; align-items: flex-start; transition: box-shadow 0.2s;">
                    <?php if($form['image']): ?>
                        <div style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; flex-shrink: 0;">
                            <img src="<?= BASE_URL ?>uploads/formations/<?= htmlspecialchars($form['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div style="width: 120px; height: 120px; background: #f0f8f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--color-primary); font-size: 36px; flex-shrink: 0;">
                            <i class="fa-solid <?= $form['type'] === 'online' ? 'fa-laptop' : 'fa-building-columns' ?>"></i>
                        </div>
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 5px; color: var(--color-text); font-size: 18px;"><?= htmlspecialchars($form['title']) ?></h3>
                        <p style="color: #555; font-weight: 500; margin-bottom: 10px;">
                            <i class="fa-solid fa-school"></i> <?= htmlspecialchars($form['school_name'] ?: 'Organisme non précisé') ?>
                            &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($form['location'] ?: 'Lieu non précisé') ?>
                        </p>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px; line-height: 1.5;">
                            <?= nl2br(htmlspecialchars($form['description'])) ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #888;">
                                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($form['profile_photo']) ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                Publié par <?= htmlspecialchars($form['first_name'] . ' ' . $form['last_name']) ?> le <?= date('d/m/Y', strtotime($form['created_at'])) ?>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span style="background: <?= $form['type'] === 'online' ? '#e0f2fe; color: #0284c7' : '#fef3c7; color: #d97706' ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                    <?= $form['type'] === 'online' ? 'À distance' : 'Présentiel' ?>
                                </span>
                                <?php if($current_role === 'admin'): ?>
                                    <button onclick="editFormation(<?= $form['id'] ?>, '<?= htmlspecialchars(addslashes($form['title'])) ?>', '<?= htmlspecialchars(addslashes($form['school_name'])) ?>', '<?= htmlspecialchars(addslashes($form['location'])) ?>', '<?= $form['type'] ?>', `<?= htmlspecialchars(addslashes($form['description'])) ?>`)" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #3b82f6; border-color: #3b82f6;"><i class="fa-solid fa-pen"></i></button>
                                    <a href="formations.php?delete=<?= $form['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #ef4444; border-color: #ef4444;" onclick="return confirm('Supprimer cette formation ?');"><i class="fa-solid fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($formations)): ?>
                <p style="text-align: center; color: #888; padding: 40px;">Aucune formation n'est disponible pour le moment.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if($current_role === 'admin'): ?>
<!-- Form to edit formation -->
<div id="edit-form" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 600px; width: 100%;">
        <h3 style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;">Modifier la formation</h3>
        <form method="POST" action="formations.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="edit_id" id="edit-id">
            <div class="form-group">
                <label>Titre de la formation</label>
                <input type="text" name="title" id="edit-title" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Nom de l'école ou de l'organisme</label>
                    <input type="text" name="school_name" id="edit-school" class="form-control">
                </div>
                <div class="form-group">
                    <label>Lieu (ou "En ligne")</label>
                    <input type="text" name="location" id="edit-location" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Type de formation</label>
                <select name="type" id="edit-type" class="form-control">
                    <option value="offline">Présentiel</option>
                    <option value="online">À distance (En ligne)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit-description" class="form-control" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>Nouvelle Image (Optionnel, laisse vide pour conserver l'actuelle)</label>
                <input type="file" name="formation_image" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-form').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary" style="background: #3b82f6;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editFormation(id, title, school, location, type, description) {
    document.getElementById('edit-form').style.display = 'flex';
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-school').value = school;
    document.getElementById('edit-location').value = location;
    document.getElementById('edit-type').value = type;
    document.getElementById('edit-description').value = description;
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
