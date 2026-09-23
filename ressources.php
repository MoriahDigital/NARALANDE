<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$resources = [
    ['category' => 'emploi', 'label' => 'Emploi', 'icon' => 'briefcase', 'title' => 'Trouver un emploi sans réseau puissant', 'summary' => 'Construisez une preuve de compétence et contactez les bonnes personnes.', 'steps' => ['Préparez un CV d’une page avec un numéro joignable.', 'Créez un exemple concret de votre travail, même simple.', 'Ciblez 5 entreprises ou professionnels chaque semaine.', 'Relancez avec respect après 3 à 5 jours.']],
    ['category' => 'formation', 'label' => 'Formation', 'icon' => 'graduation-cap', 'title' => 'Apprendre avec peu de data', 'summary' => 'Organisez votre apprentissage autour de ressources accessibles et d’un objectif précis.', 'steps' => ['Choisissez une seule compétence pour les 30 prochains jours.', 'Téléchargez les contenus quand vous avez une bonne connexion.', 'Pratiquez 30 minutes par jour sur un projet réel.', 'Montrez votre progression à un mentor ou à un proche.']],
    ['category' => 'financement', 'label' => 'Financement', 'icon' => 'coins', 'title' => 'Financer une petite activité', 'summary' => 'Commencez par tester la demande avant de chercher un grand financement.', 'steps' => ['Parlez à 10 clients potentiels avant de dépenser.', 'Commencez avec un service que vous savez déjà rendre.', 'Séparez l’argent de l’activité et vos dépenses personnelles.', 'Réinvestissez une partie de chaque bénéfice.']],
    ['category' => 'securite', 'label' => 'Sécurité', 'icon' => 'shield-halved', 'title' => 'Éviter les arnaques numériques', 'summary' => 'Protégez votre argent, vos comptes et vos documents dans vos échanges en ligne.', 'steps' => ['Ne partagez jamais un code de validation reçu par SMS.', 'Vérifiez l’identité avant tout paiement ou recrutement.', 'Méfiez-vous des emplois qui demandent de payer pour commencer.', 'Gardez une copie de vos preuves et signalez les abus.']],
    ['category' => 'activite', 'label' => 'Activité', 'icon' => 'store', 'title' => 'Démarrer avec son téléphone', 'summary' => 'Transformez une compétence locale en première offre visible.', 'steps' => ['Choisissez un service clair : coiffure, livraison, design, réparation ou conseil.', 'Photographiez votre travail avec une bonne lumière.', 'Publiez un prix et une zone de service compréhensibles.', 'Demandez un témoignage après chaque prestation.']],
    ['category' => 'orientation', 'label' => 'Orientation', 'icon' => 'compass', 'title' => 'Choisir une voie réaliste', 'summary' => 'Comparez vos intérêts, les débouchés et les moyens disponibles.', 'steps' => ['Listez trois domaines qui vous attirent réellement.', 'Parlez à une personne qui exerce déjà ce métier.', 'Comparez durée, coût, lieu et débouchés.', 'Testez la voie avec une petite activité ou un cours court.']],
];

$pdo->exec("CREATE TABLE IF NOT EXISTS resources (id INT NOT NULL AUTO_INCREMENT, category VARCHAR(40) NOT NULL, title VARCHAR(160) NOT NULL, summary TEXT NOT NULL, content TEXT, link VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$admin_resources = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC")->fetchAll();
$admin_icons = ['emploi' => 'briefcase', 'formation' => 'graduation-cap', 'financement' => 'coins', 'securite' => 'shield-halved', 'activite' => 'store', 'orientation' => 'compass'];
foreach ($admin_resources as $resource) {
    $resources[] = ['category' => $resource['category'], 'label' => ucfirst($resource['category']), 'icon' => $admin_icons[$resource['category']] ?? 'circle-info', 'title' => $resource['title'], 'summary' => $resource['summary'], 'steps' => array_values(array_filter(preg_split('/\r?\n|[.;]/', $resource['content'] ?? ''))), 'link' => $resource['link']];
}

$resource_question = trim($_GET['question'] ?? '');
$assistant_answer = '';
$assistant_matches = [];
if ($resource_question !== '') {
    $question_terms = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($resource_question), -1, PREG_SPLIT_NO_EMPTY), static function ($term) { return mb_strlen($term) > 2; }));
    foreach ($resources as $resource) {
        $searchable = mb_strtolower($resource['title'] . ' ' . $resource['summary'] . ' ' . implode(' ', $resource['steps']) . ' ' . $resource['category']);
        $score = 0;
        foreach ($question_terms as $term) if (mb_strpos($searchable, $term) !== false) $score += mb_strlen($term) > 5 ? 3 : 1;
        if ($score > 0) { $resource['assistant_score'] = $score; $assistant_matches[] = $resource; }
    }
    usort($assistant_matches, static function ($first, $second) { return $second['assistant_score'] <=> $first['assistant_score']; });
    $assistant_matches = array_slice($assistant_matches, 0, 2);
    $assistant_answer = $assistant_matches ? 'Voici les ressources les plus proches de votre question. Commencez par la première étape, puis ouvrez le lien proposé si l’administrateur en a ajouté un.' : 'Je n’ai pas encore une fiche qui répond exactement. Essayez avec des mots comme emploi, formation, financement, arnaque, téléphone ou activité.';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<div class="page-shell content-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    <main class="surface-main resources-main">
        <header class="page-heading"><div><span class="eyebrow">Guides pratiques</span><h1><i class="fa-solid fa-compass-drafting"></i> Ressources utiles</h1><p>Des fiches courtes pour financer, se protéger, apprendre et démarrer concrètement.</p></div><span class="heading-badge"><i class="fa-solid fa-wifi"></i> Léger en data</span></header>
        <section class="resource-assistant"><div class="assistant-title"><span class="assistant-orb"><i class="fa-solid fa-wand-magic-sparkles"></i></span><div><span class="eyebrow">Assistant ressources</span><h2>Posez votre question</h2><p>Une réponse guidée à partir des fiches validées par Naralandé.</p></div></div><form method="GET" class="resource-question-form"><input class="form-control" name="question" value="<?= htmlspecialchars($resource_question) ?>" placeholder="Ex. Comment éviter une arnaque à l'emploi ?" required><button class="btn btn-primary" type="submit">Répondre</button></form><?php if ($resource_question !== ''): ?><div class="resource-answer"><strong><?= htmlspecialchars($assistant_answer) ?></strong><?php if ($assistant_matches): ?><div class="answer-links"><?php foreach ($assistant_matches as $match): ?><a href="#resource-<?= array_search($match, $resources, true) ?>"><i class="fa-solid fa-arrow-right"></i> <?= htmlspecialchars($match['title']) ?></a><?php endforeach; ?></div><?php endif; ?></div><?php endif; ?></section>
        <div class="resource-toolbar"><div class="resource-search"><i class="fa-solid fa-magnifying-glass"></i><input id="resource-search" type="search" placeholder="Rechercher une aide, un métier, une idée..."></div><div class="resource-filters"><button class="resource-filter is-active" data-filter="all">Tout</button><?php foreach (['emploi', 'formation', 'financement', 'securite', 'activite', 'orientation'] as $filter): ?><button class="resource-filter" data-filter="<?= $filter ?>"><?= ucfirst($filter) ?></button><?php endforeach; ?></div></div>
        <div class="resource-grid" id="resource-grid">
            <?php foreach ($resources as $index => $resource): ?>
                <article id="resource-<?= $index ?>" class="resource-card" data-category="<?= $resource['category'] ?>" data-search="<?= htmlspecialchars(mb_strtolower($resource['title'] . ' ' . $resource['summary'] . ' ' . implode(' ', $resource['steps']))) ?>">
                    <div class="resource-card-top"><span class="resource-icon"><i class="fa-solid fa-<?= $resource['icon'] ?>"></i></span><button class="resource-save" data-resource="<?= $index ?>" aria-label="Enregistrer"><i class="fa-regular fa-bookmark"></i></button></div><span class="resource-label"><?= htmlspecialchars($resource['label']) ?></span><h2><?= htmlspecialchars($resource['title']) ?></h2><p><?= htmlspecialchars($resource['summary']) ?></p><details><summary>Voir les étapes</summary><ol><?php foreach ($resource['steps'] as $step): ?><li><?= htmlspecialchars(trim($step)) ?></li><?php endforeach; ?></ol></details><?php if (!empty($resource['link'])): ?><a class="resource-external-link" href="<?= htmlspecialchars($resource['link']) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-link"></i> Ouvrir le lien</a><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="resource-note"><i class="fa-solid fa-circle-info"></i><p><strong>Bon réflexe :</strong> aucune institution sérieuse ne vous demande votre code secret, votre mot de passe ou un paiement urgent pour vous garantir un emploi.</p></div>
    </main>
</div>
<script>
const resourceSearch = document.getElementById('resource-search');
const resourceCards = [...document.querySelectorAll('.resource-card')];
let activeFilter = 'all';
function filterResources() { const term = (resourceSearch.value || '').toLowerCase(); resourceCards.forEach(card => { card.hidden = !(activeFilter === 'all' || card.dataset.category === activeFilter) || !card.dataset.search.includes(term); }); }
document.querySelectorAll('.resource-filter').forEach(button => button.addEventListener('click', () => { document.querySelectorAll('.resource-filter').forEach(item => item.classList.remove('is-active')); button.classList.add('is-active'); activeFilter = button.dataset.filter; filterResources(); }));
resourceSearch.addEventListener('input', filterResources);
const savedResources = JSON.parse(localStorage.getItem('naralande-resources') || '[]');
document.querySelectorAll('.resource-save').forEach(button => { const id = button.dataset.resource; if (savedResources.includes(id)) { button.classList.add('is-saved'); button.innerHTML = '<i class="fa-solid fa-bookmark"></i>'; } button.addEventListener('click', () => { const position = savedResources.indexOf(id); if (position === -1) { savedResources.push(id); button.classList.add('is-saved'); button.innerHTML = '<i class="fa-solid fa-bookmark"></i>'; } else { savedResources.splice(position, 1); button.classList.remove('is-saved'); button.innerHTML = '<i class="fa-regular fa-bookmark"></i>'; } localStorage.setItem('naralande-resources', JSON.stringify(savedResources)); }); });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
