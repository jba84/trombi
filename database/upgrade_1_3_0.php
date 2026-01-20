<?php
// Standalone upgrade script for version 1.3.0
// This script should be placed in the 'database' directory and run from the project root.

// --- Setup Pathing and Configuration ---

// Define a base path to locate the necessary include files.
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// 1. Include paths configuration first, which is essential.
$paths_file = BASE_PATH . '/public/includes/paths.php';
if (!file_exists($paths_file)) {
    die("Error: Critical file not found: {$paths_file}. Please ensure paths are correct and run from the project root.\n");
}
require_once $paths_file;

// 2. Define a constant to signal to bootstrap.php that this is a command-line script.
// This can be used to prevent actions like starting sessions or sending headers.
if (!defined('INCLUDED_FROM_UPGRADE_SCRIPT')) {
    define('INCLUDED_FROM_UPGRADE_SCRIPT', true);
}

// 3. Now, include the main bootstrap file which sets up the database connection.
$bootstrap_path = PUBLIC_PATH . '/includes/bootstrap.php';
if (!file_exists($bootstrap_path)) {
    die("Error: Critical file not found: {$bootstrap_path}.\n");
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