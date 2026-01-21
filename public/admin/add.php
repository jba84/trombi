<?php
require_once '../includes/admin_head.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $job_title = sanitize_input($_POST['job_title']);
    $department_id = (int)$_POST['department_id'];
    $company_id = (int)$_POST['company_id'];
    
    // Upload image simple
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], PUBLIC_PATH . '/uploads/' . $new_name)) {
                $profile_picture = $new_name;
            }
        }
    }

    $sql = "INSERT INTO staff_members (first_name, last_name, email, job_title, department_id, company_id, profile_picture) 
            VALUES (:fn, :ln, :em, :jt, :di, :ci, :pp)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':fn' => $first_name, ':ln' => $last_name, ':em' => $email,
            ':jt' => $job_title, ':di' => $department_id, ':ci' => $company_id,
            ':pp' => $profile_picture
        ]);
        // Redirection après succès
        echo "<script>window.location.href = 'index.php';</script>";
        exit;
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

$companies = get_all_companies($conn);
$departments = get_all_departments($conn);
?>

<h2 class="text-2xl font-bold text-gray-800 mb-6">Ajouter un employé</h2>

<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Prénom</label>
                <input type="text" name="first_name" required class="w-full border rounded px-3 py-2 text-gray-700 focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Nom</label>
                <input type="text" name="last_name" required class="w-full border rounded px-3 py-2 text-gray-700 focus:outline-none focus:border-indigo-500">
            </div>
        </div>
        
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input type="email" name="email" required class="w-full border rounded px-3 py-2 text-gray-700">
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Poste</label>
            <input type="text" name="job_title" required class="w-full border rounded px-3 py-2 text-gray-700">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Entreprise</label>
                <select name="company_id" class="w-full border rounded px-3 py-2 bg-white">
                    <?php foreach($companies as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Département</label>
                <select name="department_id" class="w-full border rounded px-3 py-2 bg-white">
                    <?php foreach($departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">Photo</label>
            <input type="file" name="profile_picture" class="w-full text-gray-500">
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="index.php" class="bg-gray-200 text-gray-700 py-2 px-4 rounded hover:bg-gray-300">Annuler</a>
            <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">Enregistrer</button>
        </div>
    </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
