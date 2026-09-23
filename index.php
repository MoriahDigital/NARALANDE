<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="premium-landing">
    <nav class="navbar">
        <div class="navbar-container">
            <a href="<?= BASE_URL ?>index.php" class="navbar-brand">
                <img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="Logo Naralandé" class="brand-mark">
                <span>Naralandé</span>
            </a>
            <ul class="navbar-menu">
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php" class="btn btn-secondary btn-small">Créer un compte</a></li>
            </ul>
        </div>
    </nav>

    <section class="premium-hero">
        <div class="hero-copy">
            <div class="hero-eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Le réseau qui transforme les liens en opportunités</div>
            <h1>Votre prochain <span class="highlight">projet commence</span> ici.</h1>
            <p>
                Naralandé réunit les personnes, les idées et les opportunités qui font avancer une communauté. Échangez, apprenez, trouvez votre prochaine mission et donnez de la visibilité à ce que vous construisez.
            </p>

            <div class="hero-actions">
                <a href="register.php" class="btn btn-secondary hero-button">Créer mon compte <i class="fa-solid fa-arrow-right"></i></a>
                <a href="login.php" class="btn btn-outline hero-button">Je me connecte</a>
            </div>

            <div class="hero-metrics">
                <div class="metric">
                    <strong>24k+</strong>
                    <span>Utilisateurs</span>
                </div>
                <div class="metric">
                    <strong>3.2k</strong>
                    <span>Offres</span>
                </div>
                <div class="metric">
                    <strong>98%</strong>
                    <span>Engagement</span>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div>
            <div class="preview-card">
                <div class="preview-header">
                    <div class="preview-user">
                        <img src="<?= BASE_URL ?>uploads/profiles/profile_demo.jpg" alt="Profil utilisateur">
                        <div>
                            <strong>Mahawa Yaya</strong><br>
                            <small class="preview-username">@mahawayay</small>
                        </div>
                    </div>
                    <span class="preview-badge"><i class="fa-solid fa-circle"></i> En direct</span>
                </div>

                <div class="preview-post">
                    <p>
                        &laquo;&nbsp;J'ai trouvé une formation très utile et j'ai pu décrocher un premier contact avec une entreprise grâce à cette communauté.&nbsp;&raquo;
                    </p>
                    <div class="preview-signal"><span class="signal-dot"></span><div><strong>Signal du jour</strong><small>Une idée partagée peut devenir une rencontre.</small></div><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <div class="preview-actions">
                        <span><i class="fa-solid fa-heart"></i> 1.2k</span>
                        <span><i class="fa-solid fa-comment"></i> 184</span>
                        <span><i class="fa-solid fa-share"></i> 97</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pathway-section">
        <div class="pathway-intro"><span class="hero-eyebrow"><i class="fa-solid fa-compass"></i> Votre boussole Naralandé</span><h2>Choisissez votre prochaine direction.</h2><p>Chaque espace est pensé pour déclencher une action concrète, pas seulement faire défiler un écran.</p></div>
        <div class="pathway-grid">
            <a href="register.php" class="pathway-card pathway-green"><span class="pathway-number">01</span><i class="fa-solid fa-people-arrows"></i><strong>Créer des liens utiles</strong><small>Rencontrez des profils qui partagent vos ambitions.</small><b>Rejoindre la communauté <i class="fa-solid fa-arrow-up-right-from-square"></i></b></a>
            <a href="register.php" class="pathway-card pathway-gold"><span class="pathway-number">02</span><i class="fa-solid fa-route"></i><strong>Accélérer son parcours</strong><small>Formations, conseils et opportunités réunis au même endroit.</small><b>Découvrir les parcours <i class="fa-solid fa-arrow-up-right-from-square"></i></b></a>
            <a href="register.php" class="pathway-card pathway-blue"><span class="pathway-number">03</span><i class="fa-solid fa-store"></i><strong>Faire connaître son projet</strong><small>Présentez vos compétences, produits et idées à la bonne audience.</small><b>Donner de la visibilité <i class="fa-solid fa-arrow-up-right-from-square"></i></b></a>
        </div>
    </section>

    <section class="feature-section">
        <div class="section-heading">
            <h2>Un écosystème qui reste humain</h2>
            <p>Des outils simples, des interactions expressives et des opportunités qui circulent dans la vraie vie.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-user-group"></i></div>
                <h3>Réseau communautaire</h3>
                <p>Développez votre cercle, partagez vos idées, engagez des discussions utiles et créez des relations authentiques.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-briefcase"></i></div>
                <h3>Opportunités pro</h3>
                <p>Accédez à des offres d’emploi, des projets, des conseils et des services utiles pour votre progression.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h3>Formation & mentorat</h3>
                <p>Des parcours inspirants, des ressources et des contenus qui aident à développer des compétences concrètes.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-shop"></i></div>
                <h3>Boutique & visibilité</h3>
                <p>Publiez, monétisez et valorisez vos produits ou services dans un environnement crédible et professionnel.</p>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
