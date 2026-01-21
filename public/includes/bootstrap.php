<?php
/**
 * Initialisation de l'application
 */

// 1. Charger les chemins
require_once __DIR__ . '/paths.php';

// 2. Charger les fonctions (load_app_settings sera alors dispo pour la suite)
require_once __DIR__ . '/functions.php';

// 3. Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. Initialiser la connexion PDO
$conn = getDBConnection();

// 5. Charger le gestionnaire de langue (Si le fichier existe)
if (file_exists(__DIR__ . '/LanguageManager.php')) {
    require_once __DIR__ . '/LanguageManager.php';
}