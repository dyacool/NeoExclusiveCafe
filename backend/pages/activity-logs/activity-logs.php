<?php
require_once __DIR__ . '/../admin-includes/database.php';
require_once __DIR__ . '/../admin-includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: /backend/login/admin/admin-login.php');
    exit();
}

$page_title = "Activity Logs";

// Include navbar
require_once __DIR__ . '/../admin-includes/navbar/navbar.php';

// First, check if the activity_logs table exists
$table_check_query = "SHOW TABLES LIKE 'activity_logs'";
$table_exists = mysqli_query($conn, $table_check_query);

if (!$table_exists || mysqli_num_rows($table_exists) === 0) {
    // Create the activity_logs table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        admin_name VARCHAR(255) NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        action_description TEXT NOT NULL,
        affected_table VARCHAR(100) NULL,
        affected_id INT NULL,
        ip_address VARCHAR(45) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!mysqli_query($conn, $create_table_sql)) {
        die("Error creating activity_logs table: " . mysqli_error($conn));
    }
}

// Initialize variables
$total_records = 0;
$total_pages = 1;
$limit = 50; // Number of records per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total number of records with error handling
$total_records_query = "SELECT COUNT(*) as count FROM activity_logs";
$total_records_result = mysqli_query($conn, $total_records_query);

if ($total_records_result) {
    $total_records = mysqli_fetch_assoc($total_records_result)['count'];
    $total_pages = max(1, ceil($total_records / $limit));
    // Ensure page number is within valid range
    $page = min($page, $total_pages);
    $offset = ($page - 1) * $limit;
} else {
    // Handle query error
    error_log("Error getting total records: " . mysqli_error($conn));
}

// Get activity logs with pagination and error handling
$result = false;
$error_message = '';

$query = "SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT ? OFFSET ?";
if ($stmt = mysqli_prepare($conn, $query)) {
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $error_message = "Error executing query: " . mysqli_stmt_error($stmt);
        error_log($error_message);
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Error preparing query: " . mysqli_error($conn);
    error_log($error_message);
}
?>

<link rel="stylesheet" href="activity-logs.css">

<div class="main-content">
    <div class="page-header">
        <h1>Activity Logs</h1>
        <nav class="breadcrumb">
            <ol>
                <li><a href="/backend/pages/homepage/admin-homepage.php">Home</a></li>
                <li class="active">Activity Logs</li>
            </ol>
        </nav>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($error_message): ?>
                        <tr>
                            <td colspan="5" class="error-message">
                                An error occurred while fetching the logs. Please try again later.
                            </td>
                        </tr>
                    <?php elseif ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['admin_name']); ?></td>
                                <td>
                                    <span class="action-badge <?php echo strtolower($row['action_type']); ?>">
                                        <?php echo htmlspecialchars($row['action_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['action_description']); ?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['timestamp'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-records">No activity logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>" class="page-link">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page + 1); ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Add footer
require_once __DIR__ . '/../admin-includes/footer/admin-footer.php'; 
?>
