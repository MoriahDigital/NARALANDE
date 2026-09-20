<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

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

// Output JSON format as requested
file_put_contents(__DIR__ . '/mock_data.json', json_encode($mockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Inject into Database
try {
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin_id = $stmt->fetchColumn() ?: 1;

    // Insert Post (Invitation)
    $post_content = "**" . $mockData['publication_invitation']['title'] . "**\n\n" . $mockData['publication_invitation']['body'];
    $postStmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
    $postStmt->execute([$admin_id, $post_content]);

    // Insert Formations (Filières)
    $formStmt = $pdo->prepare("INSERT INTO formations (user_id, title, school_name, location, type, description) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($mockData['publication_invitation']['filieres'] as $filiere) {
        $desc = $filiere['description'] . "\n\nDurée: " . $filiere['duration'] . "\nDébouchés: " . $filiere['debouches'];
        $formStmt->execute([$admin_id, $filiere['name'], "NARALANDE Academy", "Campus Principal", "offline", $desc]);
    }

    // Insert Advice (Conseils & Carrière)
    $artStmt = $pdo->prepare("INSERT INTO articles (user_id, title, category, content) VALUES (?, ?, ?, ?)");
    $artStmt->execute([$admin_id, "Garder le cap : Que faire quand on veut tout abandonner ?", "vie_pratique", $mockData['conseils_carrieres']['advice_text']]);

    echo "Mock data JSON generated and injected into DB successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
