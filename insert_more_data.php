<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_id = $stmt->fetchColumn() ?: 1;

try {
    // Insert more Formations
    $pdo->exec("INSERT INTO formations (user_id, title, school_name, location, type, description, image) VALUES 
    ($admin_id, 'Formation Developpeur Full-Stack JS', 'Le Wagon', 'En Ligne', 'online', 'Devenez dev web avec React et Node.js en 9 semaines intensives.', 'form_1.jpg'),
    ($admin_id, 'Master en Intelligence Artificielle', 'Polytech Conakry', 'Conakry', 'offline', 'Formation complète de 2 ans sur l\'IA et le Machine Learning.', 'form_2.jpg'),
    ($admin_id, 'Certification AWS Cloud Practitioner', 'Amazon Web Services', 'En Ligne', 'online', 'Préparez-vous à l\'examen officiel avec nos cours et quiz exclusifs.', 'form_1.jpg')
    ");
    
    // Insert Articles (Conseils & Carrière)
    $pdo->exec("INSERT INTO articles (user_id, title, category, content) VALUES 
    ($admin_id, 'Comment réussir son entretien d\'embauche en ligne ?', 'emploi', 'Les entretiens en visioconférence sont devenus la norme. \n\n1. Préparez votre environnement : assurez-vous d\'avoir un fond neutre et une bonne connexion.\n2. Regardez la caméra, pas l\'écran, pour créer un contact visuel.\n3. Habillez-vous comme pour un entretien classique.\n\nBonne chance !'),
    ($admin_id, '5 conseils pour mieux s\'organiser en télétravail', 'vie_pratique', 'Le télétravail peut vite devenir chaotique si l\'on ne s\'organise pas.\n\n- Créez un espace dédié au travail.\n- Fixez-vous des horaires stricts.\n- Prenez des pauses régulières (méthode Pomodoro).\n- Éteignez les notifications personnelles sur votre téléphone.\n- Gardez le lien avec vos collègues en organisant des points réguliers.'),
    ($admin_id, 'Comment négocier son salaire lors de l\'embauche ?', 'emploi', 'Négocier son salaire est souvent intimidant. Voici comment vous y prendre :\n\nFaites des recherches sur le marché. Ne donnez pas le premier chiffre si possible. Valorisez vos compétences et ce que vous allez apporter à l\'entreprise plutôt que vos besoins personnels. Restez confiant !')
    ");

    echo "Nouvelles données ajoutées avec succès dans Formations et Conseils & Carrière.\n";
} catch (Exception $e) {
    echo "Erreur lors de l'insertion : " . $e->getMessage() . "\n";
}
?>
