<?php
// Standalone upgrade script for version 1.3.0
// This script should be placed in the 'database' directory and run from the project root.

echo "Starting upgrade to version 1.3.0...\n";

// Define a base path to locate the bootstrap file
define('BASE_PATH', dirname(__DIR__));

// --- Environment and Database Setup ---
// Include the bootstrap file to get the database connection and constants
// We need to define INCLUDED_FROM_UPGRADE_SCRIPT to bypass any routing/output logic.
define('INCLUDED_FROM_UPGRADE_SCRIPT', true);
$bootstrap_path = BASE_PATH . '/public/includes/bootstrap.php';

if (!file_exists($bootstrap_path)) {
    die("Error: Could not find bootstrap file at: {$bootstrap_path}. Make sure you are running this script from the project root.\n");
}
require_once $bootstrap_path;

// Check if the database connection was successful
if (!$conn) {
    die("Error: Database connection failed. Please check your .env configuration.\n");
}

// --- Migration ---
echo "Attempting to create 'contract_history' table...\n";

// The table name is already defined with its prefix in bootstrap.php via config/database.php
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