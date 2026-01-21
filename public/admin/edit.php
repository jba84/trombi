<?php
ob_start();
define('INCLUDED_FROM_ADMIN_PAGE', true);
require_once '../includes/admin_head.php';

$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        ':fn' => sanitize_input($_POST['first_name']),
        ':ln' => sanitize_input($_POST['last_name']),
        ':ci' => (int)$_POST['company_id'],
        ':di' => (int)$_POST['department_id'],
        ':jt' => sanitize_input($_POST['job_title']),
        ':em' => sanitize_input($_POST['email']),
        ':ph' => !empty($_POST['phone_number']) ? sanitize_input($_POST['phone_number']) : null,
        ':sd' => !empty($_POST['contract_start_date']) ? $_POST['contract_start_date'] : null,
        ':ed' => !empty($_POST['contract_end_date']) ? $_POST['contract_end_date'] : null,
        ':id' => $id
    ];

    $current = get_staff_member_by_id($conn, $id);
    if ($current['contract_start_date'] != $data[':sd']) {
        archive_contract($conn, $id, $current['contract_start_date'], $current['contract_end_date']);
    }

    $sql = "UPDATE staff_members SET first_name=:fn, last_name=:ln, company_id=:ci, department_id=:di, job_title=:jt, email=:em, phone_number=:ph, contract_start_date=:sd, contract_end_date=:ed WHERE id=:id";
    if ($conn->prepare($sql)->execute($data)) {
        set_session_message('success_message', "Mis à jour avec succès");
        header("Location: index.php"); exit;
    }
}

$staff = get_staff_member_by_id($conn, $id);
$companies = get_all_companies($conn);
$departments = get_all_departments($conn);
require_once '../includes/admin_header.php';
?>

<div class="max-w-2xl mx-auto bg-white p-8 rounded shadow">
    <form method="POST" action="edit.php?id=<?php echo $id; ?>">
        <div class="grid grid-cols-2 gap-4">
            <input type="text" name="first_name" value="<?php echo $staff['first_name']; ?>" class="border p-2 rounded">
            <input type="text" name="last_name" value="<?php echo $staff['last_name']; ?>" class="border p-2 rounded">
        </div>
        <select name="company_id" class="w-full border p-2 mt-4 rounded">
            <?php foreach($companies as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $staff['company_id'] ? 'selected' : ''); ?>><?php echo $c['name']; ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="phone_number" value="<?php echo $staff['phone_number']; ?>" placeholder="Téléphone" class="w-full border p-2 mt-4 rounded">
        <div class="grid grid-cols-2 gap-4 mt-4">
            <input type="date" name="contract_start_date" value="<?php echo $staff['contract_start_date']; ?>" class="border p-2 rounded">
            <input type="date" name="contract_end_date" value="<?php echo $staff['contract_end_date']; ?>" class="border p-2 rounded">
        </div>
        <button type="submit" class="w-full bg-indigo-600 text-white p-2 mt-6 rounded">Sauvegarder</button>
    </form>
</div>
<?php require_once '../includes/admin_footer.php'; ob_end_flush(); ?>