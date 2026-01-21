<?php
// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Database Configuration using environment variables
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'staff_dir');
define('DB_CREATE_DATABASE', isset($_ENV['DB_CREATE_DATABASE']) ? 
    (strtolower($_ENV['DB_CREATE_DATABASE']) === 'true') : true);

// Define table prefix
define('DB_TABLE_PREFIX', $_ENV['DB_TABLE_PREFIX'] ?? '');

// Define table names with prefixes
define('TABLE_COMPANIES', DB_TABLE_PREFIX . 'companies');
define('TABLE_DEPARTMENTS', DB_TABLE_PREFIX . 'departments');
define('TABLE_STAFF_MEMBERS', DB_TABLE_PREFIX . 'staff_members');
define('TABLE_APP_SETTINGS', DB_TABLE_PREFIX . 'app_settings');

/**
 * CONNEXION INITIALE (Pour vérification/création)
 */
try {
    // On utilise temporairement PDO pour vérifier la base
    $temp_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo_init = new PDO($temp_dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Create database if it doesn't exist
    if (DB_CREATE_DATABASE) {
        $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * CONNEXION PRINCIPALE ($conn) en PDO
 * Indispensable pour tes nouvelles fonctions dans functions.php
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database selection failed: " . $e->getMessage());
}

function get_table_name($table_name) {
    return DB_TABLE_PREFIX . $table_name;
}