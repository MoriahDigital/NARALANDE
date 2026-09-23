<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$user_id = (int) $_SESSION['user_id'];
$answers = [
    'interest' => trim($_POST['interest'] ?? ''),
    'goal' => trim($_POST['goal'] ?? ''),
    'mode' => trim($_POST['mode'] ?? '')
];
$recommendations = [];
$orientation_done = $_SERVER['REQUEST_METHOD'] === 'POST';

$formations = $pdo->query("SELECT f.*, u.first_name, u.last_name FROM formations f JOIN users u ON u.id = f.user_id ORDER BY f.created_at DESC LIMIT 50")->fetchAll();
if ($orientation_done) {
    $terms = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(implode(' ', $answers)), -1, PREG_SPLIT_NO_EMPTY), static function ($term) {
        return mb_strlen($term) > 2;
    }));
    foreach ($formations as $formation) {
        $haystack = mb_strtolower(implode(' ', [$formation['title'], $formation['school_name'], $formation['location'], $formation['type'], $formation['description']]));
        $score = 0;
        foreach ($terms as $term) if (mb_strpos($haystack, $term) !== false) $score += mb_strlen($term) > 5 ? 3 : 1;
        if ($answers['mode'] !== '' && (($answers['mode'] === 'online' && $formation['type'] === 'online') || ($answers['mode'] === 'offline' && $formation['type'] === 'offline'))) $score += 4;
        if ($score > 0) {
            $formation['orientation_score'] = $score;
            $recommendations[] = $formation;
        }
    }
    usort($recommendations, static function ($first, $second) { return $second['orientation_score'] <=> $first['orientation_score']; });
    $recommendations = array_slice($recommendations, 0, 4);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="surface-main orientation-main">
        <header class="page-heading"><div><span class="eyebrow">Décision personnalisée</span><h1><i class="fa-solid fa-compass"></i> Orientation</h1><p>Répondez à trois questions et obtenez une direction adaptée à votre profil.</p></div><span class="heading-badge"><i class="fa-solid fa-wand-magic-sparkles"></i> Recommandation</span></header>

        <section class="orientation-hero"><div><span class="section-kicker">3 questions, une direction</span><h2>Construisez votre prochaine étape.</h2><p>Répondez en quelques secondes. Naralandé rapproche votre profil des formations disponibles dans la communauté.</p></div><div class="orientation-steps"><span class="is-active">01<br><small>Vous</small></span><i></i><span>02<br><small>Votre voie</small></span><i></i><span>03<br><small>Action</small></span></div></section>

        <form method="POST" class="orientation-form">
            <div class="orientation-question"><label for="interest"><span>01</span> Quel domaine vous attire ?</label><input class="form-control" id="interest" name="interest" value="<?= htmlspecialchars($answers['interest']) ?>" placeholder="Ex. numérique, commerce, santé, design..."></div>
            <div class="orientation-question"><label for="goal"><span>02</span> Quel est votre objectif ?</label><select class="form-control" id="goal" name="goal"><option value="">Choisir un objectif</option><option value="emploi" <?= $answers['goal'] === 'emploi' ? 'selected' : '' ?>>Trouver un emploi</option><option value="competence" <?= $answers['goal'] === 'competence' ? 'selected' : '' ?>>Développer une compétence</option><option value="reconversion" <?= $answers['goal'] === 'reconversion' ? 'selected' : '' ?>>Changer de voie</option><option value="projet" <?= $answers['goal'] === 'projet' ? 'selected' : '' ?>>Lancer un projet</option></select></div>
            <div class="orientation-question"><label for="mode"><span>03</span> Quel format vous convient ?</label><select class="form-control" id="mode" name="mode"><option value="">Peu importe</option><option value="online" <?= $answers['mode'] === 'online' ? 'selected' : '' ?>>À distance</option><option value="offline" <?= $answers['mode'] === 'offline' ? 'selected' : '' ?>>En présentiel</option></select></div>
            <button class="btn btn-primary orientation-submit" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> Voir mon orientation</button>
        </form>

        <?php if ($orientation_done): ?>
            <section class="orientation-results"><div class="results-heading"><div><span class="section-kicker">Votre sélection</span><h2><?= $recommendations ? 'Des pistes pour commencer' : 'Votre parcours commence ici' ?></h2></div><i class="fa-solid fa-lightbulb"></i></div><?php if ($recommendations): ?><div class="orientation-grid"><?php foreach ($recommendations as $formation): ?><article class="orientation-card"><span class="match-score"><?= min(99, 60 + ($formation['orientation_score'] * 7)) ?>%</span><h3><?= htmlspecialchars($formation['title']) ?></h3><p><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($formation['school_name'] ?: 'Organisme communautaire') ?></p><small><?= htmlspecialchars(mb_strimwidth($formation['description'] ?? 'Une formation à explorer selon votre projet.', 0, 130, '...')) ?></small><a href="formations.php" class="btn btn-secondary btn-small">Voir les formations <i class="fa-solid fa-arrow-right"></i></a></article><?php endforeach; ?></div><?php else: ?><div class="empty-panel"><i class="fa-solid fa-compass"></i><strong>Aucune correspondance exacte pour le moment</strong><span>Explorez les formations ou essayez des mots-clés plus précis.</span><a href="formations.php" class="btn btn-primary btn-small">Parcourir les formations</a></div><?php endif; ?></section>
        <?php endif; ?>

        <a class="orientation-resource-link" href="ressources.php"><i class="fa-solid fa-toolbox"></i><span><strong>Besoin de conseils pratiques ?</strong><small>Retrouvez les guides sur le financement, les arnaques et le démarrage dans Ressources utiles.</small></span><i class="fa-solid fa-arrow-right"></i></a>

        <section class="passport-section" data-passport="naralande-<?= $user_id ?>">
            <div class="passport-header"><div><span class="section-kicker">Votre espace d’action</span><h2>Le Passeport de progression</h2><p>Un petit cap concret vaut mieux qu’une longue liste d’intentions.</p></div><button type="button" class="passport-share" id="passport-share"><i class="fa-solid fa-share-nodes"></i> Partager mon cap</button></div>
            <div class="passport-cap"><span class="passport-cap-icon"><i class="fa-solid fa-flag"></i></span><div><small>Mon cap actuel</small><strong><?= $answers['interest'] ? htmlspecialchars($answers['interest']) : 'Construire ma prochaine opportunité' ?></strong><p><?= $answers['goal'] === 'emploi' ? 'Avancer vers un emploi qui vous ressemble.' : ($answers['goal'] === 'reconversion' ? 'Préparer votre changement de voie étape par étape.' : 'Transformer une envie en première action visible.') ?></p></div></div>
            <div class="passport-progress-row"><span id="passport-progress-label">0 / 3 actions terminées</span><div><b id="passport-progress-bar"></b></div><strong id="passport-progress-percent">0%</strong></div>
            <div class="passport-tasks">
                <label class="passport-task"><input type="checkbox"><span class="task-check"><i class="fa-solid fa-check"></i></span><span><strong>Clarifier mon objectif</strong><small>Écrire en une phrase ce que je veux obtenir.</small></span></label>
                <label class="passport-task"><input type="checkbox"><span class="task-check"><i class="fa-solid fa-check"></i></span><span><strong>Faire une première preuve</strong><small>Créer un exemple, un CV, un portfolio ou un prototype.</small></span></label>
                <label class="passport-task"><input type="checkbox"><span class="task-check"><i class="fa-solid fa-check"></i></span><span><strong>Activer une connexion</strong><small>Contacter une personne, une école ou un programme utile.</small></span></label>
            </div>
        </section>
    </main>
</div>
<script>
const passport = document.querySelector('[data-passport]');
if (passport) {
    const storageKey = passport.dataset.passport;
    const tasks = [...passport.querySelectorAll('.passport-task input')];
    const bar = document.getElementById('passport-progress-bar');
    const label = document.getElementById('passport-progress-label');
    const percent = document.getElementById('passport-progress-percent');
    const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
    tasks.forEach((task, index) => { task.checked = Boolean(saved[index]); });
    const updatePassport = () => {
        const completed = tasks.filter(task => task.checked).length;
        const ratio = Math.round((completed / tasks.length) * 100);
        localStorage.setItem(storageKey, JSON.stringify(tasks.map(task => task.checked)));
        bar.style.width = ratio + '%'; label.textContent = completed + ' / ' + tasks.length + ' actions terminées'; percent.textContent = ratio + '%';
    };
    tasks.forEach(task => task.addEventListener('change', updatePassport));
    updatePassport();
    document.getElementById('passport-share')?.addEventListener('click', async () => {
        const text = 'Mon cap sur Naralandé : je construis ma prochaine opportunité, une action à la fois.';
        if (navigator.share) await navigator.share({ title: 'Mon cap Naralandé', text });
        else { await navigator.clipboard.writeText(text); alert('Votre cap a été copié.'); }
    });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
