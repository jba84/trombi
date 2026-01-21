<?php
/**
 * Core utility functions - PDO & PHP 8.4 Ready
 */

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $path = ltrim($path, '/');
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return htmlspecialchars($scheme . '://' . $host . '/' . $path, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input(?string $input): string {
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }
}

// --- DATABASE FUNCTIONS ---

function get_all_staff_members(PDO $conn, string $sort_by = 'last_name', string $sort_order = 'ASC', string $search = '', string $department = '', string $company = ''): array {
    $params = [];
    $sql = "SELECT s.*, c.name AS company, d.name AS department, d.color AS department_color, c.logo AS company_logo
            FROM staff_members s 
            LEFT JOIN companies c ON s.company_id = c.id 
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.job_title LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($department)) {
        $sql .= " AND d.name = :dept";
        $params[':dept'] = $department;
    }
    if (!empty($company)) {
        $sql .= " AND c.name = :comp";
        $params[':comp'] = $company;
    }

    $allowed_sort = ['first_name', 'last_name', 'department', 'company'];
    $sort_column = in_array($sort_by, $allowed_sort) ? $sort_by : 'last_name';
    $order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

    $sql .= " ORDER BY $sort_column $order";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_staff_member_by_id(PDO $conn, int $id) {
    $stmt = $conn->prepare("SELECT s.*, c.name AS company_name, d.name AS department_name 
                            FROM staff_members s 
                            LEFT JOIN companies c ON s.company_id = c.id 
                            LEFT JOIN departments d ON s.department_id = d.id 
                            WHERE s.id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function get_active_department_names(PDO $conn): array {
    return $conn->query("SELECT DISTINCT d.name FROM departments d JOIN staff_members s ON d.id = s.department_id ORDER BY d.name")->fetchAll(PDO::FETCH_COLUMN);
}

function get_active_company_names(PDO $conn): array {
    return $conn->query("SELECT DISTINCT c.name FROM companies c JOIN staff_members s ON c.id = s.company_id ORDER BY c.name")->fetchAll(PDO::FETCH_COLUMN);
}

function get_all_companies(PDO $conn): array {
    return $conn->query("SELECT * FROM companies ORDER BY name")->fetchAll();
}

function get_all_departments(PDO $conn): array {
    return $conn->query("SELECT * FROM departments ORDER BY name")->fetchAll();
}

function archive_contract(PDO $conn, int $staff_id, ?string $start_date, ?string $end_date): bool {
    if (empty($start_date)) return true;
    $stmt = $conn->prepare("INSERT INTO contract_history (staff_id, start_date, end_date, archived_at) VALUES (:id, :s, :e, NOW())");
    return $stmt->execute([':id' => $staff_id, ':s' => $start_date, ':e' => $end_date]);
}

// --- SYSTEM ---

function getDBConnection(): PDO {
    static $conn = null;
    if ($conn === null) {
        require_once dirname(__DIR__) . '/config/database.php';
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $conn;
}

function get_staff_image_url($staff, $size = '150x150') {
    $photo = $staff['profile_picture'] ?? $staff['photo'] ?? null;
    return (!empty($photo) && file_exists(PUBLIC_PATH . "/uploads/" . $photo)) ? asset('uploads/' . $photo) : null;
}

function get_text_contrast_class($hex) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
    return ((($r * 299) + ($g * 587) + ($b * 114)) / 1000) > 190 ? 'dark-text' : 'light-text';
}

function set_session_message($k, $v) { if (session_status() === PHP_SESSION_NONE) session_start(); $_SESSION[$k] = $v; }
function get_session_message($k) { if (session_status() === PHP_SESSION_NONE) session_start(); $m = $_SESSION[$k] ?? ''; unset($_SESSION[$k]); return $m; }
function set_form_data($d) { if (session_status() === PHP_SESSION_NONE) session_start(); $_SESSION['form_data'] = $d; }
function get_form_data() { if (session_status() === PHP_SESSION_NONE) session_start(); $d = $_SESSION['form_data'] ?? []; unset($_SESSION['form_data']); return $d; }