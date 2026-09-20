<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_id = $stmt->fetchColumn() ?: 1;

try {
    $pdo->exec("INSERT INTO posts (user_id, content) VALUES 
    ($admin_id, 'a publié une nouvelle formation : Formation Developpeur Full-Stack JS (Le Wagon)'),
    ($admin_id, 'a publié un nouvel article dans Conseils & Carrière (Recherche d\'emploi) :\n\nComment réussir son entretien d\'embauche en ligne ?'),
    ($admin_id, 'a publié un nouvel article dans Conseils & Carrière (Conseils de vie & Organisation) :\n\n5 conseils pour mieux s\'organiser en télétravail')
    ");

    echo "Posts ajoutés avec succès.\n";
} catch (Exception $e) {
    echo "Erreur lors de l'insertion : " . $e->getMessage() . "\n";
}
?>
