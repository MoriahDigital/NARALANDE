<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$termsColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'terms_accepted'")->fetch();
if (!$termsColumn) {
    $pdo->exec("ALTER TABLE users ADD terms_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
}

// Check if already accepted
$stmt = $pdo->prepare("SELECT terms_accepted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn()) {
    $_SESSION['terms_accepted'] = true;
    header('Location: dashboard.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept']) && isset($_POST['agree'])) {
        $stmt = $pdo->prepare("UPDATE users SET terms_accepted = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['terms_accepted'] = true;
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    } elseif (isset($_POST['decline'])) {
        header('Location: logout.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions d'utilisation - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .terms-container {
            background: #fff;
            max-width: 600px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .terms-header {
            background: var(--color-primary);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .terms-header img {
            height: 60px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .terms-content {
            padding: 30px;
            color: #374151;
            line-height: 1.6;
        }
        .rule-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-start;
        }
        .rule-icon {
            color: #ef4444;
            font-size: 24px;
            background: #fef2f2;
            padding: 10px;
            border-radius: 50%;
            min-width: 24px;
            text-align: center;
        }
        .rule-text h4 {
            margin: 0 0 5px 0;
            color: #111827;
            font-size: 16px;
        }
        .rule-text p {
            margin: 0;
            font-size: 14px;
            color: #4b5563;
        }
        .terms-actions {
            background: #f9fafb;
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="terms-container">
        <div class="terms-header">
            <img src="<?= BASE_URL ?>assets/images/logo.jpg" alt="<?= SITE_NAME ?> Logo">
            <h2 style="margin: 0; font-size: 24px;">Charte de bonne conduite</h2>
            <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 14px;">Votre sécurité et votre confort sont nos priorités</p>
        </div>
        
        <div class="terms-content">
            <p style="margin-top: 0; font-size: 15px; margin-bottom: 25px;">
                Bienvenue sur <strong>Naralandé</strong> ! Pour garantir un espace sain, respectueux et constructif pour tous nos utilisateurs, nous avons mis en place des règles strictes de tolérance zéro. En continuant, vous vous engagez à respecter les points suivants :
            </p>
            
            <div class="rule-item">
                <div class="rule-icon"><i class="fa-solid fa-ban"></i></div>
                <div class="rule-text">
                    <h4>Pas d'injures ni de harcèlement</h4>
                    <p>Soyez respectueux envers les autres membres. Toute insulte, menace ou comportement visant à intimider sera sanctionné par un bannissement.</p>
                </div>
            </div>
            
            <div class="rule-item">
                <div class="rule-icon"><i class="fa-solid fa-user-shield"></i></div>
                <div class="rule-text">
                    <h4>Aucune nudité ni contenu explicite</h4>
                    <p>La publication d'images à caractère sexuel, violent ou choquant est strictement interdite sur toute la plateforme.</p>
                </div>
            </div>
            
            <div class="rule-item">
                <div class="rule-icon"><i class="fa-solid fa-hand-holding-hand"></i></div>
                <div class="rule-text">
                    <h4>Non aux propos racistes et discriminatoires</h4>
                    <p>Naralandé est un espace inclusif. La haine, le racisme, la xénophobie et toute forme de discrimination n'ont pas leur place ici.</p>
                </div>
            </div>
            
            <p style="margin-bottom: 0; font-size: 13px; color: #6b7280; margin-top: 25px; text-align: center;">
                En cliquant sur "J'accepte", vous reconnaissez avoir lu et compris ces règles. Le non-respect entraînera la suppression immédiate de votre compte.
            </p>
        </div>
        
        <form method="POST" action="terms.php" class="terms-actions" style="flex-direction: column; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                <input type="checkbox" name="agree" id="agree" required style="width: 18px; height: 18px; cursor: pointer;">
                <label for="agree" style="font-size: 14px; color: #374151; cursor: pointer; user-select: none;">J'ai lu et j'accepte les conditions d'utilisation</label>
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end; width: 100%;">
                <button type="submit" name="decline" class="btn btn-secondary" formnovalidate style="border: 1px solid #d1d5db; background: white; color: #374151;">Je refuse (Déconnexion)</button>
                <button type="submit" name="accept" class="btn btn-primary" style="background: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-check"></i> Continuer
                </button>
            </div>
        </form>
    </div>
</body>
</html>
