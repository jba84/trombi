<?php
/**
 * Configuration de la base de données (PDO)
 */

// Chargement des variables d'environnement
require_once __DIR__ . '/env_loader.php';

// Définition des constantes à partir du fichier .env
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'staff_dir');
define('DB_CREATE_DATABASE', isset($_ENV['DB_CREATE_DATABASE']) ? 
    (strtolower($_ENV['DB_CREATE_DATABASE']) === 'true') : true);

// Préfixe des tables (utile pour les environnements partagés)
define('DB_TABLE_PREFIX', $_ENV['DB_TABLE_PREFIX'] ?? '');

// Noms des tables avec préfixe
define('TABLE_COMPANIES', DB_TABLE_PREFIX . 'companies');
define('TABLE_DEPARTMENTS', DB_TABLE_PREFIX . 'departments');
define('TABLE_STAFF_MEMBERS', DB_TABLE_PREFIX . 'staff_members');
define('TABLE_APP_SETTINGS', DB_TABLE_PREFIX . 'app_settings');

/**
 * Logique de création/sélection de la base
 * Cette partie s'exécute lors de l'appel à getDBConnection()
 */
try {
    // 1. Connexion initiale sans base de données pour vérifier/créer
    $init_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $init_pdo = new PDO($init_dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if (DB_CREATE_DATABASE) {
        $init_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
    }
} catch (PDOException $e) {
    // Si la connexion échoue ici, c'est probablement un problème d'identifiants
    die("Erreur critique de base de données : " . $e->getMessage());
}

// Fonction utilitaire pour le code legacy
function get_table_name($table_name) {
    return DB_TABLE_PREFIX . $table_name;
}