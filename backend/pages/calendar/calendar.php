<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Initialize statistics array
$stats = [
    'pending_orders' => 0,
    'completed_orders' => 0,
    'delivery_orders' => 0,
    'pickup_orders' => 0
];

// Count orders by status using the same method as order-list.php
$count_sql = "SELECT status, delivery_method, COUNT(*) as count FROM orders GROUP BY status, delivery_method";
$count_result = mysqli_query($conn, $count_sql);

if ($count_result) {
    while ($count_row = mysqli_fetch_assoc($count_result)) {
        $status = $count_row['status'];
        $delivery_method = $count_row['delivery_method'];
        $count = $count_row['count'];
        
        // Pending Orders (Pending, Processing, Preparing)
        if (in_array($status, ['Pending', 'Processing', 'Preparing'])) {
            $stats['pending_orders'] += $count;
        }
        
        // Completed Orders (Completed, Delivered, Picked-up)
        if (in_array($status, ['Completed', 'Delivered', 'Picked-up'])) {
            $stats['completed_orders'] += $count;
        }
        
        // For Delivery Orders (not completed and delivery method is Delivery)
        if ($delivery_method == 'Delivery' && !in_array($status, ['Completed', 'Delivered', 'Cancelled'])) {
            $stats['delivery_orders'] += $count;
        }
        
        // For Pickup Orders (not completed and delivery method is Pick-up)
        if ($delivery_method == 'Pick-up' && !in_array($status, ['Completed', 'Picked-up', 'Cancelled'])) {
            $stats['pickup_orders'] += $count;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - NeoCafe Admin</title>
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
    <!-- Navbar CSS -->
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/admin-navigation.css">
    <!-- Calendar CSS -->
    <link rel="stylesheet" href="calendar.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../admin-includes/navbar/navbar.php'; ?>
        <div class="main-container">
        <!-- Top Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['completed_orders']); ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['delivery_orders']); ?></div>
                <div class="stat-label">For Delivery</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['pickup_orders']); ?></div>
                <div class="stat-label">For Pickup</div>
            </div>
        </div>

        <div class="layout-container">
            <div class="controls-section">
                <div class="control-card">
                    <h3>Orders Overview</h3>
                    <div class="control-row">
                        <label for="toggleCompletedBtn">Show completed orders:</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="toggleCompletedBtn" onchange="toggleCompletedOrders()">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
                <div class="control-card">
                    <h3>Daily Order Limit</h3>
                    <div class="control-row">
                        <label>No. of Orders:</label>
                        <input type="number" id="dailyLimit" min="0" value="5">
                        <button class="btn-green" onclick="updateDailyLimit()">Save</button>
                    </div>
                </div>

                <div class="control-card">
                    <h3>Available Today Limit</h3>
                    <div class="control-row">
                        <label>No. of Orders:</label>
                        <input type="number" id="availtodayOrderLimit" min="0" value="1">
                        <button class="btn-green" onclick="updateAvailTodayOrderLimit()">Save</button>
                    </div>

                </div>

                <div class="control-card business-hours">
                    <h3>Business Hours</h3>
                    <div class="time-row">
                        <div class="time-group">
                            <label>Opens:</label>
                            <input type="time" id="openingTime" name="openingTime" value="08:00">
                        </div>
                        <div class="time-group">
                            <label>Closes:</label>
                            <input type="time" id="closingTime" name="closingTime" value="04:42">
                        </div>
                    </div>
                    <button class="btn-green business-hour" onclick="updateBusinessHours()" id="saveHoursBtn">Save</button>
                </div>
            </div>

            <!-- Right Side Calendar -->
            <div class="calendar-section">
                <div class="calendar-container">
                    <div id="calendar">
                        <div class="calendar-header">
                            <button id="prev">&lt;</button>
                            <span id="monthYear"></span>
                            <button id="next">&gt;</button>
                        </div>
                        <div class="days"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="order-details-modal" id="orderModal">
        <div class="modal-content">
            <div class="close-btnn">
                <span class="close">&times;</span>
            </div>
            <div id="orderInfo"></div>
        </div>
    </div>

    <!-- Daily Limit Update Modal -->
    <div class="modal" id="dailyLimitModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Daily Order Limit</h3>
                <span class="close" onclick="closeDailyLimitModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Enter the new daily order limit:</p>
                <input type="number" id="modalDailyLimit" min="0" placeholder="Enter limit">
            </div>
            <div class="modal-footer">
                <button class="btn-green" onclick="saveDailyLimit()">Save</button>
                <button class="btn-cancel" onclick="closeDailyLimitModal()">Cancel</button>
            </div>
        </div>
    </div>
    </div>

    <script src="calendar.js"></script>
</body>
</html>