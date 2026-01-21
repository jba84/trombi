<?php
// Charger l'environnement
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';

// Les variables sont déjà dispo via bootstrap.php
$search = sanitize_input($_GET['search'] ?? '');
$department = sanitize_input($_GET['department'] ?? '');
$company = sanitize_input($_GET['company'] ?? '');
$sort_by = sanitize_input($_GET['sort'] ?? 'last_name');
$sort_order = sanitize_input($_GET['order'] ?? 'ASC');

$staff_members = get_all_staff_members($conn, $sort_by, $sort_order, $search, $department, $company);
$active_depts = get_active_department_names($conn);
$active_comps = get_active_company_names($conn);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-thin text-gray-700 mb-8"><?php echo __('staff_directory'); ?></h1>

    <div class="staff-grid grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($staff_members as $staff): ?>
            <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>