<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Données en dur (Mock Data)
$mockData = [
    "publication_invitation" => [
        "title" => "🚀 Préparez votre avenir : Les inscriptions pour la rentrée sont ouvertes !",
        "body" => "Rejoignez notre campus d'excellence et donnez une nouvelle dimension à votre parcours académique. Nous vous offrons un cadre d'apprentissage innovant, des professeurs passionnés et des opportunités uniques pour bâtir la carrière de vos rêves. N'attendez plus, inscrivez-vous dès aujourd'hui et prenez votre avenir en main !",
        "filieres" => [
            [
                "name" => "Licence en Ingénierie Quantique Appliquée",
                "description" => "Apprenez à maîtriser les technologies de demain en explorant le calcul quantique et ses applications industrielles.",
                "duration" => "3 ans",
                "debouches" => "Ingénieur quantique, chercheur en technologies avancées"
            ],
            [
                "name" => "Master en Éco-Conception et Villes Durables",
                "description" => "Concevez les métropoles de demain avec une approche centrée sur l'écologie et l'innovation urbaine.",
                "duration" => "2 ans",
                "debouches" => "Urbaniste écologique, consultant en développement durable"
            ],
            [
                "name" => "Bachelor en Cyber-Psychologie",
                "description" => "Étudiez l'impact du numérique sur le comportement humain et la sécurité des données comportementales.",
                "duration" => "3 ans",
                "debouches" => "Cyber-psychologue, analyste en sécurité humaine"
            ]
        ]
    ],
    "conseils_carrieres" => [
        "menu_title" => "Conseils et Carrières",
        "advice_text" => "Il est tout à fait normal de traverser des périodes de doute ou de démotivation au cours de ses études. Si vous songez à abandonner, rappelez-vous pourquoi vous avez commencé et tout le chemin que vous avez déjà parcouru. Chaque défi surmonté vous rend plus fort et vous rapproche de vos objectifs. Ne prenez pas de décision précipitée sous le coup de la fatigue ou du stress. Prenez le temps de souffler, parlez-en à vos proches, et n'hésitez surtout pas à solliciter de l'aide. Votre conseiller pédagogique, les professeurs ou les services d'accompagnement de l'école sont là pour vous écouter et vous aider à trouver des solutions adaptées. Vous n'êtes pas seul(e) dans cette épreuve, accrochez-vous !"
    ]
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div style="display: flex; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 20px;">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <main style="flex: 1; background: transparent; display: flex; flex-direction: column; gap: 30px;">
        
        <!-- Section 1 : Publication d'invitation -->
        <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 4px solid var(--color-primary);">
            <h1 style="color: var(--color-primary-dark); font-size: 24px; margin-bottom: 15px;">
                <?= htmlspecialchars($mockData['publication_invitation']['title']) ?>
            </h1>
            <p style="font-size: 16px; color: #444; line-height: 1.6; margin-bottom: 25px;">
                <?= htmlspecialchars($mockData['publication_invitation']['body']) ?>
            </p>
            
            <h2 style="font-size: 18px; color: #333; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                Nos Filières Ouvertes à l'Inscription
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach($mockData['publication_invitation']['filieres'] as $filiere): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; transition: transform 0.2s;">
                        <h3 style="color: var(--color-primary); font-size: 16px; margin-bottom: 10px;">
                            <i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars($filiere['name']) ?>
                        </h3>
                        <p style="font-size: 14px; color: #555; margin-bottom: 15px;">
                            <?= htmlspecialchars($filiere['description']) ?>
                        </p>
                        <div style="font-size: 13px; color: #64748b; display: flex; flex-direction: column; gap: 5px;">
                            <span><strong>Durée :</strong> <?= htmlspecialchars($filiere['duration']) ?></span>
                            <span><strong>Débouchés :</strong> <?= htmlspecialchars($filiere['debouches']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 25px; text-align: center;">
                <button class="btn btn-primary" style="padding: 10px 25px; font-size: 16px;">S'inscrire maintenant</button>
            </div>
        </div>

        <!-- Section 2 : Conseils et Carrières -->
        <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid #f59e0b;">
            <h2 style="color: #d97706; font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-life-ring"></i> <?= htmlspecialchars($mockData['conseils_carrieres']['menu_title']) ?>
            </h2>
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; color: #92400e; font-size: 15px; line-height: 1.7; font-style: italic;">
                "<?= nl2br(htmlspecialchars($mockData['conseils_carrieres']['advice_text'])) ?>"
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
