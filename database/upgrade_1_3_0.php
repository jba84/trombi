<?php
// Standalone upgrade script for version 1.3.0
// This script is designed to be run from the project's root directory.

// --- Setup Pathing and Configuration ---

// 1. Manually define all required path constants for robustness.
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}
if (!defined('PRIVATE_PATH')) {
    define('PRIVATE_PATH', BASE_PATH);
}

// 2. Define a constant to signal this is a command-line script.
if (!defined('INCLUDED_FROM_UPGRADE_SCRIPT')) {
    define('INCLUDED_FROM_UPGRADE_SCRIPT', true);
}

// 3. Include the bootstrap file to get the database connection and configuration.
$bootstrap_path = PUBLIC_PATH . '/includes/bootstrap.php';
if (!file_exists($bootstrap_path)) {
    die("Error: Critical file not found: {$bootstrap_path}.\n");
}
require_once $bootstrap_path; // This loads .env, db connection, and the $table_prefix variable.

// --- Begin Execution ---

echo "Starting upgrade to version 1.3.0...\n";

// Check if the database connection was successful
if (!$conn) {
    die("Error: Database connection failed. Please check your .env configuration.\n");
}

// --- Migration ---
echo "Attempting to create 'contract_history' table...\n";

// **Robustness fix:**
// Instead of relying on a constant that might not exist in older code,
// we construct the table name manually using the prefix from the loaded config.
// The $table_prefix variable is defined in config/database.php, which was loaded by bootstrap.php.
$tableName = ($table_prefix ?? '') . 'contract_history';

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