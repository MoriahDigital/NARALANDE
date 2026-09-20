<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height: 100vh; display: flex; flex-direction: column; background: var(--color-primary-dark); margin: -8px;">
    
    <!-- Navbar for Landing -->
    <nav style="padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; width: 100%;">
        <div style="color: white; font-size: 28px; font-weight: 800; display: flex; align-items: center; gap: 15px; letter-spacing: -0.5px;">
            <img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo Naralandé" style="height: 45px; border-radius: 8px;">
            Naralandé
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="login.php" class="btn" style="background: transparent; color: white; border: 2px solid rgba(255,255,255,0.3); padding: 10px 24px;">Se connecter</a>
        </div>
    </nav>

    <!-- Main Hero Section -->
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px;">
        <div style="max-width: 1200px; width: 100%; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 60px;">
            
            <!-- Left Side: Text and CTA -->
            <div style="flex: 1; min-width: 300px; color: white; max-width: 600px;">
                <h1 style="font-size: 64px; font-weight: 900; line-height: 1.1; margin-bottom: 25px;">
                    Le <span style="color: var(--color-accent);">lieu de rencontre</span> idéal.
                </h1>
                <p style="font-size: 20px; line-height: 1.6; margin-bottom: 40px; color: rgba(255,255,255,0.8);">
                    Rejoignez Naralandé dès aujourd'hui. Partagez vos moments, découvrez de nouvelles personnes, et construisez votre communauté dans un espace entièrement sécurisé et moderne.
                </p>
                <div style="display: flex; gap: 20px;">
                    <a href="register.php" class="btn btn-secondary" style="font-size: 18px; padding: 16px 36px; box-shadow: 0 10px 25px rgba(201, 162, 39, 0.4);">
                        Créer un compte gratuit
                    </a>
                </div>
            </div>
            
            <!-- Right Side: Big Logo/Visual -->
            <div style="flex: 1; min-width: 300px; display: flex; justify-content: center; position: relative;">
                <div style="background: white; padding: 50px; border-radius: 40px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); transform: rotate(-2deg); transition: transform 0.3s ease;" onmouseover="this.style.transform='rotate(0deg) scale(1.02)'" onmouseout="this.style.transform='rotate(-2deg)'">
                    <img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo Naralandé" style="width: 100%; max-width: 400px; border-radius: 20px;">
                </div>
                <!-- Decorative elements -->
                <div style="position: absolute; top: -20px; right: 20px; width: 100px; height: 100px; background: var(--color-accent); border-radius: 50%; z-index: -1; opacity: 0.8; filter: blur(20px);"></div>
                <div style="position: absolute; bottom: -20px; left: 20px; width: 150px; height: 150px; background: var(--color-primary-light); border-radius: 50%; z-index: -1; opacity: 0.6; filter: blur(30px);"></div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
