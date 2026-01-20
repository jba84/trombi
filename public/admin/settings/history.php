<?php
require_once __DIR__ . '../../../private/config/init.php';
require_once PRIVATE_PATH . '/utils/auth_check.php';

// Set the page title
$page_title = "Manage Contract History";

// Handle form submission for deleting history
$delete_message = '';
$deleted_count = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_period'])) {
    $period = $_POST['delete_period'];
    $interval_map = [
        '1-year' => '1 YEAR',
        '2-years' => '2 YEAR',
        '5-years' => '5 YEAR',
        'all' => null
    ];

    if (array_key_exists($period, $interval_map)) {
        $conn->begin_transaction();
        try {
            $sql = "DELETE FROM " . TABLE_CONTRACT_HISTORY;
            if ($interval_map[$period] !== null) {
                $sql .= " WHERE archived_at < NOW() - INTERVAL " . $interval_map[$period];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $deleted_count = $stmt->affected_rows;
            
            $conn->commit();
            $delete_message = "Successfully deleted " . $deleted_count . " record(s).";
            $logger->info("Contract history cleanup", ['period' => $period, 'count' => $deleted_count]);
        } catch (Exception $e) {
            $conn->rollback();
            $delete_message = "Error deleting history: " . $e->getMessage();
            $logger->error("Contract history deletion failed", ['error' => $e->getMessage()]);
        }
    } else {
        $delete_message = "Invalid period selected.";
    }
}

// Get current count of history records
$total_records = $conn->query("SELECT COUNT(*) FROM " . TABLE_CONTRACT_HISTORY)->fetch_row()[0];

include_once PRIVATE_PATH . '/includes/admin_header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?php echo $page_title; ?></h1>

    <?php if ($delete_message): ?>
        <div class="alert alert-<?php echo ($deleted_count > 0 || strpos($delete_message, 'Successfully') !== false) ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($delete_message); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Cleanup Archived Contracts</h6>
        </div>
        <div class="card-body">
            <p>This page allows you to permanently delete archived contract records from the database to save space.</p>
            <p>There are currently <strong><?php echo $total_records; ?></strong> archived contract record(s) in the database.</p>
            <hr>
            <form method="POST" action="history.php" onsubmit="return confirm('Are you sure you want to permanently delete these records? This action cannot be undone.');">
                <div class="form-group">
                    <label for="delete_period">Select records to delete:</label>
                    <select name="delete_period" id="delete_period" class="form-control" style="max-width: 300px;">
                        <option value="1-year">Older than 1 year</option>
                        <option value="2-years">Older than 2 years</option>
                        <option value="5-years">Older than 5 years</option>
                        <option value="all">Delete All Records</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="ri-delete-bin-line"></i> Delete History
                </button>
            </form>
        </div>
    </div>
</div>

<?php
include_once PRIVATE_PATH . '/includes/admin_footer.php';
?>
