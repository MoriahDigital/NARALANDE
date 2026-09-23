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
    $category = $_POST['category'];
    $content = $_POST['content'];

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO articles (user_id, title, category, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $category, $content]);
        
        // Auto-post to newsfeed
        $cat_label = $category === 'emploi' ? "Recherche d'emploi" : "Conseils de vie";
        $post_content = "a publié un nouvel article dans Conseils & Carrière (" . $cat_label . ") :\n\n" . $title;
        $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $postStmt->execute([$user_id, $post_content]);
        
        header('Location: advice.php?success=1');
        exit;
    }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['edit_id'];
        $title = $_POST['title'];
        $category = $_POST['category'];
        $content = $_POST['content'];
        
        if (!empty($title) && !empty($content)) {
            $stmt = $pdo->prepare("UPDATE articles SET title = ?, category = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $category, $content, $id]);
            header('Location: advice.php?success_edit=1');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $current_role === 'admin') {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: advice.php');
    exit;
}

// Fetch articles
$stmt = $pdo->query("SELECT a.*, u.first_name, u.last_name, u.profile_photo FROM articles a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
$articles = $stmt->fetchAll();

// Mock Data
$mockData = [
    "conseils_carrieres" => [
        "menu_title" => "Conseils et Carrières",
        "advice_text" => "Il est tout à fait normal de traverser des périodes de doute ou de démotivation au cours de ses études. Si vous songez à abandonner, rappelez-vous pourquoi vous avez commencé et tout le chemin que vous avez déjà parcouru. Chaque défi surmonté vous rend plus fort et vous rapproche de vos objectifs. Ne prenez pas de décision précipitée sous le coup de la fatigue ou du stress. Prenez le temps de souffler, parlez-en à vos proches, et n'hésitez surtout pas à solliciter de l'aide. Votre conseiller pédagogique, les professeurs ou les services d'accompagnement de l'école sont là pour vous écouter et vous aider à trouver des solutions adaptées. Vous n'êtes pas seul(e) dans cette épreuve, accrochez-vous !"
    ]
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="surface-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: var(--color-primary-dark);"><i class="fa-solid fa-lightbulb"></i> Conseils & Carrière</h2>
            <?php if($current_role === 'admin'): ?>
                <button onclick="document.getElementById('add-form').style.display='block'" class="btn btn-primary">Rédiger un conseil</button>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Votre article a été publié avec succès !</div>
        <?php endif; ?>

        <?php if($current_role === 'admin'): ?>
        <!-- Form to add article -->
        <div id="add-form" style="display: none; background: #f9fbf9; padding: 20px; border-radius: 8px; border: 1px solid #e0e6e3; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px; font-size: 18px;">Partager une technique ou un conseil</h3>
            <form method="POST" action="advice.php">
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Titre de l'article</label>
                        <input type="text" name="title" class="form-control" required placeholder="Ex: 5 astuces pour réussir son entretien...">
                    </div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="category" class="form-control">
                            <option value="emploi">Recherche d'emploi</option>
                            <option value="vie_pratique">Conseils de vie & Organisation</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Contenu de l'article</label>
                    <textarea name="content" class="form-control" rows="8" required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Publier</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Mock Data Section -->
        <div style="background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #f59e0b; margin-bottom: 20px;">
            <h2 style="color: #d97706; font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-life-ring"></i> <?= htmlspecialchars($mockData['conseils_carrieres']['menu_title']) ?>
            </h2>
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; color: #92400e; font-size: 15px; line-height: 1.7; font-style: italic;">
                "<?= nl2br(htmlspecialchars($mockData['conseils_carrieres']['advice_text'])) ?>"
            </div>
        </div>

        <!-- List Articles from Database -->
        <div style="display: flex; flex-direction: column; gap: 25px;">
            <?php foreach($articles as $a): ?>
                <div style="border-bottom: 1px solid #eee; padding-bottom: 20px;">
                    <span style="background: <?= $a['category'] === 'emploi' ? '#e0e7ff; color: #4338ca' : '#fce7f3; color: #be185d' ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 10px; display: inline-block;">
                        <?= $a['category'] === 'emploi' ? 'Recherche d\'emploi' : 'Conseils de vie' ?>
                    </span>
                    <h3 style="margin-bottom: 15px; color: var(--color-primary-dark); font-size: 20px;"><?= htmlspecialchars($a['title']) ?></h3>
                    
                    <div style="color: #444; font-size: 15px; line-height: 1.7; margin-bottom: 20px; white-space: pre-wrap; font-family: Georgia, serif;"><?= htmlspecialchars($a['content']) ?></div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: #888;">
                            <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($a['profile_photo']) ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                            Rédigé par <a href="<?= BASE_URL ?>users/profile.php?id=<?= $a['user_id'] ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></a> le <?= date('d/m/Y', strtotime($a['created_at'])) ?>
                        </div>
                        <?php if($current_role === 'admin'): ?>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="editArticle(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['title'])) ?>', '<?= $a['category'] ?>', `<?= htmlspecialchars(addslashes($a['content'])) ?>`)" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #3b82f6; border-color: #3b82f6;"><i class="fa-solid fa-pen"></i></button>
                                <a href="advice.php?delete=<?= $a['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #ef4444; border-color: #ef4444;" onclick="return confirm('Supprimer cet article ?');"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($articles)): ?>
                <p style="text-align: center; color: #888; padding: 40px;">Aucun article ou conseil n'a été publié pour le moment.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if($current_role === 'admin'): ?>
<!-- Form to edit article -->
<div id="edit-form" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 600px; width: 100%;">
        <h3 style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;">Modifier l'article</h3>
        <form method="POST" action="advice.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="edit_id" id="edit-id">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Titre de l'article</label>
                    <input type="text" name="title" id="edit-title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" id="edit-category" class="form-control">
                        <option value="emploi">Recherche d'emploi</option>
                        <option value="vie_pratique">Conseils de vie & Organisation</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Contenu de l'article</label>
                <textarea name="content" id="edit-content" class="form-control" rows="8" required></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-form').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary" style="background: #3b82f6;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editArticle(id, title, category, content) {
    document.getElementById('edit-form').style.display = 'flex';
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-category').value = category;
    document.getElementById('edit-content').value = content;
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
