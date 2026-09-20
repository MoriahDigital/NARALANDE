<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, status FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = "Votre compte a été suspendu.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <h2>Connexion à Naralandé</h2>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">Inscription réussie ! Vous pouvez vous connecter.</div>
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
        <button type="submit" class="btn btn-primary" style="width: 100%;">Se connecter</button>
    </form>
    <p style="margin-top: 15px; text-align: center;">Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
