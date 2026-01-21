<?php

/**
 * Core utility functions for the Staff Directory application.
 *
 * This file includes functions for URL generation, input sanitization,
 * database interactions, and other essential tasks.
 */

// Ensure the functions are not declared more than once.
if (!function_exists('url')) {
    /**
     * Generates a full URL for a given path.
     * Respects the current scheme (http/https).
     *
     * @param string $path The path to append to the base URL.
     * @return string The full URL.
     */
    function url(string $path = ''): string
    {
        // Ensure the path has a leading slash
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }

        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        return htmlspecialchars($scheme . '://' . $host . $path, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize_input')) {
    /**
     * Sanitizes user input to prevent XSS attacks.
     *
     * @param string|null $input The input string to sanitize.
     * @return string The sanitized string.
     */
    function sanitize_input(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Fetches all staff members with their company and department details.
 *
 * @param PDO $conn The database connection object.
 * @return array An array of staff members.
 */
function get_all_staff(PDO $conn): array
{
    $stmt = $conn->query("SELECT s.*, c.name AS company_name, d.name AS department_name 
                            FROM staff s 
                            LEFT JOIN companies c ON s.company_id = c.id 
                            LEFT JOIN departments d ON s.department_id = d.id
                            ORDER BY s.last_name, s.first_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches a single staff member by their ID.
 * CORRECTED: This version now fetches contract_start_date and contract_end_date.
 *
 * @param PDO $conn The database connection object.
 * @param int $id The ID of the staff member.
 * @return array|false The staff member's data or false if not found.
 */
function get_staff_member_by_id(PDO $conn, int $id)
{
    $stmt = $conn->prepare("SELECT s.id, s.first_name, s.last_name, s.email, s.phone, s.job_title, s.company_id, s.department_id, s.photo, s.contract_start_date, s.contract_end_date, c.name AS company_name, d.name AS department_name 
                            FROM staff s 
                            LEFT JOIN companies c ON s.company_id = c.id 
                            LEFT JOIN departments d ON s.department_id = d.id 
                            WHERE s.id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/**
 * Fetches all companies from the database.
 *
 * @param PDO $conn The database connection object.
 * @return array An array of companies.
 */
function get_all_companies(PDO $conn): array
{
    $stmt = $conn->query("SELECT * FROM companies ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches all departments from the database.
 *
 * @param PDO $conn The database connection object.
 * @return array An array of departments.
 */
function get_all_departments(PDO $conn): array
{
    $stmt = $conn->query("SELECT * FROM departments ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gets the name of a company by its ID.
 *
 * @param PDO $conn The database connection object.
 * @param int $company_id The ID of the company.
 * @return string|null The company name or null if not found.
 */
function get_company_name_by_id(PDO $conn, int $company_id): ?string
{
    $stmt = $conn->prepare("SELECT name FROM companies WHERE id = :id");
    $stmt->execute([':id' => $company_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : null;
}

/**
 * Gets the name of a department by its ID.
 *
 * @param PDO $conn The database connection object.
 * @param int $department_id The ID of the department.
 * @return string|null The department name or null if not found.
 */
function get_department_name_by_id(PDO $conn, int $department_id): ?string
{
    $stmt = $conn->prepare("SELECT name FROM departments WHERE id = :id");
    $stmt->execute([':id' => $department_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : null;
}

/**
 * Counts the number of staff members assigned to a specific company.
 *
 * @param PDO $conn The database connection object.
 * @param int $company_id The ID of the company.
 * @return int The number of staff members.
 */
function get_staff_count_by_company_id(PDO $conn, int $company_id): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM staff WHERE company_id = :company_id");
    $stmt->execute([':company_id' => $company_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Counts the number of staff members assigned to a specific department.
 *
 * @param PDO $conn The database connection object.
 * @param int $department_id The ID of the department.
 * @return int The number of staff members.
 */
function get_staff_count_by_department_id(PDO $conn, int $department_id): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM staff WHERE department_id = :department_id");
    $stmt->execute([':department_id' => $department_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Archives a staff member's contract details into the history table.
 * NEW: This function was missing and is now added.
 *
 * @param PDO $conn The database connection object.
 * @param int $staff_id The ID of the staff member.
 * @param string|null $start_date The start date of the contract.
 * @param string|null $end_date The end date of the contract.
 * @return bool True on success, false on failure.
 */
function archive_contract(PDO $conn, int $staff_id, ?string $start_date, ?string $end_date): bool
{
    // Only archive if there's a start date to record.
    if (empty($start_date)) {
        return true; // Nothing to archive, but not an error.
    }

    $sql = "INSERT INTO contract_history (staff_id, start_date, end_date, archived_at) VALUES (:staff_id, :start_date, :end_date, NOW())";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        ':staff_id' => $staff_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date
    ]);
}

/**
 * Singleton pattern for getting the LanguageManager instance.
 *
 * @return LanguageManager The singleton LanguageManager instance.
 */
function getLanguageManager(): Language
