<?php
/**
 * Initialisation de l'application
 */

// 1. D'abord, on définit les chemins (BASE_PATH, etc.)
require_once __DIR__ . '/paths.php';

// 2. CRITIQUE : Charger l'autoloader de Composer (FastRoute, Monolog, etc.)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    // Message d'aide si on a oublié l'étape 1
    die("<h3>Erreur critique</h3><p>Le dossier <code>vendor</code> est manquant.</p><p>Veuillez lancer la commande : <code>composer install</code> à la racine du projet.</p>");
}

// 3. Charger nos fonctions maison
require_once __DIR__ . '/functions.php';

// 4. Initialiser la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 5. Initialiser la connexion BDD (via PDO)
$conn = getDBConnection();

// 6. Initialiser le Logger (Monolog) pour auth.php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('staff_directory');
// On logue dans /var/www/html/staff-directory/var/logs/app.log (créez le dossier var/logs si besoin)
$logFile = BASE_PATH . '/var/logs/app.log';

// On s'assure que le dossier de logs existe
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

try {
    $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
} catch (Exception $e) {
    // Si Monolog échoue (permissions), on continue sans logger pour ne pas bloquer le site
    $logger = null;
}

// 7. Charger le gestionnaire de langue
if (file_exists(__DIR__ . '/LanguageManager.php')) {
    require_once __DIR__ . '/LanguageManager.php';
}
