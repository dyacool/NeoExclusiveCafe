<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../login/admin/admin-auth.php";
require_once __DIR__ . "/../admin-includes/database.php";

// Helper function to format selected dates for dashboard
function formatDashboardDates($datesString) {
    if (empty($datesString)) return "";
    
    $dates = explode(',', $datesString);
    $dates = array_filter($dates); // Remove empty values
    
    if (empty($dates)) return "";
    
    $formattedDates = [];
    foreach ($dates as $date) {
        $dateObj = DateTime::createFromFormat('Y-m-d', trim($date));
        if ($dateObj) {
            $formattedDates[] = $dateObj->format('n/j'); // Format as M/D (e.g., 8/27)
        }
    }
    
    return implode(' · ', $formattedDates);
}

// Initialize stats array with default values
$stats = [
    'active_orders' => 0,
    'pending_orders' => 0,
    'active_bulk_orders' => 0,
    'pending_bulk_orders' => 0,
    'total_products' => 0,
    'active_refund_requests' => 0,
    'today_sales' => 0,
    'month_net_profit' => 0
];

// Initialize arrays
$top_products = [];
$latest_transactions = [];
$latest_orders = [];
$availtoday_products = [];

// Get today's date for calculations
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$last_month_start = date('Y-m-d', strtotime('-30 days'));

// ======================
// DASHBOARD STATISTICS
// ======================

try {
    // Active Orders (all orders that are not completed/cancelled)
    $active_orders_query = "SELECT COUNT(*) as active_orders FROM orders 
                           WHERE status NOT IN ('Delivered', 'Picked-up', 'Cancelled', 'Completed')";
    $result = mysqli_query($conn, $active_orders_query);
    $row = mysqli_fetch_assoc($result);
    $stats['active_orders'] = $row['active_orders'] ?? 0;
    
    // Pending Orders count (confirmed status in database)
    $pending_orders_query = "SELECT COUNT(*) as pending_orders FROM orders 
                            WHERE status = 'confirmed'";
    $result = mysqli_query($conn, $pending_orders_query);
    $row = mysqli_fetch_assoc($result);
    $stats['pending_orders'] = $row['pending_orders'] ?? 0;
    
    // Active Bulk Orders
    $active_bulk_query = "SELECT COUNT(*) as active_bulk FROM bulk_orders 
                         WHERE status NOT IN ('completed', 'cancelled', 'delivered')";
    $result = mysqli_query($conn, $active_bulk_query);
    $row = mysqli_fetch_assoc($result);
    $stats['active_bulk_orders'] = $row['active_bulk'] ?? 0;
    
    // Pending Bulk Orders count
    $pending_bulk_query = "SELECT COUNT(*) as pending_bulk FROM bulk_orders 
                          WHERE status = 'pending'";
    $result = mysqli_query($conn, $pending_bulk_query);
    $row = mysqli_fetch_assoc($result);
    $stats['pending_bulk_orders'] = $row['pending_bulk'] ?? 0;
    
    // Today's Sales
    $today_sales_query = "SELECT SUM(total_amount) as today_sales FROM orders 
                         WHERE DATE(order_date) = ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $today_sales_query);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['today_sales'] = $row['today_sales'] ?? 0;
    
    // Yesterday's Sales for comparison
    $yesterday_sales_query = "SELECT SUM(total_amount) as yesterday_sales FROM orders 
                             WHERE DATE(order_date) = ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $yesterday_sales_query);
    mysqli_stmt_bind_param($stmt, "s", $yesterday);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $yesterday_sales = $row['yesterday_sales'] ?? 0;
    
    // Total Products
    $products_query = "SELECT COUNT(*) as total_products FROM products WHERE deleted_at IS NULL";
    $result = mysqli_query($conn, $products_query);
    $row = mysqli_fetch_assoc($result);
    $stats['total_products'] = $row['total_products'] ?? 0;
    
    // Active Refund Requests (pending status)
    $refund_query = "SELECT COUNT(*) as active_refunds FROM order_refunds 
                    WHERE refund_status = 'pending'";
    $result = mysqli_query($conn, $refund_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['active_refund_requests'] = $row['active_refunds'] ?? 0;
    }
    
    // Month Net Profit calculation
    $month_start = date('Y-m-01'); // First day of current month
    
    // Total Revenue this month
    $month_revenue_query = "SELECT SUM(total_amount) as month_revenue FROM orders 
                           WHERE DATE(order_date) >= ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $month_revenue_query);
    mysqli_stmt_bind_param($stmt, "s", $month_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $month_revenue = $row['month_revenue'] ?? 0;
    
    // Total Discounts this month
    $month_discounts_query = "SELECT SUM(discount_amount) as month_discounts FROM orders 
                             WHERE DATE(order_date) >= ? AND status NOT IN ('Cancelled') 
                             AND discount_amount > 0";
    $stmt = mysqli_prepare($conn, $month_discounts_query);
    mysqli_stmt_bind_param($stmt, "s", $month_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $month_discounts = $row['month_discounts'] ?? 0;
    
    // Total Refunds this month (approved refunds)
    $month_refunds_query = "SELECT SUM(refund_amount) as month_refunds FROM order_refunds 
                           WHERE DATE(created_at) >= ? AND refund_status IN ('approved', 'completed')";
    $stmt = mysqli_prepare($conn, $month_refunds_query);
    mysqli_stmt_bind_param($stmt, "s", $month_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $month_refunds = $row['month_refunds'] ?? 0;
    
    // Calculate Net Profit (Revenue - Discounts - Refunds)
    // Note: Expenses would need a separate expenses table to track
    $stats['month_net_profit'] = $month_revenue - $month_discounts - $month_refunds;
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// ======================
// TOP 10 PRODUCTS DATA
// ======================

try {
    // Get top 10 products by quantity sold
    $top_products_query = "SELECT 
                              oi.product_name as name,
                              SUM(oi.quantity) as total_sold,
                              SUM(oi.price * oi.quantity) as total_revenue,
                              AVG(oi.price) as price
                          FROM order_items oi
                          JOIN orders o ON oi.order_id = o.order_id
                          WHERE o.status NOT IN ('Cancelled')
                          GROUP BY oi.product_name
                          ORDER BY total_sold DESC
                          LIMIT 10";
    
    $result = mysqli_query($conn, $top_products_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $top_products[] = [
                'name' => $row['name'],
                'total_sold' => (int)$row['total_sold'],
                'total_revenue' => (float)$row['total_revenue'],
                'price' => (float)$row['price']
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Top products query error: " . $e->getMessage());
}

// If no real data, use sample data for demo
if (empty($top_products)) {
    $top_products = [
        ['name' => 'Product1', 'total_sold' => 250, 'total_revenue' => 62500, 'price' => 250.00],
        ['name' => 'Product2', 'total_sold' => 220, 'total_revenue' => 55000, 'price' => 250.00],
        ['name' => 'Product3', 'total_sold' => 180, 'total_revenue' => 36000, 'price' => 200.00],
        ['name' => 'Product4', 'total_sold' => 150, 'total_revenue' => 22500, 'price' => 150.00],
        ['name' => 'Product5', 'total_sold' => 130, 'total_revenue' => 32500, 'price' => 250.00],
        ['name' => 'Product6', 'total_sold' => 120, 'total_revenue' => 36000, 'price' => 300.00],
        ['name' => 'Product7', 'total_sold' => 110, 'total_revenue' => 38500, 'price' => 350.00],
        ['name' => 'Product8', 'total_sold' => 100, 'total_revenue' => 25000, 'price' => 250.00],
        ['name' => 'Product9', 'total_sold' => 90, 'total_revenue' => 27000, 'price' => 300.00],
        ['name' => 'Product10', 'total_sold' => 80, 'total_revenue' => 20000, 'price' => 250.00]
    ];
}

// ======================
// LATEST TRANSACTIONS
// ======================

try {
    $transactions_query = "SELECT 
                              order_id as id,
                              customer_name,
                              total_amount,
                              order_date,
                              status
                          FROM orders 
                          WHERE status IN ('Completed', 'Delivered', 'Picked-up')
                          ORDER BY order_date DESC 
                          LIMIT 5";
    
    $result = mysqli_query($conn, $transactions_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $latest_transactions[] = [
                'id' => $row['id'],
                'customer_name' => $row['customer_name'],
                'total_amount' => (float)$row['total_amount'],
                'order_date' => $row['order_date'],
                'status' => $row['status']
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Latest transactions query error: " . $e->getMessage());
}

// ======================
// LATEST ORDERS
// ======================

try {
    $orders_query = "SELECT 
                        order_id as id,
                        customer_name,
                        total_amount,
                        order_date,
                        status
                    FROM orders 
                    ORDER BY order_date DESC 
                    LIMIT 5";
    
    $result = mysqli_query($conn, $orders_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $latest_orders[] = [
                'id' => $row['id'],
                'customer_name' => $row['customer_name'],
                'total_amount' => (float)$row['total_amount'],
                'order_date' => $row['order_date'],
                'status' => $row['status']
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Latest orders query error: " . $e->getMessage());
}

// ======================
// PRODUCTS WITH TODAY AVAILABILITY
// ======================

try {
    // Get products that have availtoday status or regular products with today dates
    $availtoday_query = "SELECT DISTINCT
                            p.id,
                            p.name,
                            ps.name as status_name,
                            ps.id as status_id,
                            aps.name as availtoday_status_name,
                            COALESCE(pd.selected_dates, '') as todays_product_dates,
                            COALESCE(prd.selected_dates, '') as regular_today_dates
                        FROM products p
                        LEFT JOIN product_statuses ps ON p.status_id = ps.id
                        LEFT JOIN product_statuses aps ON p.availtoday_status_id = aps.id
                        LEFT JOIN product_day pd ON p.id = pd.product_id AND pd.status_type = 'availtoday'
                        LEFT JOIN product_day prd ON p.id = prd.product_id AND prd.status_type = 'regular'
                        WHERE p.deleted_at IS NULL 
                        AND (
                            (p.availtoday_status_id IS NOT NULL AND p.availtoday_status_id != 0)
                            OR (pd.selected_dates IS NOT NULL AND pd.selected_dates != '')
                            OR (prd.selected_dates IS NOT NULL AND prd.selected_dates != '')
                        )
                        ORDER BY p.name
                        LIMIT 10";
    
    $result = mysqli_query($conn, $availtoday_query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $availtoday_products[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Availtoday products query error: " . $e->getMessage());
}

// Today vs Yesterday sales change
if ($yesterday_sales > 0) {
    $today_change_percent = round((($stats['today_sales'] - $yesterday_sales) / $yesterday_sales) * 100, 1);
    $stats['today_change'] = ($today_change_percent >= 0 ? '+' : '') . $today_change_percent . '% from yesterday';
} else {
    $stats['today_change'] = $stats['today_sales'] > 0 ? 'New sales today' : 'No sales yet';
}

// Active Orders change info
$stats['active_orders_change'] = $stats['pending_orders'] . ' pending';

// Active Bulk Orders change info
$stats['active_bulk_change'] = $stats['pending_bulk_orders'] . ' pending';

// Get last month's net profit for comparison
try {
    $last_month_start = date('Y-m-01', strtotime('-1 month'));
    $last_month_end = date('Y-m-t', strtotime('-1 month'));
    
    // Last month revenue
    $last_month_revenue_query = "SELECT SUM(total_amount) as last_revenue FROM orders 
                                WHERE DATE(order_date) >= ? AND DATE(order_date) <= ? 
                                AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $last_month_revenue_query);
    mysqli_stmt_bind_param($stmt, "ss", $last_month_start, $last_month_end);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_revenue = $row['last_revenue'] ?? 0;
    
    // Last month discounts
    $last_month_discounts_query = "SELECT SUM(discount_amount) as last_discounts FROM orders 
                                  WHERE DATE(order_date) >= ? AND DATE(order_date) <= ? 
                                  AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $last_month_discounts_query);
    mysqli_stmt_bind_param($stmt, "ss", $last_month_start, $last_month_end);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_discounts = $row['last_discounts'] ?? 0;
    
    // Last month refunds
    $last_month_refunds_query = "SELECT SUM(refund_amount) as last_refunds FROM order_refunds 
                                WHERE DATE(created_at) >= ? AND DATE(created_at) <= ? 
                                AND refund_status IN ('approved', 'completed')";
    $stmt = mysqli_prepare($conn, $last_month_refunds_query);
    mysqli_stmt_bind_param($stmt, "ss", $last_month_start, $last_month_end);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_refunds = $row['last_refunds'] ?? 0;
    
    $last_month_profit = $last_revenue - $last_discounts - $last_refunds;
    
    if ($last_month_profit > 0) {
        $profit_change_percent = round((($stats['month_net_profit'] - $last_month_profit) / $last_month_profit) * 100, 1);
        $stats['profit_change'] = ($profit_change_percent >= 0 ? '+' : '') . $profit_change_percent . '% from last month';
    } else {
        $stats['profit_change'] = 'First month data';
    }
} catch (Exception $e) {
    $stats['profit_change'] = 'Data unavailable';
}

// Get products added this week
try {
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $new_products_query = "SELECT COUNT(*) as new_products FROM products 
                          WHERE created_at >= ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $new_products_query);
    mysqli_stmt_bind_param($stmt, "s", $week_ago);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $new_products = $row['new_products'] ?? 0;
    $stats['products_change'] = '+' . $new_products . ' new this week';
} catch (Exception $e) {
    $stats['products_change'] = 'Data unavailable';
}

// Get refund requests this week for comparison
try {
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $week_refunds_query = "SELECT COUNT(*) as week_refunds FROM order_refunds 
                          WHERE DATE(created_at) >= ? AND refund_status = 'pending'";
    $stmt = mysqli_prepare($conn, $week_refunds_query);
    mysqli_stmt_bind_param($stmt, "s", $week_ago);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $week_refunds = $row['week_refunds'] ?? 0;
    $stats['refund_change'] = $week_refunds . ' new this week';
} catch (Exception $e) {
    $stats['refund_change'] = 'Data unavailable';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeoCafe Dashboard</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="../counts.css">
    <link rel="stylesheet" href="../../assets/css/dashboard-polling.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include "../admin-includes/navbar/navbar.php"; ?>

    <!-- Dashboard Polling Loading Indicator -->
    <div id="dashboard-loading-indicator">
        <div class="spinner"></div>
        <span>Updating...</span>
    </div>

    <div class="main-container">
        <div class="dashboard-container">
            <div class="dashboard-section-1">
                <div class="service-cards-grid">

                    <!-- Sales Today -->
                    <div class="service-card" data-stat="today_sales">
                        <div class="card-header">
                            <span class="card-title">Sales Today</span>
                            <div class="card-loading-spinner"></div>
                        </div>
                        <div class="card-value fas fa-peso-sign"> <?php echo number_format((double)$stats['today_sales'], 2, '.', ','); ?></div>
                        <div class="card-change positive"><?php echo $stats['today_change']; ?></div>
                    </div>

                    <!-- Month Net Profit -->
                    <div class="service-card" data-stat="month_net_profit">
                        <div class="card-header">
                            <span class="card-title">Month Net Profit</span>
                            <div class="card-loading-spinner"></div>
                        </div>
                        <div class="card-value fas fa-peso-sign"> <?php echo number_format((double)$stats['month_net_profit'], 2, '.', ','); ?></div>
                        <div class="card-change positive"><?php echo $stats['profit_change']; ?></div>
                    </div>

                    <!-- Total Products -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Total Products</span>
                            <i class="fas fa-box card-icon"></i>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['total_products'], 0); ?></div>
                        <div class="card-change positive"><?php echo $stats['products_change']; ?></div>
                    </div>

                    <!-- Active Orders -->
                    <div class="service-card" data-stat="active_orders">
                        <div class="card-header">
                            <span class="card-title">Active Orders</span>
                            <div class="card-loading-spinner"></div>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['active_orders'], 0); ?></div>
                        <div class="card-change"><?php echo $stats['active_orders_change']; ?></div>
                    </div>

                    <!-- Active Bulk Orders -->
                    <div class="service-card" data-stat="active_bulk_orders">
                        <div class="card-header">
                            <span class="card-title">Active Bulk Orders</span>
                            <div class="card-loading-spinner"></div>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['active_bulk_orders'], 0); ?></div>
                        <div class="card-change"><?php echo $stats['active_bulk_change']; ?></div>
                    </div>

                    <!-- Active Refund Requests -->
                    <div class="service-card" data-stat="active_refund_requests">
                        <div class="card-header">
                            <span class="card-title">Active Refund Requests</span>
                            <div class="card-loading-spinner"></div>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['active_refund_requests'], 0); ?></div>
                        <div class="card-change"><?php echo $stats['refund_change']; ?></div>
                    </div>
                </div>

                <div class="dashboard-section-1-content">
                    <div class="business-hours-card">
                        <h3>Business Hours</h3>
                        <div class="time-controls">
                            <div class="time-group">
                                <label>Opens:</label>
                                <input type="time" id="openingTime" name="openingTime" value="08:00">
                            </div>
                            <div class="time-group">
                                <label>Closes:</label>
                                <input type="time" id="closingTime" name="closingTime" value="04:42">
                            </div>
                        </div>
                        <button class="btn-primary btn-full" onclick="updateBusinessHours()" id="saveHoursBtn">
                            <span class="button-text">Save Hours</span>
                            <span class="loading-spinner" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                                Updating...
                            </span>
                        </button>
                    </div>

                    <div class="dashboard-sidebar">
                        <div class="calendar-card">
                            <div class="calendar-header">
                                <button class="calendar-nav" onclick="previousMonth()">«</button>
                                <span class="calendar-month" id="calendar-month-year">Aug 2025</span>
                                <button class="calendar-nav" onclick="nextMonth()">»</button>
                            </div>
                            <div class="calendar-body">
                                <div class="calendar-weekdays">
                                    <div class="weekday">S</div>
                                    <div class="weekday">M</div>
                                    <div class="weekday">T</div>
                                    <div class="weekday">W</div>
                                    <div class="weekday">T</div>
                                    <div class="weekday">F</div>
                                    <div class="weekday">S</div>
                                </div>
                                <div class="calendar-days" id="calendar-days"></div>
                            </div>
                            <div class="calendar-footer">
                                 <a href="../calendar/calendar.php" class="calendar-link">full calendar</a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Div 2: Charts Section -->
            <div class="dashboard-section-2">
                <!-- Top 10 Products Chart -->
                <div class="chart-card" data-container="top-products">
                    <div class="chart-header">
                        <h3>Top 10 Products</h3>
                        <div class="card-loading-spinner"></div>
                        <span class="chart-subtitle">Best selling products this month</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="top-products-chart"></canvas>
                    </div>
                </div>

                <!-- Sales Per Product Chart -->
                <div class="chart-card" data-container="sales-per-product">
                    <div class="chart-header">
                        <h3>Sales Per Product</h3>
                        <div class="card-loading-spinner"></div>
                        <span class="chart-subtitle">Total sales revenue and units sold by product</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="sales-per-product-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Div 3: Tables Section -->
            <div class="dashboard-section-3">
                <!-- Latest Transactions -->
                <div class="table-card" data-container="latest-transactions">
                    <div class="table-header">
                        <h3>Latest Transactions</h3>
                        <div class="card-loading-spinner"></div>
                        <span class="table-subtitle">Recent payment transactions</span>
                    </div>
                    <div class="table-container">
                        <?php if (count($latest_transactions) > 0): ?>
                            <table class="data-table">
                                <tbody>
                                    <?php foreach ($latest_transactions as $transaction): ?>
                                        <tr>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-name"><?php echo htmlspecialchars($transaction['customer_name']); ?></div>
                                                    <div class="transaction-id">TXN<?php echo str_pad($transaction['id'], 3, '0', STR_PAD_LEFT); ?></div>
                                                </div>
                                            </td>
                                            <td class="amount-cell">
                                                <div class="amount">₱<?php echo number_format($transaction['total_amount'], 2); ?></div>
                                                <div class="status-badge status-completed">Completed</div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <p>No transactions found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Latest Orders -->
                <div class="table-card" data-container="latest-orders">
                    <div class="table-header">
                        <h3>Latest Orders</h3>
                        <div class="card-loading-spinner"></div>
                        <span class="table-subtitle">Recent customer orders</span>
                    </div>
                    <div class="table-container">
                        <?php if (count($latest_orders) > 0): ?>
                            <table class="data-table">
                                <tbody>
                                    <?php foreach ($latest_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                                    <div class="order-id">#<?php echo $order['id']; ?></div>
                                                </div>
                                            </td>
                                            <td class="amount-cell">
                                                <div class="amount">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                                <div class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], '', $order['status'])); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <p>No orders found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const topProductsData = <?php echo json_encode($top_products); ?>;
    </script>
    <script src="dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/dashboard-polling.js?v=<?php echo time(); ?>"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            generateCalendar();
            createCharts();
            
            // Initialize polling system
            console.log('[Dashboard] Initializing AJAX polling system');
            const dashboardPoller = new DashboardPoller({
                pollInterval: 5000, // Poll every 5 seconds
                apiEndpoint: '../../api/get-dashboard-stats.php'
            });
            dashboardPoller.start();
            console.log('[Dashboard] Polling system initialized');
        });
    </script>
</body>
</html>
