<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database includes removed - using static data only
require_once __DIR__ . "/../../login/admin/admin-auth.php";

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
    'total_users' => 0
];

// Initialize arrays
$top_products = [];
$latest_transactions = [];
$latest_orders = [];

// Database connection removed - using static data only

// Total Users (using same query as admin-homepage.php)
// Total Users - using default value since users table doesn't exist
$stats['total_users'] = 0;

// All stats using static values - no database queries
$stats['net_income'] = 0;
$stats['today_income'] = 0;
$stats['total_orders'] = 0;
$stats['pending_orders'] = 0;
$stats['total_products'] = 0;

// Top 10 products - using sample data since order_items table doesn't exist
$top_products = [];

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

// Latest 5 transactions - using static data
$latest_transactions = [];

// Latest 5 orders - using static data
$latest_orders = [];

// Products with today availability - using static data
$availtoday_products = [];

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
                                                    <div class="transaction-id">TXN<?php echo str_pad($transaction['id'], 3, '0', STR_PAD_LEFT); ?></div>
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
                                                    <div class="order-id">#<?php echo $order['id']; ?></div>
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

                <!-- Products with Today Availability -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Products with Today Availability</h3>
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
