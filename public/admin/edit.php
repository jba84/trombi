<?php
ob_start(); // Start output buffering

// Define constant to indicate this is an admin page
define('INCLUDED_FROM_ADMIN_PAGE', true);

// Include admin head for initialization
require_once '../includes/admin_head.php';

// Assure-toi que $conn est bien une connexion PDO
if (!$conn instanceof PDO) {
    // Si admin_head n'a pas chargé la bonne connexion, on force le chargement via la nouvelle fonction
    $conn = getDBConnection();
}

// Get departments & companies (Functions now use PDO, so this works)
$departments = get_all_departments($conn);
$companies = get_all_companies($conn);

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id']; // Cast to int for safety

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $company_id = sanitize_input($_POST['company_id']);
    $department_id = sanitize_input($_POST['department_id']);
    $job_title = sanitize_input($_POST['job_title']);
    $email = sanitize_input($_POST['email']);
    
    // Handle optional fields (Phone & Dates)
    $phone_number = !empty($_POST['phone_number']) ? sanitize_input($_POST['phone_number']) : null;
    $contract_start_date = !empty($_POST['contract_start_date']) ? sanitize_input($_POST['contract_start_date']) : null;
    $contract_end_date = !empty($_POST['contract_end_date']) ? sanitize_input($_POST['contract_end_date']) : null;

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($company_id) || empty($department_id) || empty($job_title) || empty($email)) {
        set_session_message('error_message', __('all_fields_required'));
        set_form_data($_POST);
        header("Location: edit.php?id=" . $id);
        exit;
    } else {
        // Fetch current staff data
        $current_staff = get_staff_member_by_id($conn, $id);
        
        if (!$current_staff) {
            set_session_message('error_message', __('staff_not_found'));
            header("Location: index.php");
            exit;
        }

        // --- CORRECTION : Logique d'archivage mise à jour pour PDO ---
        // On vérifie si les dates ont changé
        $old_start = $current_staff['contract_start_date'] ?? null;
        $old_end = $current_staff['contract_end_date'] ?? null;

        if ($old_start != $contract_start_date || $old_end != $contract_end_date) {
            // On appelle la nouvelle signature de la fonction (Connexion, ID, Start, End)
            archive_contract($conn, $id, $old_start, $old_end);
        }

        $profile_picture = $current_staff['profile_picture'] ?? null;

        // 1. Handle File Upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $upload_result = upload_profile_picture($_FILES['profile_picture']);

            if ($upload_result['success']) {
                // Delete old picture
                if ($profile_picture) {
                    $old_picture_path = PUBLIC_PATH . "/uploads/" . basename($profile_picture);
                    if (file_exists($old_picture_path)) {
                        @unlink($old_picture_path);
                    }
                }
                $profile_picture = $upload_result['filename'];
            } else {
                set_session_message('error_message', $upload_result['message']);
                set_form_data($_POST);
                header("Location: edit.php?id=" . $id);
                exit;
            }
        }
        // 2. Handle Image Deletion
        else if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
            if ($profile_picture) {
                $old_picture_path = PUBLIC_PATH . "/uploads/" . basename($profile_picture);
                if (file_exists($old_picture_path)) {
                    @unlink($old_picture_path);
                }
            }
            $profile_picture = NULL;
        }

        // --- CORRECTION MAJEURE : Requete UPDATE version PDO ---
        // Note: On utilise 'phone_number' pour correspondre à ton ALTER TABLE, 
        // même si le functions.php de Jules mentionnait 'phone'.
        
        $sql = "UPDATE " . TABLE_STAFF_MEMBERS . " SET
                first_name = :first_name,
                last_name = :last_name,
                company_id = :company_id,
                department_id = :department_id,
                job_title = :job_title,
                email = :email,
                phone_number = :phone_number, 
                contract_start_date = :contract_start_date,
                contract_end_date = :contract_end_date,
                profile_picture = :profile_picture
                WHERE id = :id";

        try {
            $stmt = $conn->prepare($sql);
            
            // Exécution avec un tableau associatif (plus propre que bind_param)
            $result = $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':company_id' => $company_id,
                ':department_id' => $department_id,
                ':job_title' => $job_title,
                ':email' => $email,
                ':phone_number' => $phone_number,
                ':contract_start_date' => $contract_start_date,
                ':contract_end_date' => $contract_end_date,
                ':profile_picture' => $profile_picture,
                ':id' => $id
            ]);

            if ($result) {
                if (isset($_SESSION['form_data'])) {
                     unset($_SESSION['form_data']);
                }
                set_session_message('success_message', __('staff_updated'));
                header("Location: index.php?updated=1");
                exit;
            }
        } catch (PDOException $e) {
            set_session_message('error_message', __('error_updating_staff') . ": " . $e->getMessage());
            set_form_data($_POST);
            header("Location: edit.php?id=" . $id);
            exit;
        }
    }
}

// --- Fetch data for displaying the form ---
$staff = get_staff_member_by_id($conn, $id);

if (!$staff) {
    set_session_message('error_message', __('staff_not_found'));
    header("Location: index.php");
    exit;
}

$error_message = get_session_message('error_message');
$success_message = get_session_message('success_message');
$form_data_from_session = get_form_data();

$form_data = !empty($form_data_from_session) ? $form_data_from_session : $staff;

// Gestion de la compatibilité des noms de champs (DB vs Formulaire)
// Si la DB renvoie 'contract_start_date' (ancien nom) ou 'start_date' (nouveau nom potentiel)
$val_start_date = $form_data['contract_start_date'] ?? $form_data['start_date'] ?? '';
$val_end_date = $form_data['contract_end_date'] ?? $form_data['end_date'] ?? '';
$val_phone = $form_data['phone_number'] ?? $form_data['phone'] ?? '';
$val_photo = $form_data['profile_picture'] ?? $form_data['photo'] ?? '';

$has_real_picture = !empty($val_photo);

require_once '../includes/admin_header.php';
?>

<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow-md">

    <h1 class="text-2xl font-semibold mb-5 text-gray-700"><?php echo __('edit_staff_member'); ?>: <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h1>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('first_name'); ?></label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>

            <div class="form-group">
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('last_name'); ?></label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('company'); ?></label>
            <select id="company_id" name="company_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value=""><?php echo __('select_company'); ?></option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>"
                            <?php echo (isset($form_data['company_id']) && $form_data['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group mb-4">
            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('department'); ?></label>
            <select id="department_id" name="department_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value=""><?php echo __('select_department'); ?></option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"
                            data-color="<?php echo htmlspecialchars($dept['color']); ?>"
                            <?php echo (isset($form_data['department_id']) && $form_data['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="department-color-preview" class="mt-2" style="display: none;"></div>
        </div>

         <div class="form-group mb-4">
            <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('job_title'); ?></label>
            <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($form_data['job_title']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('email'); ?></label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="form-group mb-4">
            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('phone_number'); ?></label>
            <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($val_phone); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="form-group">
                <label for="contract_start_date" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('contract_start_date'); ?></label>
                <input type="date" id="contract_start_date" name="contract_start_date" value="<?php echo htmlspecialchars($val_start_date); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
            <div class="form-group">
                <label for="contract_end_date" class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('contract_end_date'); ?></label>
                <input type="date" id="contract_end_date" name="contract_end_date" value="<?php echo htmlspecialchars($val_end_date); ?>" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            </div>
        </div>
        <div class="form-group mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo __('profile_picture'); ?></label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="sr-only dropzone-input">
            <input type="hidden" name="delete_image" id="delete_image_flag" value="0">

            <label for="profile_picture" id="dropzone" class="dropzone mb-4 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-300">
                <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600">
                        <span class="relative bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                            <?php echo __('upload_new_file'); ?>
                        </span>
                        <p class="pl-1"><?php echo __('drag_drop_replace'); ?></p>
                    </div>
                    <p class="text-xs text-gray-500"><?php echo __('image_formats'); ?></p>
                </div>
            </label>

             <div class="flex justify-center">
                 <div class="relative">
                     <?php
                     // Logique simplifiée pour l'affichage de l'image
                     // get_staff_image_url doit être adapté si possible, sinon on utilise le chemin brut
                     $display_url = !empty($val_photo) ? asset('uploads/' . $val_photo) : asset('images/default-avatar.png'); 
                     // NOTE: Si get_staff_image_url fonctionne toujours dans functions.php, utilisez-le ici.
                     ?>
                     <img id="image-preview"
                          src="<?php echo $display_url; ?>"
                          alt="<?php echo __('current_picture'); ?>"
                          class="w-[150px] h-[150px] rounded-lg bg-gray-100 object-cover">

                     <button type="button" id="remove-image"
                             class="absolute -top-2 -right-2 bg-gray-800 text-white rounded-full p-1.5 shadow-sm hover:bg-gray-700"
                             data-update-field="delete_image" data-update-value="1"
                             style="display: <?php echo $has_real_picture ? 'flex' : 'none'; ?>">
                         <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                         </svg>
                     </button>
                 </div>
             </div>
        </div>

        <div class="form-actions flex justify-end gap-3 mt-6 border-t border-gray-200 pt-4">
             <a href="history.php?id=<?php echo $id; ?>" class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Contract History</a>
            <a href="index.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"><?php echo __('cancel'); ?></a>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"><?php echo __('update_staff_member'); ?></button>
        </div>
    </form>
</div>

<script>
window.translations = {
    selected: "<?php echo __('selected'); ?>",
    uploadImageFile: "<?php echo __('upload_image_file'); ?>",
    fileTooLarge: "<?php echo __('file_too_large'); ?>"
};
</script>
<script src="../assets/js/staff-form-utils.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id')
    const colorPreview = document.getElementById('department-color-preview')
    const initialDeptId = "<?php echo isset($form_data['department_id']) ? $form_data['department_id'] : ''; ?>"
    
    if (initialDeptId) {
        departmentSelect.value = initialDeptId
        if(typeof updateDepartmentColorPreview === 'function') {
             updateDepartmentColorPreview(departmentSelect, colorPreview)
        }
    }
    departmentSelect.addEventListener('change', function() {
        if(typeof updateDepartmentColorPreview === 'function') {
            updateDepartmentColorPreview(departmentSelect, colorPreview)
        }
    })
})
</script>

<?php
require_once '../includes/admin_footer.php';
ob_end_flush();
?>