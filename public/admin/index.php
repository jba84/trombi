<?php
/**
 * Admin Dashboard
 * Requires authentication to access
 */

// Define constant to indicate this is an admin page (required by admin_head.php)
define('INCLUDED_FROM_ADMIN_PAGE', true);

// Include admin head for initialization, security checks and database connection
require_once '../includes/admin_head.php';

// Get any flash messages stored in session
$error_message = get_session_message('error_message');
$success_message = get_session_message('success_message');

// Handle single delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = sanitize_input($_GET['delete']);
    $staff_member = get_staff_member_by_id($conn, $id);
    if ($staff_member) {
        archive_contract($conn, $id, $staff_member['company_id'], $staff_member['department_id'], $staff_member['job_title'], $staff_member['contract_start_date'], $staff_member['contract_end_date']);
        if (delete_staff_member($conn, $id)) {
            set_session_message('success_message', __("staff_deleted"));
        } else {
            set_session_message('error_message', __("error_deleting_staff"));
        }
    } else {
        set_session_message('error_message', __("staff_not_found"));
    }
    header("Location: index.php");
    exit;
}

// Handle bulk delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete' && isset($_POST['selected_staff'])) {
    $selected_ids = (array) $_POST['selected_staff'];
    $deleted_count = 0;
    $error_count = 0;

    foreach ($selected_ids as $id) {
        $id = sanitize_input($id);
        $staff_member = get_staff_member_by_id($conn, $id);
        if ($staff_member) {
            archive_contract($conn, $id, $staff_member['company_id'], $staff_member['department_id'], $staff_member['job_title'], $staff_member['contract_start_date'], $staff_member['contract_end_date']);
            if (delete_staff_member($conn, $id)) {
                $deleted_count++;
            } else {
                $error_count++;
            }
        }
    }

    if ($deleted_count > 0) {
        set_session_message('success_message', $deleted_count . ' ' . __('staff_deleted_plural'));
    }
    if ($error_count > 0) {
        set_session_message('error_message', $error_count . ' ' . __('staff_delete_error_plural'));
    }
    header("Location: index.php");
    exit;
}


// Get all staff members
$staff_members = get_all_staff_members($conn);

// Get company statistics
$company_stats = get_all_company_statistics($conn);

// Include the HTML header after all processing is done
require_once '../includes/admin_header.php';
?>

<h1 class="text-2xl font-semibold mb-4 text-gray-700"><?php echo __('staff_members_management'); ?></h1>

<?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success_message; ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error_message; ?></span>
    </div>
<?php endif; ?>

<form method="POST" action="index.php" id="bulk-action-form">
    <div class="admin-actions mb-4 flex items-center gap-4">
        <a href="add.php" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition duration-150 ease-in-out text-sm font-medium">
            <i class="ri-user-add-line mr-1"></i> <?php echo __('add_new_staff_member'); ?>
        </a>
        <div class="bulk-actions-group">
            <select name="bulk_action" id="bulk-action-select" disabled class="rounded px-4 py-2 border border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm bg-gray-100 cursor-not-allowed">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button type="submit" id="bulk-action-submit" disabled class="inline-block bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition duration-150 ease-in-out text-sm font-medium cursor-not-allowed">Apply</button>
        </div>
    </div>

    <!-- Filter controls for admin table -->
    <div class="p-4 border-b border-gray-200 bg-gray-50 mb-0 rounded-t-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="filter-control">
                <label for="admin-search" class="block text-xs font-medium text-gray-500 mb-1"><?php echo __('search'); ?></label>
                <input type="text" id="admin-search" placeholder="<?php echo __('search_admin_placeholder'); ?>" class="w-full rounded px-4 py-2 border border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
            </div>
            <div class="filter-control">
                <label for="admin-department-filter" class="block text-xs font-medium text-gray-500 mb-1"><?php echo __('department'); ?></label>
                <select id="admin-department-filter" class="w-full rounded px-4 py-2 border border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm bg-white">
                    <option value=""><?php echo __('all_departments'); ?></option>
                    <?php
                    $departments = get_active_department_names($conn);
                    foreach ($departments as $dept) {
                        echo "<option value=\"{$dept}\">{$dept}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="filter-control">
                <label for="admin-company-filter" class="block text-xs font-medium text-gray-500 mb-1"><?php echo __('company'); ?></label>
                <select id="admin-company-filter" class="w-full rounded px-4 py-2 border border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm bg-white">
                    <option value=""><?php echo __('all_companies'); ?></option>
                    <?php
                    $companies = get_active_company_names($conn);
                    foreach ($companies as $company) {
                        echo "<option value=\"{$company}\">{$company}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto bg-white shadow rounded-b-lg">
        <table class="staff-table w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3">
                        <input type="checkbox" id="select-all-checkbox">
                    </th>
                    <th scope="col" class="px-4 py-3"><?php echo __('photo'); ?></th>
                    <th scope="col" class="px-4 py-3"><?php echo __('name'); ?></th>
                    <th scope="col" class="px-4 py-3"><?php echo __('company'); ?></th>
                    <th scope="col" class="px-4 py-3"><?php echo __('department'); ?></th>
                    <th scope="col" class="px-4 py-3"><?php echo __('job_title'); ?></th>
                    <th scope="col" class="px-4 py-3"><?php echo __('email'); ?></th>
                    <th scope="col" class="px-4 py-3"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody id="admin-staff-table-body">
                <?php if (count($staff_members) > 0): ?>
                    <?php foreach ($staff_members as $staff): ?>
                        <tr class="border-b hover:bg-gray-50" style="--dept-color: <?php echo htmlspecialchars($staff['department_color']); ?>">
                            <td class="px-4 py-2">
                                <input type="checkbox" name="selected_staff[]" value="<?php echo $staff['id']; ?>" class="staff-checkbox">
                            </td>
                            <td class="px-4 py-2">
                                <img src="<?php echo get_staff_image_url($staff, '50x50', null, $staff['department_color']); ?>"
                                     alt="<?php echo $staff['first_name']; ?>"
                                     class="h-8 w-8 rounded-full object-cover object-top">
                            </td>
                            <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">
                                <?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?>
                            </td>
                            <td class="px-4 py-2">
                                <?php if (!empty($staff['company_logo'])): ?>
                                    <div class="flex items-center gap-2">
                                        <img src="<?php echo url($staff['company_logo']); ?>"
                                             alt="<?php echo $staff['company']; ?> logo"
                                             class="h-4 w-auto">
                                        <span><?php echo $staff['company']; ?></span>
                                    </div>
                                <?php else: ?>
                                    <?php echo $staff['company']; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2">
                                <?php
                                $text_class = get_text_contrast_class($staff['department_color']);
                                ?>
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?php echo $text_class; ?>"
                                      style="background-color: <?php echo $staff['department_color']; ?>">
                                    <?php echo $staff['department']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-2"><?php echo $staff['job_title']; ?></td>
                            <td class="px-4 py-2"><?php echo $staff['email']; ?></td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <a href="edit.php?id=<?php echo $staff['id']; ?>"
                                   class="icon-link inline-flex justify-center items-center h-8 w-8 rounded-lg border border-indigo-200 text-indigo-400 hover:text-indigo-600 hover:border-indigo-300 transition-colors duration-200 mr-1">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <a href="index.php?delete=<?php echo $staff['id']; ?>"
                                   class="icon-link inline-flex justify-center items-center h-8 w-8 rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:border-red-300 transition-colors duration-200"
                                   onclick="return confirm('<?php echo __('confirm_delete_staff'); ?>');">
                                    <i class="ri-delete-bin-line"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500"><?php echo __('no_staff_found'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const staffCheckboxes = document.querySelectorAll('.staff-checkbox');
    const bulkActionSelect = document.getElementById('bulk-action-select');
    const bulkActionSubmit = document.getElementById('bulk-action-submit');
    const bulkActionForm = document.getElementById('bulk-action-form');

    function toggleBulkActions() {
        const anySelected = Array.from(staffCheckboxes).some(c => c.checked);
        bulkActionSelect.disabled = !anySelected;
        bulkActionSubmit.disabled = !anySelected;
        
        if (anySelected) {
            bulkActionSelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
            bulkActionSubmit.classList.remove('cursor-not-allowed');
        } else {
            bulkActionSelect.classList.add('bg-gray-100', 'cursor-not-allowed');
            bulkActionSubmit.classList.add('cursor-not-allowed');
        }
    }

    selectAllCheckbox.addEventListener('change', function () {
        staffCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        toggleBulkActions();
    });

    staffCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            }
            toggleBulkActions();
        });
    });

    bulkActionForm.addEventListener('submit', function (e) {
        if (bulkActionSelect.value === 'delete') {
            if (!confirm('Are you sure you want to delete the selected staff members?')) {
                e.preventDefault();
            }
        }
    });

    toggleBulkActions(); // Initial check
});
</script>

<!-- Core filter module shared between frontend and admin -->
<script src="<?php echo asset('js/filter-core.js'); ?>"></script>
<!-- Include admin-specific filter script -->
<script src="<?php echo asset('js/admin-filters.js'); ?>"></script>

<?php require_once '../includes/admin_footer.php'; ?>
