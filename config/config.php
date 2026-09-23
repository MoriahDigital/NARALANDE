<?php
define('SITE_NAME', 'Naralandé');

// Détecte automatiquement le protocole (http/https) et le domaine,
// que ce soit en local (XAMPP) ou une fois hébergé
function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // En local, le projet est dans un sous-dossier (/NARALANDE/)
    // En ligne, il est à la racine du domaine (naralande.com/)
    if ($host === 'localhost' || $host === '127.0.0.1') {
        return $protocol . $host . '/NARALANDE/';
    }

    return $protocol . $host . '/';
}

define('BASE_URL', getBaseUrl());

// Taille maximale d'upload : 5 Mo (en octets)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// Extensions d'images autorisées
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

/**
 * Vérifie la taille d'un fichier uploadé.
 * Retourne true si le fichier est dans la limite, false sinon.
 */
function validateUploadSize(array $file): bool
{
    return isset($file['size']) && $file['size'] <= MAX_UPLOAD_SIZE;
}

function displayFirstName(array $user): string
{
    return $user['first_name'] ?? '';
}

function displayFullName(array $user): string
{
    return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

function displayUsername(array $user): string
{
    return $user['username'] ?? '';
}
?>
