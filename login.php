<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';

$termsColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'terms_accepted'")->fetch();
if (!$termsColumn) {
    $pdo->exec("ALTER TABLE users ADD terms_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, status, terms_accepted FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = "Votre compte a été suspendu.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['terms_accepted'] = (bool) $user['terms_accepted'];
                header('Location: ' . ($user['terms_accepted'] ? 'dashboard.php' : 'terms.php'));
                exit;
            }
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-layout auth-login-layout">
    <aside class="auth-intro">
        <a href="<?= BASE_URL ?>index.php" class="auth-brand"><img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo Naralandé"><span>Naralandé</span></a>
        <span class="eyebrow">Votre espace personnel</span>
        <h1>Reprenez le fil de vos opportunités.</h1>
        <p>Retrouvez vos échanges, vos réactions et les occasions qui comptent pour vous.</p>
        <div class="auth-intro-note"><i class="fa-solid fa-shield-heart"></i><span><strong>Un espace pensé pour vous</strong><small>Simple, humain et connecté à votre réalité.</small></span></div>
    </aside>
    <section class="auth-container">
    <a href="<?= BASE_URL ?>index.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Retour à l’accueil</a>
    <div class="auth-heading"><span class="section-kicker">Bon retour</span><h2>Connexion</h2><p>Accédez à votre communauté Naralandé.</p></div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Inscription réussie ! Vous pouvez vous connecter.</div>
    <?php endif; ?>

    <?php if(isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Votre mot de passe a été réinitialisé avec succès. Connectez-vous avec votre nouveau mot de passe.</div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="login">Email ou nom d'utilisateur</label>
            <input type="text" name="login" id="login" class="form-control" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div style="position: relative;">
                <input type="password" name="password" id="password" class="form-control" required style="padding-right: 40px;">
                <i class="fa-solid fa-eye" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;" onclick="
                    const pwd = document.getElementById('password');
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
        <div class="auth-forgot-link">
            <a href="<?= BASE_URL ?>forgot_password.php">Mot de passe oublié ?</a>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Se connecter</button>
    </form>
    <p class="auth-switch">Pas encore de compte ? <a href="register.php">Créer mon compte</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
