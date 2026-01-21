<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';

$conn = getDBConnection();

$search = sanitize_input($_GET['search'] ?? '');
$department = sanitize_input($_GET['department'] ?? '');
$company = sanitize_input($_GET['company'] ?? '');
$sort_by = sanitize_input($_GET['sort'] ?? 'last_name');
$sort_order = sanitize_input($_GET['order'] ?? 'ASC');

$staff_members = get_all_staff_members($conn, $sort_by, $sort_order, $search, $department, $company);
$departments = get_active_department_names($conn);
$companies = get_active_company_names($conn);
?>

<h1 class="page-title mb-6 text-gray-700 font-thin text-4xl"><?php echo __('staff_directory'); ?></h1>

<div class="controls flex flex-wrap gap-4 mb-6 items-center">
    <div class="search-box flex-grow min-w-[250px]">
        <input class="w-full rounded-full px-4 py-2 border border-gray-200 shadow-sm" type="text" id="search" placeholder="<?php echo __('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="flex flex-wrap gap-4">
        <select id="company-filter" class="rounded-full border px-4 py-2 bg-white">
            <option value=""><?php echo __('all_companies'); ?></option>
            <?php foreach ($companies as $comp): ?>
                <option value="<?php echo htmlspecialchars($comp); ?>" <?php echo ($company == $comp) ? 'selected' : ''; ?>><?php echo htmlspecialchars($comp); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="department-filter" class="rounded-full border px-4 py-2 bg-white">
            <option value=""><?php echo __('all_departments'); ?></option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department == $dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="staff-grid grid grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-6 mb-8">
    <?php foreach ($staff_members as $staff): ?>
        <?php 
            $imageUrl = get_staff_image_url($staff); 
            $placeholderColor = $staff['department_color'] ?? '#cccccc';
            $textClass = get_text_contrast_class($placeholderColor);
        ?>
        <div class="staff-card bg-white rounded-[20px] shadow-md overflow-hidden flex flex-col">
            <?php if ($imageUrl): ?>
                <img src="<?php echo $imageUrl; ?>" class="w-full aspect-square object-cover">
            <?php else: ?>
                <div class="w-full aspect-square flex items-center justify-center text-4xl font-bold" style="background-color:<?php echo $placeholderColor; ?>; color:<?php echo ($textClass === 'dark-text' ? '#333' : '#fff'); ?>">
                    <?php echo strtoupper(substr($staff['first_name'],0,1).substr($staff['last_name'],0,1)); ?>
                </div>
            <?php endif; ?>
            <div class="p-4 flex-grow">
                <h3 class="text-lg font-medium"><?php echo htmlspecialchars($staff['first_name'].' '.$staff['last_name']); ?></h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($staff['job_title']); ?></p>
                <span class="inline-block px-2 py-1 rounded-full text-xs mt-2 <?php echo $textClass; ?>" style="background-color:<?php echo $placeholderColor; ?>">
                    <?php echo htmlspecialchars($staff['department']); ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php'; ?>