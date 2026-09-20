<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($birth_date)) {
        $errors[] = "Veuillez remplir tous les champs obligatoires.";
    }

    if ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password, birth_date) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$first_name, $last_name, $username, $email, $hashed_password, $birth_date])) {
                header('Location: login.php?success=1');
                exit;
            } else {
                $errors[] = "Une erreur est survenue lors de l'inscription.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <h2>Créer un compte Naralandé</h2>
    <?php if(!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach($errors as $err) echo "<li>".htmlspecialchars($err)."</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="register.php">
        <div class="form-group">
            <label for="first_name">Prénom *</label>
            <input type="text" name="first_name" id="first_name" class="form-control" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="last_name">Nom *</label>
            <input type="text" name="last_name" id="last_name" class="form-control" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="username">Nom d'utilisateur *</label>
            <input type="text" name="username" id="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="birth_date">Date de naissance *</label>
            <input type="date" name="birth_date" id="birth_date" class="form-control" required value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password">Mot de passe *</label>
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
        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe *</label>
            <div style="position: relative;">
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required style="padding-right: 40px;">
                <i class="fa-solid fa-eye" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;" onclick="
                    const pwd = document.getElementById('password_confirm');
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
        <button type="submit" class="btn btn-primary" style="width: 100%;">S'inscrire</button>
    </form>
    <p style="margin-top: 15px; text-align: center;">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
