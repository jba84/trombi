<?php
/**
 * En-tête pour l'administration
 * Gère les dépendances, l'auth et le début du HTML
 */

// 1. Charger le bootstrap (chemins, BDD, etc.)
require_once __DIR__ . '/bootstrap.php';

// 2. CORRECTION : Charger le système d'authentification
// C'est indispensable pour que la fonction is_logged_in() existe !
require_once PUBLIC_PATH . '/admin/auth/auth.php';

// 3. Vérification de sécurité
// Si l'utilisateur n'est pas connecté, on le redirige vers le login
// require_login() est une fonction de auth.php qui gère tout ça
require_login(); 

// 4. Initialisation des variables communes
$conn = getDBConnection();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?php echo current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_panel'); ?> - Staff Directory</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        /* Styles spécifiques admin */
        .sidebar-link.active {
            background-color: #4f46e5;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="min-h-screen flex">
        
        <aside class="w-64 bg-gray-800 text-white flex flex-col flex-shrink-0 transition-all duration-300" id="sidebar">
            <div class="p-4 bg-gray-900 flex items-center justify-between">
                <span class="text-xl font-bold tracking-wider">ADMIN</span>
            </div>
            
            <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto">
                <a href="<?php echo url('admin/index.php'); ?>" class="sidebar-link flex items-center px-4 py-2 rounded-md hover:bg-gray-700 transition <?php echo ($current_page == 'index.php') ? 'active' : 'text-gray-300'; ?>">
                    <i class="ri-dashboard-line mr-3 text-lg"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="<?php echo url('admin/add.php'); ?>" class="sidebar-link flex items-center px-4 py-2 rounded-md hover:bg-gray-700 transition <?php echo ($current_page == 'add.php') ? 'active' : 'text-gray-300'; ?>">
                    <i class="ri-user-add-line mr-3 text-lg"></i>
                    <span><?php echo __('add_staff'); ?></span>
                </a>

                <div class="border-t border-gray-700 my-4"></div>

                <a href="<?php echo url('index.php'); ?>" class="flex items-center px-4 py-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition">
                    <i class="ri-home-line mr-3 text-lg"></i>
                    <span><?php echo __('back_to_site'); ?></span>
                </a>
                
                <a href="<?php echo url('admin/auth/logout.php'); ?>" class="flex items-center px-4 py-2 text-red-400 hover:text-red-300 hover:bg-gray-700 rounded-md transition mt-auto">
                    <i class="ri-logout-box-line mr-3 text-lg"></i>
                    <span><?php echo __('logout'); ?></span>
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="bg-white shadow-sm lg:hidden">
                <div class="px-4 py-3 flex items-center justify-between">
                    <h1 class="text-lg font-semibold text-gray-700">Admin Panel</h1>
                    <button class="text-gray-500 focus:outline-none">
                        <i class="ri-menu-line text-2xl"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
