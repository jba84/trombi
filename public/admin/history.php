<?php
define('INCLUDED_FROM_ADMIN_PAGE', true);
require_once '../includes/admin_head.php';

$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$staff_id) {
    set_session_message('error_message', 'Invalid staff ID.');
    header('Location: index.php');
    exit;
}

$staff_member = get_staff_member_by_id($conn, $staff_id);

if (!$staff_member) {
    set_session_message('error_message', 'Staff member not found.');
    header('Location: index.php');
    exit;
}

// Handle contract purge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge_date'])) {
    $purge_date = sanitize_input($_POST['purge_date']);
    if (!empty($purge_date)) {
        $purged_count = purge_contract_history($conn, $staff_id, $purge_date);
        if ($purged_count !== false) {
            set_session_message('success_message', "$purged_count contracts purged successfully before $purge_date.");
        } else {
            set_session_message('error_message', 'Error purging contracts.');
        }
        header('Location: history.php?id=' . $staff_id);
        exit;
    }
}

$history = get_contract_history($conn, $staff_id);

require_once '../includes/admin_header.php';
?>

<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow-md">
    <h1 class="text-2xl font-semibold mb-5 text-gray-700">Contract History for <?php echo htmlspecialchars($staff_member['first_name'] . ' ' . $staff_member['last_name']); ?></h1>

    <?php $error_message = get_session_message('error_message');
    if ($error_message): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <?php $success_message = get_session_message('success_message');
    if ($success_message): ?>
        <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="mb-6">
        <form action="history.php?id=<?php echo $staff_id; ?>" method="POST" class="flex items-center gap-4">
            <div>
                <label for="purge_date" class="block text-sm font-medium text-gray-700">Purge contracts before:</label>
                <input type="date" id="purge_date" name="purge_date" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Purge</button>
        </form>
    </div>

    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th class="py-2 px-4 border-b">Company</th>
                <th class="py-2 px-4 border-b">Department</th>
                <th class="py-2 px-4 border-b">Job Title</th>
                <th class="py-2 px-4 border-b">Start Date</th>
                <th class="py-2 px-4 border-b">End Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($history)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4">No contract history found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($history as $record): ?>
                    <tr>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['company_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['department_name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['job_title']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars(date('d/m/Y', strtotime($record['start_date']))); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($record['end_date'] ? date('d/m/Y', strtotime($record['end_date'])) : 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-6">
        <a href="index.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition duration-150 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-300">Back to Staff List</a>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
