<?php
/**
 * Staff Directory Path Configuration
 * 
 * This file defines all path constants used throughout the application.
 * It is the single source of truth for path definitions and is used by
 * both the installer and the main application.
 */

// Define base paths if not already defined
if (!defined('BASE_PATH')) {
    // Project root is two levels up from the includes directory
    // /project-root/public/includes -> /project-root
    define('BASE_PATH', dirname(__DIR__, 2)); // Project root
    
    // Path to the directory containing private files like configurations and vendor libs
    define('PRIVATE_PATH', BASE_PATH); 
    
    // Path to the public web root
    define('PUBLIC_PATH', BASE_PATH . '/public');

    // CORRECTED: Define specific paths used by the application functions
    define('CONFIG_PATH', PRIVATE_PATH . '/config');
    define('LANG_PATH', PRIVATE_PATH . '/languages');
    
    // The base URI for routing (leave empty if at the root of the domain)
    define('APP_BASE_URI', ''); 
}

// Allow for path overrides in a local configuration file (for development)
$local_paths_file = __DIR__ . '/paths.local.php';
if (file_exists($local_paths_file)) {
    include $local_paths_file;
}
