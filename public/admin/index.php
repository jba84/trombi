<?php
// 1. Initialisation
require_once '../includes/admin_head.php';

// 2. Gestion de la suppression (si demandée)
if (isset($_POST['delete_id'])) {
    $id_to_delete = (int)$_POST['delete_id'];
    try {
        // On récupère l'employé pour supprimer sa photo si elle existe
        $staff = get_staff_member_by_id($conn, $id_to_delete);
        if ($staff && !empty($staff['profile_picture'])) {
            $photo_path = PUBLIC_PATH . '/uploads/' . $staff['profile_picture'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        // Suppression en BDD
        $stmt = $conn->prepare("DELETE FROM staff_members WHERE id = :id");
        $stmt->execute([':id' => $id_to_delete]);
        
        echo '<div class="bg-green-100 text-green-700 p-4 mb-4 rounded">Employé supprimé avec succès.</div>';
    } catch (PDOException $e) {
        echo '<div class="bg-red-100 text-red-700 p-4 mb-4 rounded">Erreur : ' . $e->getMessage() . '</div>';
    }
}

// 3. Récupération des données
// On récupère tout le monde (sans filtre par défaut pour l'admin)
$staff_members = get_all_staff_members($conn);
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Tableau de bord</h2>
    <a href="add.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
        <i class="ri-add-line mr-2"></i> Ajouter un employé
    </a>
</div>

<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <table class="min-w-full leading-normal">
        <thead>
            <tr>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Employé
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Poste & Département
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Contact
                </th>
                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staff_members as $staff): ?>
            <tr>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10">
                            <?php 
                                $img = get_staff_image_url($staff);
                                if($img): 
                            ?>
                                <img class="w-full h-full rounded-full object-cover" src="<?php echo $img; ?>" alt="" />
                            <?php else: ?>
                                <div class="w-full h-full rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-bold">
                                    <?php echo strtoupper(substr($staff['first_name'],0,1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-gray-900 whitespace-no-wrap font-semibold">
                                <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                            </p>
                            <p class="text-gray-500 whitespace-no-wrap text-xs">
                                <?php echo htmlspecialchars($staff['company']); ?>
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($staff['job_title']); ?></p>
                    <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                        <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                        <span class="relative"><?php echo htmlspecialchars($staff['department']); ?></span>
                    </span>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <p class="text-gray-900 whitespace-no-wrap text-xs"><?php echo htmlspecialchars($staff['email']); ?></p>
                    <p class="text-gray-500 whitespace-no-wrap text-xs"><?php echo htmlspecialchars($staff['phone_number'] ?? ''); ?></p>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                    <div class="flex items-center space-x-4">
                        <a href="edit.php?id=<?php echo $staff['id']; ?>" class="text-indigo-600 hover:text-indigo-900 font-medium">Modifier</a>
                        
                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?');" class="inline">
                            <input type="hidden" name="delete_id" value="<?php echo $staff['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium bg-transparent border-none cursor-pointer">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
