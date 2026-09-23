<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Ensure the password_resets table exists
try {
    $pdo->query("SELECT 1 FROM password_resets LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(100) NOT NULL,
            `token` varchar(64) NOT NULL,
            `expires_at` datetime NOT NULL,
            `used` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_token` (`token`),
            KEY `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

$error = '';
$success = '';
$show_link = false;
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Veuillez saisir votre adresse email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez saisir une adresse email valide.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, first_name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Invalidate any previous tokens for this email
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0")->execute([$email]);

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Store token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires_at]);

            // Build reset link
            $reset_link = BASE_URL . "reset_password.php?token=" . $token;

            // Try to send email
            $subject = "Naralandé — Réinitialisation de votre mot de passe";
            $message = "Bonjour " . htmlspecialchars($user['first_name']) . ",\n\n";
            $message .= "Vous avez demandé la réinitialisation de votre mot de passe sur Naralandé.\n\n";
            $message .= "Cliquez sur le lien ci-dessous pour choisir un nouveau mot de passe :\n";
            $message .= $reset_link . "\n\n";
            $message .= "Ce lien est valide pendant 30 minutes.\n\n";
            $message .= "Si vous n'avez pas fait cette demande, ignorez simplement ce message.\n\n";
            $message .= "— L'équipe Naralandé";

            $headers = "From: noreply@naralande.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $mail_sent = @mail($user['email'], $subject, $message, $headers);

            if ($mail_sent) {
                $success = "Un lien de réinitialisation a été envoyé à votre adresse email. Vérifiez votre boîte de réception (et vos spams).";
            } else {
                // Fallback: show link directly (useful in local/dev or if mail() is unavailable)
                $success = "Votre demande a été traitée. Utilisez le lien ci-dessous pour réinitialiser votre mot de passe :";
                $show_link = true;
            }
        } else {
            // Don't reveal whether the email exists — show same success message
            $success = "Si cette adresse est associée à un compte, un lien de réinitialisation vous a été envoyé.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-layout auth-login-layout">
    <aside class="auth-intro">
        <a href="<?= BASE_URL ?>index.php" class="auth-brand"><img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo Naralandé"><span>Naralandé</span></a>
        <span class="eyebrow">Retrouvez votre accès</span>
        <h1>Pas de panique, on vous aide.</h1>
        <p>Entrez votre adresse email et recevez un lien sécurisé pour créer un nouveau mot de passe.</p>
        <div class="auth-intro-note"><i class="fa-solid fa-lock"></i><span><strong>Processus sécurisé</strong><small>Le lien expire après 30 minutes et ne peut être utilisé qu'une seule fois.</small></span></div>
    </aside>
    <section class="auth-container">
    <a href="<?= BASE_URL ?>login.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Retour à la connexion</a>
    <div class="auth-heading"><span class="section-kicker">Mot de passe oublié</span><h2>Réinitialisation</h2><p>Saisissez l'email associé à votre compte Naralandé.</p></div>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php if($show_link && $reset_link): ?>
            <div class="reset-link-box">
                <label>Lien de réinitialisation :</label>
                <div class="reset-link-wrapper">
                    <a href="<?= htmlspecialchars($reset_link) ?>" class="reset-link-url"><?= htmlspecialchars($reset_link) ?></a>
                    <button type="button" class="btn btn-secondary btn-small" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($reset_link) ?>'); this.innerHTML='<i class=\'fa-solid fa-check\'></i> Copié !';">
                        <i class="fa-solid fa-copy"></i> Copier
                    </button>
                </div>
                <p class="reset-link-note"><i class="fa-solid fa-clock"></i> Ce lien expire dans 30 minutes.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if(!$success): ?>
    <form method="POST" action="forgot_password.php">
        <div class="form-group">
            <label for="email">Adresse email</label>
            <div style="position: relative;">
                <i class="fa-solid fa-envelope" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #999; font-size: 14px;"></i>
                <input type="email" name="email" id="email" class="form-control" required placeholder="votre.email@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" style="padding-left: 40px;">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Envoyer le lien</button>
    </form>
    <?php endif; ?>

    <?php if($success && !$show_link): ?>
        <div style="text-align: center; margin-top: 16px;">
            <a href="<?= BASE_URL ?>login.php" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-arrow-left"></i> Retour à la connexion</a>
        </div>
    <?php endif; ?>

    <p class="auth-switch">Vous vous souvenez ? <a href="login.php">Se connecter</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
