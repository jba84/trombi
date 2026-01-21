<?php
/**
 * Définition des chemins absolus
 */
// Racine du projet (ex: /var/www/html/staff-directory)
define('BASE_PATH', dirname(__DIR__, 2)); 

// Dossiers internes
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('INCLUDES_PATH', PUBLIC_PATH . '/includes');