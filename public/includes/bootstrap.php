<?php

/**
 * Main application bootstrap file.
 *
 * This file handles session initialization, error reporting,
 * and includes the core functions. It's the central point
 * of setup for the entire application.
 */

// Start the session to handle messages and user state.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for development.
// In a production environment, you would log errors instead of displaying them.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// --- FINAL BOOTSTRAP ORDER ---

// 1. Define core path constants.
require_once __DIR__ . '/paths.php';

// 2. Load the Composer autoloader. This is crucial for all external libraries.
if (file_exists(PRIVATE_PATH . '/vendor/autoload.php')) {
    require_once PRIVATE_PATH . '/vendor/autoload.php';
} else {
    // Provide a clear error message if dependencies are not installed.
    die("<h1>Composer autoloader not found.</h1><p>Please run <code>composer install</code> in the project root to install dependencies.</p>");
}

// 3. Load core application classes that are not managed by Composer.
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/LanguageManager.php';


// 4. Include the core application helper functions, which depend on the classes above.
require_once __DIR__ . '/functions.php';

// 5. Create the global database connection using the now-available functions.
$conn = getDBConnection();
