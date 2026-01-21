<?php
/**
 * Core utility functions - PDO & PHP 8.4 Ready
 * Version consolidée et complète.
 */

// --- CONFIGURATION ---

/**
 * Charge un fichier de configuration
 */
function load_app_settings(string $file = 'app'): array {
    $configPath = CONFIG_PATH . '/' . $file . '.php';
    if (!file_exists($configPath)) return [];
    $config = require $configPath;
    return is_array($config) ? $config : [];
}

/**
 * Alias pour la compatibilité
 */
function load_app_config(string $file = 'app'): array {
    return load_app_settings($file);
}

// --- DATABASE & CONNECTION ---

/**
 * Singleton de connexion PDO
 */
function getDBConnection(): PDO {
    static $conn = null;
    if ($conn === null) {
        require_once CONFIG_PATH . '/database.php';
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Erreur de connexion BDD : " . $e->getMessage());
        }
    }
    return $conn;
}

// --- STAFF DATA (FRONTEND) ---

/**
 * Récupère les employés avec filtres et tri (pour l'accueil)
 */
function get_all_staff_members(PDO $conn, string $sort_by = 'last_name', string $sort_order = 'ASC', string $search = '', string $department = '', string $company = ''): array {
    $params = [];
    $sql = "SELECT s.*, c.name AS company, d.name AS department, d.color AS department_color, c.logo AS company_logo
            FROM staff_members s 
            LEFT JOIN companies c ON s.company_id = c.id 
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR
