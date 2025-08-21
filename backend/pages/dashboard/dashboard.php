<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Initialize stats array with default values
$stats = [
    'today_income' => 0,
    'net_income' => 0,
    'total_products' => 0,
    'pending_orders' => 0,
    'total_orders' => 0,
    'total_users' => 0
];

// Initialize arrays
$top_products = [];
$latest_transactions = [];
$latest_orders = [];

// Check if connection exists
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Total Users (using same query as admin-homepage.php)
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
if ($users_result) {
    $stats['total_users'] = $users_result->fetch_assoc()['total_users'];
}

// Net Income (using same query as admin-homepage.php)
$income_query = "SELECT COALESCE(SUM(total_amount), 0) as total_income FROM orders WHERE status IN ('Delivered', 'Picked-up')";
$income_result = $conn->query($income_query);
if ($income_result) {
    $stats['net_income'] = $income_result->fetch_assoc()['total_income'];
}

// Current day total income
$today_income_query = "SELECT COALESCE(SUM(total_amount), 0) as today_income FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('Delivered', 'Picked-up')";
$today_income_result = $conn->query($today_income_query);
if ($today_income_result) {
    $stats['today_income'] = $today_income_result->fetch_assoc()['today_income'];
}

// Total Orders (using same query as admin-homepage.php)
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = $conn->query($orders_query);
if ($orders_result) {
    $stats['total_orders'] = $orders_result->fetch_assoc()['total_orders'];
}

// Orders in Progress (using same query as admin-homepage.php)
$progress_query = "SELECT COUNT(*) as in_progress FROM orders WHERE status NOT IN ('Completed', 'Delivered', 'Picked-up')";
$progress_result = $conn->query($progress_query);
if ($progress_result) {
    $stats['pending_orders'] = $progress_result->fetch_assoc()['in_progress'];
}

// Total products (with error handling)
$products_query = "SELECT COUNT(*) as total_products FROM products";
$products_result = $conn->query($products_query);
if ($products_result) {
    $stats['total_products'] = $products_result->fetch_assoc()['total_products'];
}

// Top 10 products with error handling
$top_products_query = "
    SELECT 
        p.name, 
        COUNT(oi.id) as total_sold, 
        SUM(COALESCE(oi.subtotal, p.price)) as total_revenue,
        p.price
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('Delivered', 'Picked-up')
    GROUP BY p.id, p.name, p.price
    ORDER BY total_sold DESC 
    LIMIT 10
";
$top_products_result = $conn->query($top_products_query);
if ($top_products_result) {
    while ($row = $top_products_result->fetch_assoc()) {
        $top_products[] = $row;
    }
}

// If no products data, create sample data
if (empty($top_products)) {
    $top_products = [
        ['name' => 'Classic Sourdough Bread', 'total_sold' => 250, 'total_revenue' => 1250, 'price' => 5.00],
        ['name' => 'Cinnamon Rolls', 'total_sold' => 220, 'total_revenue' => 1100, 'price' => 5.00],
        ['name' => 'Three Cheese and Basil', 'total_sold' => 180, 'total_revenue' => 720, 'price' => 4.00],
        ['name' => 'Espresso', 'total_sold' => 150, 'total_revenue' => 450, 'price' => 3.00],
        ['name' => 'Macchiato', 'total_sold' => 130, 'total_revenue' => 650, 'price' => 5.00],
        ['name' => 'Mocha', 'total_sold' => 120, 'total_revenue' => 720, 'price' => 6.00],
        ['name' => 'Frappuccino', 'total_sold' => 110, 'total_revenue' => 770, 'price' => 7.00],
        ['name' => 'Cold Brew', 'total_sold' => 100, 'total_revenue' => 500, 'price' => 5.00],
        ['name' => 'Matcha Latte', 'total_sold' => 90, 'total_revenue' => 540, 'price' => 6.00],
        ['name' => 'Chai Latte', 'total_sold' => 80, 'total_revenue' => 400, 'price' => 5.00]
    ];
}

// Latest 5 transactions with error handling
$transactions_query = "
    SELECT 
        o.id, 
        o.total_amount, 
        o.created_at, 
        o.status, 
        COALESCE(u.first_name, 'Guest') as first_name,
        COALESCE(u.last_name, '') as last_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.status IN ('Delivered', 'Picked-up')
    ORDER BY o.created_at DESC
    LIMIT 5
";
$transactions_result = $conn->query($transactions_query);
if ($transactions_result) {
    while ($row = $transactions_result->fetch_assoc()) {
        $row['customer_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        if (empty($row['customer_name'])) {
            $row['customer_name'] = 'Guest Customer';
        }
        $latest_transactions[] = $row;
    }
}

// Latest 5 orders with error handling
$latest_orders_query = "
    SELECT 
        o.id, 
        o.total_amount, 
        o.created_at, 
        o.status, 
        COALESCE(u.first_name, 'Guest') as first_name,
        COALESCE(u.last_name, '') as last_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
";
$latest_orders_result = $conn->query($latest_orders_query);
if ($latest_orders_result) {
    while ($row = $latest_orders_result->fetch_assoc()) {
        $row['customer_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        if (empty($row['customer_name'])) {
            $row['customer_name'] = 'Guest Customer';
        }
        $latest_orders[] = $row;
    }
}

// Calculate percentage changes (placeholder values for demo)
$stats['today_change'] = '+2% from yesterday';
$stats['net_change'] = '+8% from last month';
$stats['products_change'] = '+23 new this week';
$stats['pending_change'] = '+12% from last week';
$stats['orders_change'] = '+5% from last week';
$stats['bulk_change'] = '3 pending approval';
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
                            <i class="fas fa-dollar-sign card-icon"></i>
                        </div>
                        <div class="card-value">$<?php echo number_format($stats['today_income'], 0); ?></div>
                        <div class="card-change positive"><?php echo $stats['today_change']; ?></div>
                    </div>

                    <!-- Net Income -->
                    <div class="service-card">
                        <div class="card-header">
                            <span class="card-title">Net Income</span>
                            <i class="fas fa-chart-line card-icon"></i>
                        </div>
                        <div class="card-value">$<?php echo number_format($stats['net_income'], 0); ?></div>
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

                    <!-- Bulk Requests (Placeholder) -->
                    <div class="service-card placeholder">
                        <div class="card-header">
                            <span class="card-title">Bulk Requests</span>
                            <i class="fas fa-layer-group card-icon"></i>
                        </div>
                        <div class="card-value">45</div>
                        <div class="card-change"><?php echo $stats['bulk_change']; ?></div>
                    </div>
                </div>

                <!-- Sidebar: Calendar -->
                <div class="dashboard-sidebar">
                    <!-- Calendar Widget -->
                    <div class="calendar-card">
                        <div class="calendar-header">
                            <button class="calendar-nav" onclick="previousMonth()">&larr;</button>
                            <span class="calendar-month" id="calendar-month-year">Aug 2025</span>
                            <button class="calendar-nav" onclick="nextMonth()">&rarr;</button>
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
                                                    <div class="transaction-id">TXN<?php echo str_pad($transaction['id'], 3, '0', STR_PAD_LEFT); ?> • <?php echo date('M j', strtotime($transaction['created_at'])); ?></div>
                                                </div>
                                            </td>
                                            <td class="amount-cell">
                                                <div class="amount">$<?php echo number_format($transaction['total_amount'], 2); ?></div>
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
                                                    <div class="order-id">#<?php echo $order['id']; ?> • <?php echo date('M j', strtotime($order['created_at'])); ?></div>
                                                </div>
                                            </td>
                                            <td class="amount-cell">
                                                <div class="amount">$<?php echo number_format($order['total_amount'], 2); ?></div>
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

    <script src="dashboard.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const topProductsData = <?php echo json_encode($top_products); ?>;
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            generateCalendar();
            createCharts();
        });
    </script>
</body>
</html>
