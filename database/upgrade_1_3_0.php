<?php
// Standalone upgrade script for version 1.3.0
// This script is designed to be run from the project's root directory.

// --- Setup Pathing and Configuration ---

// 1. Manually define core paths for robustness. 
//    This avoids issues with including files from a command-line context.
if (!defined('BASE_PATH')) {
    // __DIR__ is the directory of this script (/database), so dirname(__DIR__) is the project root.
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}

// 2. Define a constant to signal to bootstrap.php that this is a command-line script.
//    This should prevent it from trying to start a session.
if (!defined('INCLUDED_FROM_UPGRADE_SCRIPT')) {
    define('INCLUDED_FROM_UPGRADE_SCRIPT', true);
}

// 3. Now, locate and include the main bootstrap file which sets up the database connection.
$bootstrap_path = PUBLIC_PATH . '/includes/bootstrap.php';
if (!file_exists($bootstrap_path)) {
    die("Error: Critical file not found: {$bootstrap_path}. Please ensure you are running the script from your project's root directory.\n");
}
require_once $bootstrap_path;

// --- Begin Execution ---

echo "Starting upgrade to version 1.3.0...\n";

// Check if the database connection was successful
if (!$conn) {
    die("Error: Database connection failed. Please check your .env configuration.\n");
}

// --- Migration ---
echo "Attempting to create 'contract_history' table...\n";

// The table name is already defined with its prefix in bootstrap.php
$tableName = TABLE_CONTRACT_HISTORY;

$sql = "
CREATE TABLE IF NOT EXISTS `{$tableName}` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL,
  `company_id` INT,
  `department_id` INT,
  `job_title` VARCHAR(255),
  `contract_start_date` DATE,
  `contract_end_date` DATE,
  `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === TRUE) {
    echo "'{$tableName}' table created successfully or already exists.\n";
} else {
    die("Error creating table '{$tableName}': " . $conn->error . "\n");
}

// --- Final Message ---
echo "Database migration for version 1.3.0 completed successfully.\n";

$conn->close();
?>