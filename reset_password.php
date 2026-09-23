<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$valid_token = false;
$user_email = '';

if (empty($token)) {
    $error = "Lien de réinitialisation invalide ou manquant.";
} else {
    // Verify token
    $stmt = $pdo->prepare("SELECT email, expires_at, used FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = "Ce lien de réinitialisation est invalide.";
    } elseif ($reset['used']) {
        $error = "Ce lien a déjà été utilisé. Veuillez faire une nouvelle demande.";
    } elseif (strtotime($reset['expires_at']) < time()) {
        $error = "Ce lien a expiré. Veuillez faire une nouvelle demande.";
    } else {
        $valid_token = true;
        $user_email = $reset['email'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (strlen($new_password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Update user's password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$hashed, $user_email]);

        if ($result && $stmt->rowCount() > 0) {
            // Mark token as used
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);

            // Redirect to login with success message
            header('Location: ' . BASE_URL . 'login.php?reset=success');
            exit;
        } else {
            $error = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-layout auth-login-layout">
    <aside class="auth-intro">
        <a href="<?= BASE_URL ?>index.php" class="auth-brand"><img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo Naralandé"><span>Naralandé</span></a>
        <span class="eyebrow">Dernière étape</span>
        <h1>Choisissez un mot de passe solide.</h1>
        <p>Créez un nouveau mot de passe pour sécuriser votre compte et retrouver votre communauté.</p>
        <div class="auth-intro-note"><i class="fa-solid fa-shield-halved"></i><span><strong>Conseil sécurité</strong><small>Utilisez au moins 6 caractères avec des lettres, chiffres et symboles.</small></span></div>
    </aside>
    <section class="auth-container">
    <a href="<?= BASE_URL ?>login.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Retour à la connexion</a>
    <div class="auth-heading"><span class="section-kicker">Nouveau mot de passe</span><h2>Réinitialisation</h2><p>Définissez un nouveau mot de passe pour votre compte.</p></div>
    
    <?php if($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php if(!$valid_token): ?>
            <div style="text-align: center; margin-top: 16px;">
                <a href="<?= BASE_URL ?>forgot_password.php" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-rotate-right"></i> Nouvelle demande</a>
            </div>
            <p class="auth-switch">Vous vous souvenez ? <a href="login.php">Se connecter</a></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($valid_token): ?>
    <form method="POST" action="reset_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-group">
            <label for="new_password">Nouveau mot de passe</label>
            <div style="position: relative;">
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6" placeholder="Minimum 6 caractères" style="padding-right: 40px;">
                <i class="fa-solid fa-eye" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;" onclick="
                    const pwd = document.getElementById('new_password');
                    if (pwd.type === 'password') {
                        pwd.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        pwd.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                "></i>
            </div>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe</label>
            <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" placeholder="Retapez le mot de passe" style="padding-right: 40px;">
                <i class="fa-solid fa-eye" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;" onclick="
                    const pwd = document.getElementById('confirm_password');
                    if (pwd.type === 'password') {
                        pwd.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        pwd.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                "></i>
            </div>
        </div>
        <p class="password-strength-note"><i class="fa-solid fa-lock"></i> Minimum 6 caractères, combinaison difficile à deviner.</p>
        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-check-circle"></i> Réinitialiser le mot de passe</button>
    </form>
    <p class="auth-switch">Vous vous souvenez ? <a href="login.php">Se connecter</a></p>
    <?php endif; ?>

    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
