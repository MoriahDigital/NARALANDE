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
    $name = $_POST['name'];
    $price = $_POST['price'];
    $currency = $_POST['currency'] ?? 'FG';
    $description = $_POST['description'];
    $image = null;

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        if (!validateUploadSize($_FILES['product_image'])) {
            $upload_error = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $upload_dir = __DIR__ . '/uploads/marketplace/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $tmp_name = $_FILES['product_image']['tmp_name'];
            $filename = basename($_FILES['product_image']['name']);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                $new_name = 'product_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $image = $new_name;
                }
            }
        }
    }

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO marketplace (user_id, name, price, currency, description, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $price, $currency, $description, $image]);
        
        // Auto-post to newsfeed
        $post_img_name = null;
        if ($image) {
            $post_img_name = 'post_' . $user_id . '_' . time() . '_m.jpg';
            copy(__DIR__ . '/uploads/marketplace/' . $image, __DIR__ . '/uploads/posts/' . $post_img_name);
        }
        
        $currency_symbol = $currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : 'FG');
        $post_content = "a mis en vente un article dans la Boutique : " . $name . ($price ? (" au prix de " . $price . " " . $currency_symbol) : "");
        $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $postStmt->execute([$user_id, $post_content, $post_img_name]);
        
        header('Location: marketplace.php?success=1');
        exit;
    }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['edit_id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $currency = $_POST['currency'] ?? 'FG';
        $description = $_POST['description'];
        $image = null;
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            if (!validateUploadSize($_FILES['product_image'])) {
                $upload_error = "L'image ne doit pas dépasser 5 Mo.";
            } else {
                $upload_dir = __DIR__ . '/uploads/marketplace/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $tmp_name = $_FILES['product_image']['tmp_name'];
                $filename = basename($_FILES['product_image']['name']);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                    $new_name = 'product_' . $user_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $image = $new_name;
                    }
                }
            }
        }
        
        if (!empty($name)) {
            if ($image) {
                $stmt = $pdo->prepare("UPDATE marketplace SET name = ?, price = ?, currency = ?, description = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $price, $currency, $description, $image, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE marketplace SET name = ?, price = ?, currency = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $price, $currency, $description, $id]);
            }
            header('Location: marketplace.php?success_edit=1');
            exit;
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $current_role === 'admin') {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM marketplace WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: marketplace.php');
    exit;
}

// Fetch products
$stmt = $pdo->query("SELECT m.*, u.first_name, u.last_name, u.profile_photo FROM marketplace m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC");
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="surface-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h2 style="color: var(--color-primary-dark);"><i class="fa-solid fa-store"></i> Boutique / Annonces</h2>
            <?php if($current_role === 'admin'): ?>
                <button onclick="document.getElementById('add-form').style.display='block'" class="btn btn-primary">Publier une annonce</button>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Annonce publiée avec succès !</div>
        <?php endif; ?>

        <?php if($current_role === 'admin'): ?>
        <!-- Form to add product -->
        <div id="add-form" style="display: none; background: #f9fbf9; padding: 20px; border-radius: 8px; border: 1px solid #e0e6e3; margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px; font-size: 18px;">Vendre un article</h3>
            <form method="POST" action="marketplace.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Titre de l'annonce</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ex: Ordinateur Dell Core i7...">
                    </div>
                    <div class="form-group">
                        <label>Prix</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" style="flex: 1;">
                            <select name="currency" class="form-control" style="width: 100px;">
                                <option value="FG">FG</option>
                                <option value="USD">$ USD</option>
                                <option value="EUR">€ EUR</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description détaillée (État, caractéristiques...)</label>
                    <textarea name="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Photo de l'article</label>
                    <input type="file" name="product_image" class="form-control" accept="image/*">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-form').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Publier l'annonce</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- List Products (Grid layout) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach($products as $p): ?>
                <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;">
                    <!-- Image -->
                    <div style="height: 180px; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                        <?php if($p['image']): ?>
                            <img src="<?= BASE_URL ?>uploads/marketplace/<?= htmlspecialchars($p['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fa-solid fa-image" style="font-size: 40px; color: #ddd;"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div style="padding: 15px;">
                        <h3 style="margin-bottom: 5px; font-size: 16px; color: var(--color-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($p['name']) ?>">
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>
                        <div style="color: var(--color-primary-dark); font-weight: 700; font-size: 18px; margin-bottom: 10px;">
                            <?php 
                                $symbol = $p['currency'] === 'USD' ? '$' : ($p['currency'] === 'EUR' ? '€' : 'FG');
                                if ($p['currency'] === 'USD') echo $symbol . number_format($p['price'], 2, '.', ',');
                                else echo number_format($p['price'], 0, ',', ' ') . ' ' . $symbol;
                            ?>
                        </div>
                        <p style="color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= nl2br(htmlspecialchars($p['description'])) ?>
                        </p>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #eee; padding-top: 10px;">
                            <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #888;">
                                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($p['profile_photo']) ?>" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                                <?= htmlspecialchars($p['first_name']) ?>
                            </div>
                            <div style="display: flex; gap: 5px;">
                                <?php if($current_role === 'admin'): ?>
                                    <button onclick="editProduct(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= htmlspecialchars(addslashes($p['price'])) ?>', '<?= $p['currency'] ?>', `<?= htmlspecialchars(addslashes($p['description'])) ?>`)" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #3b82f6; border-color: #3b82f6;"><i class="fa-solid fa-pen"></i></button>
                                    <a href="marketplace.php?delete=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px; color: #ef4444; border-color: #ef4444;" onclick="return confirm('Supprimer cet article ?');"><i class="fa-solid fa-trash"></i></a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>messages/conversation.php?id=<?= $p['user_id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px;">Contacter</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($products)): ?>
                <div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 40px;">
                    Aucune annonce n'est disponible pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if($current_role === 'admin'): ?>
<!-- Form to edit product -->
<div id="edit-form" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 600px; width: 100%;">
        <h3 style="margin-bottom: 15px; font-size: 18px; color: #3b82f6;">Modifier l'article</h3>
        <form method="POST" action="marketplace.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="edit_id" id="edit-id">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Titre de l'annonce</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Prix</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" step="0.01" name="price" id="edit-price" class="form-control" style="flex: 1;">
                        <select name="currency" id="edit-currency" class="form-control" style="width: 100px;">
                            <option value="FG">FG</option>
                            <option value="USD">$ USD</option>
                            <option value="EUR">€ EUR</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Description détaillée</label>
                <textarea name="description" id="edit-description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label>Nouvelle Photo (Laissez vide pour conserver l'actuelle)</label>
                <input type="file" name="product_image" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-form').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary" style="background: #3b82f6;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProduct(id, name, price, currency, description) {
    document.getElementById('edit-form').style.display = 'flex';
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-price').value = price;
    document.getElementById('edit-currency').value = currency;
    document.getElementById('edit-description').value = description;
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
