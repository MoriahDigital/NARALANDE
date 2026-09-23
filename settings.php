<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT birth_date, gender, password, profile_photo, cover_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
        $birth_date = $_POST['birth_date'];
        $gender = $_POST['gender'];
        
        $upd = $pdo->prepare("UPDATE users SET birth_date = ?, gender = ? WHERE id = ?");
        if ($upd->execute([$birth_date, $gender, $user_id])) {
            $success_msg = "Vos informations ont été mises à jour avec succès.";
            $user['birth_date'] = $birth_date;
            $user['gender'] = $gender;
        } else {
            $error_msg = "Erreur lors de la mise à jour des informations.";
        }
    } 
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($upd->execute([$hash, $user_id])) {
                        $success_msg = "Votre mot de passe a été modifié en toute sécurité.";
                        $user['password'] = $hash;
                    } else {
                        $error_msg = "Erreur lors de la mise à jour du mot de passe.";
                    }
                } else {
                    $error_msg = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
                }
            } else {
                $error_msg = "Les nouveaux mots de passe ne correspondent pas.";
            }
        } else {
            $error_msg = "Le mot de passe actuel est incorrect.";
        }
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            if (!validateUploadSize($_FILES['profile_photo'])) {
                $error_msg = "La photo de profil ne doit pas dépasser 5 Mo.";
            } else {
                $upload_dir = __DIR__ . '/uploads/profiles/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $tmp_name = $_FILES['profile_photo']['tmp_name'];
                $filename = basename($_FILES['profile_photo']['name']);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                    $new_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $upd = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                        if ($upd->execute([$new_name, $user_id])) {
                            $success_msg = "Votre photo de profil a été mise à jour.";
                            $user['profile_photo'] = $new_name;
                        } else {
                            $error_msg = "Erreur lors de la mise à jour de la photo de profil.";
                        }
                    } else {
                        $error_msg = "Erreur lors du téléchargement de l'image.";
                    }
                } else {
                    $error_msg = "Format d'image non valide. Utilisez JPG, PNG, GIF ou WEBP.";
                }
            }
        } elseif (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
            if (!validateUploadSize($_FILES['cover_photo'])) {
                $error_msg = "La photo de couverture ne doit pas dépasser 5 Mo.";
            } else {
                $upload_dir = __DIR__ . '/uploads/covers/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $tmp_name = $_FILES['cover_photo']['tmp_name'];
                $filename = basename($_FILES['cover_photo']['name']);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
                    $new_name = 'cover_' . $user_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $upd = $pdo->prepare("UPDATE users SET cover_photo = ? WHERE id = ?");
                        if ($upd->execute([$new_name, $user_id])) {
                            $success_msg = "Votre photo de couverture a été mise à jour.";
                            $user['cover_photo'] = $new_name;
                        } else {
                            $error_msg = "Erreur lors de la mise à jour de la photo de couverture.";
                        }
                    } else {
                        $error_msg = "Erreur lors du téléchargement de l'image.";
                    }
                } else {
                    $error_msg = "Format d'image non valide. Utilisez JPG, PNG, GIF ou WEBP.";
                }
            }
        } else {
            $error_msg = "Veuillez sélectionner une image valide.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main class="settings-main">
        <header class="settings-heading">
            <div><span class="eyebrow">Espace personnel</span><h1><i class="fa-solid fa-sliders"></i> Paramètres du compte</h1><p>Personnalisez votre profil et protégez vos accès depuis un seul endroit.</p></div>
            <span class="settings-status"><i class="fa-solid fa-circle-check"></i> Compte actif</span>
        </header>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?= $success_msg ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;"><?= $error_msg ?></div>
        <?php endif; ?>

        <section class="settings-section">
            <div class="settings-section-heading"><div><span class="section-kicker">Apparence</span><h2>Votre identité visuelle</h2></div><i class="fa-solid fa-camera-retro"></i></div>
            <div class="settings-media-grid">
            <!-- Photo de Profil -->
            <div class="settings-card media-card">
                <img src="<?= BASE_URL ?>uploads/profiles/<?= htmlspecialchars($user['profile_photo'] ?? 'default_profile.png') ?>" class="settings-profile-preview">
                <div class="settings-card-content">
                    <h3>Photo de profil</h3><p>Une image claire aide les autres membres à vous reconnaître.</p>
                    <form method="POST" action="settings.php" enctype="multipart/form-data" class="settings-upload-form">
                        <input type="hidden" name="action" value="update_photo">
                        <input type="file" name="profile_photo" class="form-control" accept="image/*" required>
                        <button type="submit" class="btn btn-primary btn-small">Mettre à jour</button>
                    </form>
                </div>
            </div>

            <!-- Photo de Couverture -->
            <div class="settings-card media-card">
                <div class="settings-cover-preview">
                    <?php if(!empty($user['cover_photo']) && $user['cover_photo'] !== 'default_cover.png'): ?>
                        <img src="<?= BASE_URL ?>uploads/covers/<?= htmlspecialchars($user['cover_photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div class="settings-cover-empty">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="settings-card-content">
                    <h3>Photo de couverture</h3><p>Donnez du caractère à l’en-tête de votre profil.</p>
                    <form method="POST" action="settings.php" enctype="multipart/form-data" class="settings-upload-form">
                        <input type="hidden" name="action" value="update_photo">
                        <input type="file" name="cover_photo" class="form-control" accept="image/*" required>
                        <button type="submit" class="btn btn-primary btn-small">Mettre à jour</button>
                    </form>
                </div>
            </div>
            </div>
            </div>
        </section>

        <div class="settings-columns">
            <!-- Informations Personnelles -->
            <section class="settings-card settings-form-card">
                <div class="settings-card-title"><span class="settings-icon"><i class="fa-solid fa-user"></i></span><div><h2>Informations personnelles</h2><p>Gardez vos informations à jour.</p></div></div>
                <form method="POST" action="settings.php">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div class="form-group">
                        <label for="birth_date">Date de naissance</label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Genre</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="male" <?= ($user['gender'] == 'male') ? 'selected' : '' ?>>Homme</option>
                            <option value="female" <?= ($user['gender'] == 'female') ? 'selected' : '' ?>>Femme</option>
                            <option value="other" <?= ($user['gender'] == 'other') ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary settings-submit">Enregistrer les informations</button>
                </form>
            </section>

            <!-- Sécurité du Compte -->
            <section class="settings-card settings-form-card security-card">
                <div class="settings-card-title"><span class="settings-icon security-icon"><i class="fa-solid fa-shield-halved"></i></span><div><h2>Sécurité du compte</h2><p>Renforcez la protection de votre accès.</p></div></div>
                <form method="POST" action="settings.php">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    
                    <p class="password-note"><i class="fa-solid fa-lock"></i> Minimum 6 caractères, avec une combinaison difficile à deviner.</p>
                    <button type="submit" class="btn btn-danger settings-submit">Changer le mot de passe</button>
                </form>
            </section>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
