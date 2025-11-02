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
    'today_income' => 0,
    'net_income' => 0,
    'total_products' => 0,
    'pending_orders' => 0,
    'total_orders' => 0,
    'total_users' => 0,
    'bulk_orders' => 0
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
    // Today's Income
    $today_income_query = "SELECT SUM(total_amount) as today_income FROM orders 
                          WHERE DATE(order_date) = ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $today_income_query);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['today_income'] = $row['today_income'] ?? 0;
    
    // Yesterday's Income for comparison
    $yesterday_income_query = "SELECT SUM(total_amount) as yesterday_income FROM orders 
                              WHERE DATE(order_date) = ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $yesterday_income_query);
    mysqli_stmt_bind_param($stmt, "s", $yesterday);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $yesterday_income = $row['yesterday_income'] ?? 0;
    
    // Net Income (last 30 days)
    $net_income_query = "SELECT SUM(total_amount) as net_income FROM orders 
                        WHERE DATE(order_date) >= ? AND status NOT IN ('Cancelled')";
    $stmt = mysqli_prepare($conn, $net_income_query);
    mysqli_stmt_bind_param($stmt, "s", $last_month_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $stats['net_income'] = $row['net_income'] ?? 0;
    
    // Total Products
    $products_query = "SELECT COUNT(*) as total_products FROM products WHERE deleted_at IS NULL";
    $result = mysqli_query($conn, $products_query);
    $row = mysqli_fetch_assoc($result);
    $stats['total_products'] = $row['total_products'] ?? 0;
    
    // Pending Orders
    $pending_query = "SELECT COUNT(*) as pending_orders FROM orders 
                     WHERE status IN ('Pending', 'Processing', 'Preparing')";
    $result = mysqli_query($conn, $pending_query);
    $row = mysqli_fetch_assoc($result);
    $stats['pending_orders'] = $row['pending_orders'] ?? 0;
    
    // Total Orders
    $total_orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $result = mysqli_query($conn, $total_orders_query);
    $row = mysqli_fetch_assoc($result);
    $stats['total_orders'] = $row['total_orders'] ?? 0;
    
    // Total Users
    $users_query = "SELECT COUNT(*) as total_users FROM users";
    $result = mysqli_query($conn, $users_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['total_users'] = $row['total_users'] ?? 0;
    }
    
    // Bulk Orders
    $bulk_orders_query = "SELECT COUNT(*) as bulk_orders FROM bulk_orders";
    $result = mysqli_query($conn, $bulk_orders_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $stats['bulk_orders'] = $row['bulk_orders'] ?? 0;
    }
    
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

// Today vs Yesterday income change
if ($yesterday_income > 0) {
    $today_change_percent = round((($stats['today_income'] - $yesterday_income) / $yesterday_income) * 100, 1);
    $stats['today_change'] = ($today_change_percent >= 0 ? '+' : '') . $today_change_percent . '% from yesterday';
} else {
    $stats['today_change'] = $stats['today_income'] > 0 ? 'New income today' : 'No income yet';
}

// Get last month's income for comparison
try {
    $last_month_income_query = "SELECT SUM(total_amount) as last_month_income FROM orders 
                               WHERE DATE(order_date) >= ? AND DATE(order_date) < ? 
                               AND status NOT IN ('Cancelled')";
    $two_months_ago = date('Y-m-d', strtotime('-60 days'));
    $stmt = mysqli_prepare($conn, $last_month_income_query);
    mysqli_stmt_bind_param($stmt, "ss", $two_months_ago, $last_month_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_month_income = $row['last_month_income'] ?? 0;
    
    if ($last_month_income > 0) {
        $net_change_percent = round((($stats['net_income'] - $last_month_income) / $last_month_income) * 100, 1);
        $stats['net_change'] = ($net_change_percent >= 0 ? '+' : '') . $net_change_percent . '% from last month';
    } else {
        $stats['net_change'] = 'First month data';
    }
} catch (Exception $e) {
    $stats['net_change'] = 'Data unavailable';
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

// Get pending orders change from last week
try {
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $last_week_pending_query = "SELECT COUNT(*) as last_week_pending FROM orders 
                               WHERE DATE(order_date) >= ? AND DATE(order_date) < ?
                               AND status IN ('Pending', 'Processing', 'Preparing')";
    $two_weeks_ago = date('Y-m-d', strtotime('-14 days'));
    $stmt = mysqli_prepare($conn, $last_week_pending_query);
    mysqli_stmt_bind_param($stmt, "ss", $two_weeks_ago, $week_ago);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_week_pending = $row['last_week_pending'] ?? 0;
    
    if ($last_week_pending > 0) {
        $pending_change_percent = round((($stats['pending_orders'] - $last_week_pending) / $last_week_pending) * 100, 1);
        $stats['pending_change'] = ($pending_change_percent >= 0 ? '+' : '') . $pending_change_percent . '% from last week';
    } else {
        $stats['pending_change'] = 'New orders this week';
    }
} catch (Exception $e) {
    $stats['pending_change'] = 'Data unavailable';
}

// Get orders change from last week
try {
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $last_week_orders_query = "SELECT COUNT(*) as last_week_orders FROM orders 
                              WHERE DATE(order_date) >= ? AND DATE(order_date) < ?";
    $two_weeks_ago = date('Y-m-d', strtotime('-14 days'));
    $stmt = mysqli_prepare($conn, $last_week_orders_query);
    mysqli_stmt_bind_param($stmt, "ss", $two_weeks_ago, $week_ago);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_week_orders = $row['last_week_orders'] ?? 0;
    
    if ($last_week_orders > 0) {
        $orders_change_percent = round((($stats['total_orders'] - $last_week_orders) / $last_week_orders) * 100, 1);
        $stats['orders_change'] = ($orders_change_percent >= 0 ? '+' : '') . $orders_change_percent . '% from last week';
    } else {
        $stats['orders_change'] = 'First week data';
    }
} catch (Exception $e) {
    $stats['orders_change'] = 'Data unavailable';
}

// Get bulk orders pending approval
try {
    $pending_bulk_query = "SELECT COUNT(*) as pending_bulk FROM bulk_orders WHERE status = 'pending'";
    $result = mysqli_query($conn, $pending_bulk_query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $pending_bulk = $row['pending_bulk'] ?? 0;
        $stats['bulk_change'] = $pending_bulk . ' pending approval';
    } else {
        $stats['bulk_change'] = '0 pending approval';
    }
} catch (Exception $e) {
    $stats['bulk_change'] = 'Data unavailable';
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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include "../admin-includes/navbar/navbar.php"; ?>

    <div class="main-container">
        <div class="dashboard-container">
            <div class="dashboard-section-1">
                <div class="service-cards-grid">
                    <!-- Today Income -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Today Income</span>
                            <i class="card-icon"></i>
                        </div>
                        <div class="card-value fas fa-peso-sign"> <?php echo number_format($stats['today_income'], 0); ?></div>
                        <div class="card-change positive"><?php echo $stats['today_change']; ?></div>
                    </div>

                    <!-- Net Income -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Net Income</span>
                            <i class="fas fa-chart-line card-icon"></i>
                        </div>
                        <div class="card-value fas fa-peso-sign"> <?php echo number_format($stats['net_income'], 0); ?></div>
                        <div class="card-change positive"><?php echo $stats['net_change']; ?></div>
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

                    <!-- Pending Orders -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Pending Orders</span>
                            <i class="fas fa-clock card-icon"></i>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['pending_orders'], 0); ?></div>
                        <div class="card-change positive"><?php echo $stats['pending_change']; ?></div>
                    </div>

                    <!-- Total Orders -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Total Orders</span>
                            <i class="fas fa-shopping-cart card-icon"></i>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['total_orders'], 0); ?></div>
                        <div class="card-change positive"><?php echo $stats['orders_change']; ?></div>
                    </div>

                    <!-- Bulk Orders -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Bulk Orders</span>
                            <i class="fas fa-layer-group card-icon"></i>
                        </div>
                        <div class="card-value"><?php echo number_format($stats['bulk_orders'], 0); ?></div>
                        <div class="card-change"><?php echo $stats['bulk_change']; ?></div>
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
                                <a href="#" class="calendar-link">full calendar</a>
                                <span class="calendar-note">note</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Div 2: Charts Section -->
            <div class="dashboard-section-2">
                <!-- Top 10 Products Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Top 10 Products</h3>
                        <span class="chart-subtitle">Best selling products this month</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="top-products-chart"></canvas>
                    </div>
                </div>

                <!-- Sales Per Product Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sales Per Product</h3>
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
                <div class="table-card">
                    <div class="table-header">
                        <h3>Latest Transactions</h3>
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
                <div class="table-card">
                    <div class="table-header">
                        <h3>Latest Orders</h3>
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

                <!-- Products with Today Availability -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Same Day Order Products</h3>
                        <span class="table-subtitle">Products available for pickup/delivery with specific dates</span>
                    </div>
                    <div class="table-container">
                        <?php if (count($availtoday_products) > 0): ?>
                            <table class="data-table">
                                <tbody>
                                    <?php foreach ($availtoday_products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="order-id">
                                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $product['status_name'])); ?>">
                                                            <?php echo htmlspecialchars($product['status_name']); ?>
                                                        </span>
                                                        <?php if (!empty($product['availtoday_status_name'])): ?>
                                                            <span class="availtoday-badge">
                                                                <?php echo ($product['status_id'] == 3 ? 'for ' : 'also for ') . htmlspecialchars($product['availtoday_status_name']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="amount-cell">
                                                <div class="available-dates">
                                                    <?php 
                                                    $dates = $product['status_id'] == 3 ? $product['todays_product_dates'] : $product['regular_today_dates'];
                                                    echo formatDashboardDates($dates);
                                                    ?>
                                                </div>
                                                <div class="dates-count">
                                                    <?php 
                                                    $dateArray = array_filter(explode(',', $dates));
                                                    echo count($dateArray) . ' date' . (count($dateArray) != 1 ? 's' : '');
                                                    ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-alt"></i>
                                <p>No products with today availability found</p>
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
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            generateCalendar();
            createCharts();
        });
    </script>
</body>
</html>
