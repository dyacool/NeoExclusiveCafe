<?php
    // Use admin-auth for authentication
    require_once __DIR__ . '/../../login/admin/admin-auth.php';

    require_once __DIR__ . "/../admin-includes/database.php";

    // Default date range (last 30 days)
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $selected_period = '30days';
    
    // Handle custom date range and period selection
    if (isset($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        $selected_period = 'custom';
    } else if (isset($_GET['period'])) {
        $selected_period = $_GET['period'];
        switch ($_GET['period']) {
            case '7days':
                $start_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
        }
    }

    // Remove hardcoded demo dates - use actual filter logic
    
    // Get sort parameters
    $sort_field = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
    $sort_direction = isset($_GET['direction']) ? $_GET['direction'] : 'desc';
    
    // Validate sort field to prevent SQL injection
    $allowed_sort_fields = ['order_id', 'order_date', 'customer_name', 'payment_method', 'total_amount', 'status'];
    if (!in_array($sort_field, $allowed_sort_fields)) {
        $sort_field = 'order_date';
    }
    
    // Validate sort direction
    if ($sort_direction != 'asc' && $sort_direction != 'desc') {
        $sort_direction = 'desc';
    }
    
    // Pagination setup
    $records_per_page = 20;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page); // Ensure page is at least 1
    $offset = ($page - 1) * $records_per_page;
    
    // Get total count for pagination (including both regular and bulk orders)
    $count_sql = "SELECT COUNT(*) as total FROM (
                    SELECT 1 FROM orders o
                    WHERE (o.status IN ('Delivered', 'Picked-up', 'Completed'))
                    AND (DATE(o.order_date) BETWEEN ? AND ?)
                    UNION ALL
                    SELECT 1 FROM bulk_orders bo
                    WHERE (bo.status = 'completed')
                    AND (DATE(bo.updated_at) BETWEEN ? AND ?)
                  ) as combined_count";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "ssss", $start_date, $end_date, $start_date, $end_date);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $total_pages = ceil($total_records / $records_per_page);
    mysqli_stmt_close($count_stmt);
    
    // Build combined query with both regular and bulk orders
    $sql = "SELECT 
            o.order_id, 
            o.order_date, 
            o.customer_name, 
            o.payment_method, 
            o.total_amount, 
            o.status, 
            o.delivery_method as order_type,
            o.pickup_date, 
            o.delivery_date, 
            o.customer_contact, 
            o.customer_address,
            'regular' as order_source
            FROM orders o
            WHERE (o.status IN ('Delivered', 'Picked-up', 'Completed'))
            AND (DATE(o.order_date) BETWEEN ? AND ?)
            UNION ALL
            SELECT 
            bo.unique_order_id as order_id,
            bo.updated_at as order_date,
            bo.name as customer_name,
            'Bulk Order' as payment_method,
            bo.total_amount,
            'Completed' as status,
            bo.order_type,
            NULL as pickup_date,
            NULL as delivery_date,
            bo.contact as customer_contact,
            bo.delivery_address as customer_address,
            'bulk' as order_source
            FROM bulk_orders bo
            WHERE (bo.status = 'completed')
            AND (DATE(bo.updated_at) BETWEEN ? AND ?)
            ORDER BY ";
    
    // Add sorting logic - map order_source to determine sort method
    if ($sort_field === 'order_date') {
        $sql .= "order_date $sort_direction";
    } else if ($sort_field === 'total_amount') {
        $sql .= "total_amount $sort_direction";
    } else if ($sort_field === 'customer_name') {
        $sql .= "customer_name $sort_direction";
    } else if ($sort_field === 'status') {
        $sql .= "status $sort_direction";
    } else {
        $sql .= "order_date $sort_direction";
    }
    
    $sql .= " LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) { 
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssssii", $start_date, $end_date, $start_date, $end_date, $records_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Calculate totals from both regular and bulk orders
    $total_revenue = 0;
    $total_orders = 0;
    $transactions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $total_revenue += $row['total_amount'];
        $total_orders++;
        $transactions[] = $row;
    }
    
    // Calculate average order value
    $average_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
    
    // Calculate bulk order specific totals
    $bulk_revenue = 0;
    $bulk_orders_count = 0;
    $bulk_sql = "SELECT COUNT(*) as count, SUM(total_amount) as total
                 FROM bulk_orders
                 WHERE status = 'completed'
                 AND DATE(updated_at) BETWEEN ? AND ?";
    $bulk_stmt = mysqli_prepare($conn, $bulk_sql);
    if ($bulk_stmt) {
        mysqli_stmt_bind_param($bulk_stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($bulk_stmt);
        $bulk_result = mysqli_stmt_get_result($bulk_stmt);
        if ($bulk_row = mysqli_fetch_assoc($bulk_result)) {
            $bulk_orders_count = $bulk_row['count'] ?? 0;
            $bulk_revenue = $bulk_row['total'] ?? 0;
        }
        mysqli_stmt_close($bulk_stmt);
    }
    
    // Calculate total refunded amount
    $total_refunded = 0;
    $refund_sql = "SELECT SUM(refund_amount) as total_refunded 
                   FROM order_refunds 
                   WHERE refund_status IN ('approved', 'completed')
                   AND DATE(created_at) BETWEEN ? AND ?";
    $refund_stmt = mysqli_prepare($conn, $refund_sql);
    if ($refund_stmt) {
        mysqli_stmt_bind_param($refund_stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($refund_stmt);
        $refund_result = mysqli_stmt_get_result($refund_stmt);
        if ($refund_row = mysqli_fetch_assoc($refund_result)) {
            $total_refunded = $refund_row['total_refunded'] ?? 0;
        }
        mysqli_stmt_close($refund_stmt);
    }
    
    // Calculate total expenses
    $total_expenses = 0;
    $expense_categories = [];
    $expenses_check = mysqli_query($conn, "SHOW TABLES LIKE 'expenses'");
    if (mysqli_num_rows($expenses_check) > 0) {
        $expenses_sql = "SELECT SUM(amount) as total_expenses 
                        FROM expenses 
                        WHERE DATE(created_at) BETWEEN ? AND ?";
        $expenses_stmt = mysqli_prepare($conn, $expenses_sql);
        if ($expenses_stmt) {
            mysqli_stmt_bind_param($expenses_stmt, "ss", $start_date, $end_date);
            mysqli_stmt_execute($expenses_stmt);
            $expenses_result = mysqli_stmt_get_result($expenses_stmt);
            if ($expenses_row = mysqli_fetch_assoc($expenses_result)) {
                $total_expenses = $expenses_row['total_expenses'] ?? 0;
            }
            mysqli_stmt_close($expenses_stmt);
        }
        
        // Get expense breakdown by category
        $category_sql = "SELECT category, SUM(amount) as total 
                        FROM expenses 
                        WHERE DATE(created_at) BETWEEN ? AND ?
                        GROUP BY category";
        $category_stmt = mysqli_prepare($conn, $category_sql);
        if ($category_stmt) {
            mysqli_stmt_bind_param($category_stmt, "ss", $start_date, $end_date);
            mysqli_stmt_execute($category_stmt);
            $category_result = mysqli_stmt_get_result($category_stmt);
            while ($category_row = mysqli_fetch_assoc($category_result)) {
                $expense_categories[$category_row['category']] = $category_row['total'];
            }
            mysqli_stmt_close($category_stmt);
        }
    }
    
    // Get Top 3 Best-Selling Products by Bulk Order Category
    $bulk_category_products = [];
    
    // First, get ALL unique purposes from the database with actual data (completed orders only)
    $purposes_sql = "SELECT DISTINCT TRIM(bo.purpose) as purpose, COUNT(*) as count
                     FROM bulk_orders bo
                     INNER JOIN bulk_order_items boi ON bo.id = boi.bulk_order_id
                     WHERE bo.purpose IS NOT NULL AND bo.purpose != ''
                     AND bo.status = 'completed'
                     GROUP BY TRIM(bo.purpose)";
    $purposes_result = mysqli_query($conn, $purposes_sql);
    $existing_purposes = [];
    if ($purposes_result) {
        while ($purpose_row = mysqli_fetch_assoc($purposes_result)) {
            $existing_purposes[] = [
                'purpose' => $purpose_row['purpose'],
                'count' => $purpose_row['count']
            ];
        }
    }
    
    error_log("=== BULK ORDER DEBUG ===");
    error_log("Existing purposes with counts (completed only): " . json_encode($existing_purposes));
    
    // Use the actual purposes from database instead of predefined categories
    $categories_to_use = [];
    foreach ($existing_purposes as $purpose_data) {
        $categories_to_use[] = $purpose_data['purpose'];
    }
    
    // If no purposes found, log and return empty
    if (empty($categories_to_use)) {
        error_log("WARNING: No completed bulk orders found with purposes!");
        $bulk_category_products = [];
    } else {
        foreach ($categories_to_use as $category) {
            // Query to get ALL bulk order products by category (completed orders only)
            // Note: Using product_price column as per database schema
            $bulk_products_sql = "SELECT 
                                    boi.product_name,
                                    SUM(boi.quantity) as total_quantity,
                                    SUM(boi.quantity * boi.product_price) as total_revenue,
                                    COUNT(DISTINCT bo.id) as order_count
                                FROM bulk_order_items boi
                                INNER JOIN bulk_orders bo ON boi.bulk_order_id = bo.id
                                WHERE TRIM(bo.purpose) = ?
                                AND bo.status = 'completed'
                                GROUP BY boi.product_name
                                ORDER BY total_quantity DESC
                                LIMIT 3";
            
            $bulk_products_stmt = mysqli_prepare($conn, $bulk_products_sql);
            if ($bulk_products_stmt) {
                mysqli_stmt_bind_param($bulk_products_stmt, "s", $category);
                mysqli_stmt_execute($bulk_products_stmt);
                $bulk_products_result = mysqli_stmt_get_result($bulk_products_stmt);
                
                $products = [];
                while ($product_row = mysqli_fetch_assoc($bulk_products_result)) {
                    $products[] = [
                        'product_name' => $product_row['product_name'],
                        'quantity' => (int)$product_row['total_quantity'],
                        'revenue' => (float)$product_row['total_revenue'],
                        'order_count' => (int)$product_row['order_count']
                    ];
                }
                
                error_log("Category '$category' (completed) found " . count($products) . " products: " . json_encode($products));
                
                // Only add category to chart if it has products
                if (count($products) > 0) {
                    $bulk_category_products[$category] = $products;
                }
                
                mysqli_stmt_close($bulk_products_stmt);
            }
        }
    }
    
    error_log("Final bulk_category_products (completed): " . json_encode($bulk_category_products));
    error_log("=== END BULK ORDER DEBUG ===");
    
    // Calculate total discounts applied
    $total_discounts = 0;
    $discount_sql = "SELECT SUM(discount_amount) as total_discounts 
                     FROM orders 
                     WHERE status IN ('Delivered', 'Picked-up', 'Completed')
                     AND DATE(order_date) BETWEEN ? AND ?
                     AND discount_amount > 0";
    $discount_stmt = mysqli_prepare($conn, $discount_sql);
    if ($discount_stmt) {
        mysqli_stmt_bind_param($discount_stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($discount_stmt);
        $discount_result = mysqli_stmt_get_result($discount_stmt);
        if ($discount_row = mysqli_fetch_assoc($discount_result)) {
            $total_discounts = $discount_row['total_discounts'] ?? 0;
        }
        mysqli_stmt_close($discount_stmt);
    }
    
    // Calculate total shipping fees collected
    $total_shipping_fees = 0;
    $shipping_sql = "SELECT SUM(shipping_fee) as total_shipping 
                     FROM orders 
                     WHERE status IN ('Delivered', 'Picked-up', 'Completed')
                     AND DATE(order_date) BETWEEN ? AND ?
                     AND shipping_fee > 0";
    $shipping_stmt = mysqli_prepare($conn, $shipping_sql);
    if ($shipping_stmt) {
        mysqli_stmt_bind_param($shipping_stmt, "ss", $start_date, $end_date);
        mysqli_stmt_execute($shipping_stmt);
        $shipping_result = mysqli_stmt_get_result($shipping_stmt);
        if ($shipping_row = mysqli_fetch_assoc($shipping_result)) {
            $total_shipping_fees = $shipping_row['total_shipping'] ?? 0;
        }
        mysqli_stmt_close($shipping_stmt);
    }
    
    // Calculate Net Profit: Revenue - Expenses - Refunds - Discounts
    $gross_revenue = $total_revenue + $bulk_revenue;
    $net_profit = $gross_revenue - $total_expenses - $total_refunded - $total_discounts;
    
    // Get summary card sort parameters
    $summary_sort = isset($_GET['summary_sort']) ? $_GET['summary_sort'] : '';
    $summary_direction = isset($_GET['summary_direction']) ? $_GET['summary_direction'] : 'desc';
    
    // Sort summary cards if requested
    if ($summary_sort) {
        if ($summary_sort === 'revenue') {
            // Already calculated, no need to sort
        } elseif ($summary_sort === 'orders') {
            // Already calculated, no need to sort
        } elseif ($summary_sort === 'average') {
            // Already calculated, no need to sort
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="transactions.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.7/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <title>Transactions</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="transactions-container">
        <div class="main-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <p class="page-subtitle">View sales reports and transaction history</p>
                </div>
            </div>

            <!-- Summary Section with Sortable Cards -->
            <div class="summary-section">
                <!-- Summary Cards -->
                <div class="summary-cards">

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                <circle cx="12" cy="12" r="10" opacity="0.2" fill="currentColor"></circle>
                            </svg>
                        </div>
                        <h3>Net Profit</h3>
                        <p class="amount" id="net-profit">₱<?php echo number_format($net_profit, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend <?php echo $net_profit >= 0 ? 'positive' : 'negative'; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <?php if ($net_profit >= 0): ?>
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                                <?php else: ?>
                                <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"></polyline>
                                <polyline points="17,18 23,18 23,12"></polyline>
                                <?php endif; ?>
                            </svg>
                            Revenue - Expenses - Refunds - Discounts
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <h3>Gross Revenue</h3>
                        <p class="amount" id="gross-revenue">₱<?php echo number_format($total_revenue + $bulk_revenue, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Total sales revenue
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <h3 onclick="sortSummary('revenue')" class="<?php echo $summary_sort === 'revenue' ? 'sorted ' . $summary_direction : ''; ?>">
                            Order Sales
                        </h3>
                        <p class="amount" id="total-revenue">₱<?php echo number_format($total_revenue, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Order Sales
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                        </div>
                        <h3>Bulk Orders Sales</h3>
                        <p class="amount" id="bulk-revenue">₱<?php echo number_format($bulk_revenue, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Bulk sales
                        </div>
                    </div>

                    

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 12V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7"></path>
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V8s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                <line x1="4" y1="22" x2="4" y2="15"></line>
                            </svg>
                        </div>
                        <h3>Total Expenses</h3>
                        <p class="amount" id="total-expenses">₱<?php echo number_format($total_expenses, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend negative">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"></polyline>
                                <polyline points="17,18 23,18 23,12"></polyline>
                            </svg>
                            Business expenses
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <h3 onclick="sortSummary('refunded')" class="<?php echo $summary_sort === 'refunded' ? 'sorted ' . $summary_direction : ''; ?>">
                            Total Refund Amount
                        </h3>
                        <p class="amount" id="total-refunded">₱<?php echo number_format($total_refunded, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend negative">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"></polyline>
                                <polyline points="17,18 23,18 23,12"></polyline>
                            </svg>
                            Approved refunds
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 6l-4 4-4-4"></path>
                                <path d="M8 14l4 4 4-4"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </div>
                        <h3>Total Order Discounts</h3>
                        <p class="amount" id="total-discounts">₱<?php echo number_format($total_discounts, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend negative">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"></polyline>
                                <polyline points="17,18 23,18 23,12"></polyline>
                            </svg>
                            Discounts applied
                        </div>
                    </div>

                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                <path d="M9 6h6"></path>
                            </svg>
                        </div>
                        <h3>Total Delivery Fees</h3>
                        <p class="amount" id="total-shipping-fees">₱<?php echo number_format($total_shipping_fees, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Shipping fees collected
                        </div>
                    </div>



                    
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="charts-grid">
                    <div class="chart-card wide">
                        <div class="chart-header">
                            <span class="chart-title">Revenue Trend</span>
                            <span class="chart-subtitle">Daily revenue over selected period</span>
                        </div>
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                    <div class="chart-card pie-narrow">
                        <div class="chart-header">
                            <span class="chart-title">Payment Methods</span>
                            <span class="chart-subtitle">Distribution by payment type</span>
                        </div>
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
                <div class="charts-grid charts-grid-three-col">
                    <div class="chart-card chart-card-third">
                        <div class="chart-header">
                            <span class="chart-title">Delivery Methods</span>
                            <span class="chart-subtitle">Pickup vs Delivery orders</span>
                        </div>
                        <canvas id="deliveryMethodsChart"></canvas>
                    </div>
                    <div class="chart-card chart-card-third">
                        <div class="chart-header">
                            <span class="chart-title">Revenue Breakdown</span>
                            <span class="chart-subtitle">Net profit vs expenses, refunds, and discounts</span>
                        </div>
                        <canvas id="revenueBreakdownChart"></canvas>
                    </div>
                    <div class="chart-card chart-card-third">
                        <div class="chart-header">
                            <span class="chart-title">Expense Categories</span>
                            <span class="chart-subtitle">Breakdown by category type</span>
                        </div>
                        <canvas id="expenseCategoriesChart"></canvas>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="chart-card full-width">
                        <div class="chart-header">
                            <span class="chart-title">Products Sales</span>
                            <span class="chart-subtitle">Best-selling products by quantity and revenue</span>
                        </div>
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="chart-card full-width">
                        <div class="chart-header">
                            <span class="chart-title">Top 3 Products by Bulk Order Category</span>
                            <span class="chart-subtitle">Best-selling products across Corporate Event, Wedding, Birthday Party, Business Supply, and Others</span>
                        </div>
                        <canvas id="bulkCategoryProductsChart" style="max-height: 600px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="controls-section">
                <div class="filter-group">
                    <label class="filter-label">Time Period:</label>
                    <div class="filter-buttons">
                        <button class="filter-btn <?php echo $selected_period == '7days' ? 'active' : ''; ?>" onclick="filterByPeriod('7days')">
                            Last 7 Days
                        </button>
                        <button class="filter-btn <?php echo $selected_period == '30days' ? 'active' : ''; ?>" onclick="filterByPeriod('30days')">
                            Last 30 Days
                        </button>
                        <button class="filter-btn <?php echo $selected_period == '90days' ? 'active' : ''; ?>" onclick="filterByPeriod('90days')">
                            Last 90 Days
                        </button>
                        <button class="filter-btn <?php echo $selected_period == 'custom' ? 'active' : ''; ?>" onclick="toggleCustomFilter()">
                            Custom Range
                        </button>
                    </div>
                </div>
                <div class="filter-group export-group">
                    <button class="export-btn" onclick="exportTransactions()" id="exportBtn">
                        <span class="export-normal">
                            <i class="fa-solid fa-download"></i> Export Transactions
                        </span>
                        <span class="export-loading" style="display: none;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Exporting...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Custom Date Filter -->
            <div id="custom-filter" class="custom-filter <?php echo $selected_period == 'custom' ? 'active' : ''; ?>">
                <div class="date-input-group">
                    <label class="filter-label">Start Date:</label>
                    <input type="date" id="start-date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="date-input-group">
                    <label class="filter-label">End Date:</label>
                    <input type="date" id="end-date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <button onclick="applyCustomFilter()" class="btn btn-primary">Apply Filter</button>
            </div>

            <!-- Transactions Table -->
            <div class="transactions-container-table">
                <div class="table-wrapper">
                    <table class="transaction-table" id="transactionTable">
                        <thead>
                            <tr>
                                <th onclick="sortTable('order_id')" class="<?php echo $sort_field === 'order_id' ? 'sorted ' . $sort_direction : ''; ?>">Order #</th>
                                <th onclick="sortTable('order_date')" class="<?php echo $sort_field === 'order_date' ? 'sorted ' . $sort_direction : ''; ?>">Date</th>
                                <th onclick="sortTable('customer_name')" class="<?php echo $sort_field === 'customer_name' ? 'sorted ' . $sort_direction : ''; ?>">Customer</th>
                                <th onclick="sortTable('payment_method')" class="<?php echo $sort_field === 'payment_method' ? 'sorted ' . $sort_direction : ''; ?>">Payment Method</th>
                                <th onclick="sortTable('status')" class="<?php echo $sort_field === 'status' ? 'sorted ' . $sort_direction : ''; ?>">Status</th>
                                <th onclick="sortTable('total_amount')" class="<?php echo $sort_field === 'total_amount' ? 'sorted ' . $sort_direction : ''; ?>">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-tbody">
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr onclick="viewTransaction('<?php echo addslashes($transaction['order_id']); ?>')" style="cursor: pointer;">
                                        <td>
                                            <?php echo htmlspecialchars($transaction['order_id']); ?>
                                            <?php if ($transaction['order_source'] === 'bulk'): ?>
                                                <span style="margin-left: 8px; padding: 3px 8px; background-color: #e8f4f8; color: #0d7a8f; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">BULK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['order_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                        <td><?php 
                                            // Handle bulk orders - they show 'Bulk Order' directly
                                            if ($transaction['order_source'] === 'bulk') {
                                                echo htmlspecialchars($transaction['payment_method']);
                                            } else {
                                                $paymentMethods = [
                                                    '0' => 'Cash on Delivery',
                                                    '1' => 'GCash',
                                                    '2' => 'PayMaya',
                                                    '3' => 'Bank Transfer'
                                                ];
                                                echo htmlspecialchars($paymentMethods[$transaction['payment_method']] ?? $transaction['payment_method']);
                                            }
                                        ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], ['_', '_'], $transaction['status'])); ?>">
                                                <?php echo htmlspecialchars($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-transactions">
                                        <div style="text-align: center; padding: 3rem;">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; opacity: 0.5;">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.35-4.35"></path>
                                            </svg>
                                            <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">No transactions found</h3>
                                            <p style="color: var(--gray-500);">No transactions found for the selected period</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            <span>Showing<?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> transactions</span>
                        </div>
                        
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => ($page - 1)])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => ($page + 1)])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="pagination-btn">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Hidden Print Section -->
    <div class="print-section" style="display: none;">
        <div class="print-title">Transactions</div>
        <table class="transaction-table" id="printTable">
            <!-- Table content will be copied here for printing -->
        </table>
    </div>

    <!-- Transaction Modal -->
    <div id="transactionModal" class="transaction-modal">
        <div class="modal-overlay" onclick="closeModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Transaction Details</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="transactionDetails" class="transaction-details"></div>
        </div>
    </div>

    <script>
        // Fetch transactions data via AJAX
        function fetchTransactions() {
            const urlParams = new URLSearchParams(window.location.search);
            const queryString = urlParams.toString();
            
            fetch(`get-transactions.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateTransactionsTable(data.data.transactions);
                        updateSummaryCards(data.data.summary);
                        updateFilterStates(data.data.filters);
                        updatePagination(data.data.pagination);
                    } else {
                        console.error('Error fetching transactions:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching transactions:', error);
                });
        }
        
        // Update transactions table
        function updateTransactionsTable(transactions) {
            const tbody = document.getElementById('transactions-tbody');
            
            if (transactions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="no-transactions">
                            <div style="text-align: center; padding: 3rem;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 1rem; opacity: 0.5;">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                <h3 style="color: var(--gray-700); margin-bottom: 0.5rem;">No transactions found</h3>
                                <p style="color: var(--gray-500);">No transactions found for the selected period</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            transactions.forEach(transaction => {
                const statusClass = transaction.status.toLowerCase().replace(/[ -]/g, '_');
                html += `
                    <tr onclick="viewTransaction('${transaction.order_id}')" style="cursor: pointer;">
                        <td>${escapeHtml(transaction.order_id)}</td>
                        <td>${formatDate(transaction.order_date)}</td>
                        <td>${escapeHtml(transaction.customer_name)}</td>
                        <td>${formatPaymentMethod(transaction.payment_method)}</td>
                        <td>
                            <span class="status-badge status-${statusClass}">
                                ${escapeHtml(transaction.status)}
                            </span>
                        </td>
                        <td>₱${parseFloat(transaction.total_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        // Update summary cards
        function updateSummaryCards(summary) {
            document.getElementById('total-revenue').textContent = `₱${parseFloat(summary.total_revenue).toFixed(2)}`;
            document.getElementById('total-orders').textContent = summary.total_orders;
            document.getElementById('average-order-value').textContent = `₱${parseFloat(summary.average_order_value).toFixed(2)}`;
            document.getElementById('total-refunded').textContent = `₱${parseFloat(summary.total_refunded || 0).toFixed(2)}`;
        }
        
        // Update filter states
        function updateFilterStates(filters) {
            // Update date inputs
            document.getElementById('start-date').value = filters.start_date;
            document.getElementById('end-date').value = filters.end_date;
            
            // Update period buttons
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            if (filters.selected_period === 'custom') {
                document.querySelector('.filter-btn[onclick*="toggleCustomFilter"]').classList.add('active');
            } else {
                document.querySelector(`.filter-btn[onclick*="${filters.selected_period}"]`).classList.add('active');
            }
            
            // Update custom filter visibility
            const customFilter = document.getElementById('custom-filter');
            if (filters.selected_period === 'custom') {
                customFilter.classList.add('active');
            } else {
                customFilter.classList.remove('active');
            }
        }
        
        // Update pagination
        function updatePagination(pagination) {
            // This will be handled by page reload for now
            // Could be enhanced with dynamic pagination update if needed
        }
        
        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
        }

        function formatPaymentMethod(paymentMethod) {
            const paymentMethods = {
                '0': 'Cash on Delivery',
                '1': 'GCash',
                '2': 'PayMaya',
                '3': 'Bank Transfer'
            };
            return paymentMethods[paymentMethod] || paymentMethod;
        }
        
        // Period filter functions
        function filterByPeriod(period) {
            event.preventDefault();
            
            // Update URL without reload
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('period', period);
            urlParams.delete('start_date');
            urlParams.delete('end_date');
            window.history.pushState({}, '', '?' + urlParams.toString());
            
            // Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Fetch updated data
            fetchTransactions();
            fetchAndRenderCharts();
        }

        function toggleCustomFilter() {
            const customFilter = document.getElementById('custom-filter');
            const isActive = customFilter.classList.contains('active');
            
            if (isActive) {
                customFilter.classList.remove('active');
            } else {
                customFilter.classList.add('active');
                // Update filter button state
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
            }
        }

        function applyCustomFilter() {
            event.preventDefault();
            
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (startDate && endDate) {
                // Update URL without reload
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('start_date', startDate);
                urlParams.set('end_date', endDate);
                urlParams.delete('period');
                window.history.pushState({}, '', '?' + urlParams.toString());
                
                // Update active button state
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelector('.filter-btn[onclick*="toggleCustomFilter"]').classList.add('active');
                
                // Fetch updated data
                fetchTransactions();
                fetchAndRenderCharts();
            } else {
                alert('Please select both start and end dates');
            }
        }

        // Sort functions
        function sortTable(field) {
            event.preventDefault();
            
            const currentSortField = '<?php echo $sort_field; ?>';
            const currentSortDirection = '<?php echo $sort_direction; ?>';
            
            let newDirection = 'asc';
            if (field === currentSortField) {
                newDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            }
            
            // Update URL without reload
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', field);
            urlParams.set('direction', newDirection);
            window.history.pushState({}, '', '?' + urlParams.toString());
            
            // Fetch updated data
            fetchTransactions();
            fetchAndRenderCharts();
        }
        
        function sortSummary(field) {
            event.preventDefault();
            
            const currentSummarySort = '<?php echo $summary_sort; ?>';
            const currentSummaryDirection = '<?php echo $summary_direction; ?>';
            
            let newDirection = 'desc';
            if (field === currentSummarySort) {
                newDirection = currentSummaryDirection === 'asc' ? 'desc' : 'asc';
            }
            
            // Update URL without reload
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('summary_sort', field);
            urlParams.set('summary_direction', newDirection);
            window.history.pushState({}, '', '?' + urlParams.toString());
            
            // Fetch updated data
            fetchTransactions();
            fetchAndRenderCharts();
        }
        


        // Print function
        function printReport() {
            const originalTable = document.getElementById('transactionTable').cloneNode(true);
            const printTable = document.getElementById('printTable');
            
            // Since we removed the Actions column, no need to remove it from print table
            printTable.innerHTML = originalTable.innerHTML;
            window.print();
        }

        // Transaction modal functions
        function viewTransaction(orderId) {
            // Detect if this is a bulk order (IDs starting with "BO")
            const isBulkOrder = orderId.toString().startsWith('BO');
            const endpoint = isBulkOrder 
                ? `../homepage/get-bulk-order-details.php?id=${orderId}`
                : `../homepage/get-order-details.php?id=${orderId}`;
            
            fetch(endpoint)
                .then(response => response.json())
                .then(order => {
                    const modal = document.getElementById('transactionModal');
                    const details = document.getElementById('transactionDetails');
                    
                    // Format display date based on order type
                    let displayDate = '';
                    if (isBulkOrder) {
                        // Bulk orders use purpose and date_needed fields
                        displayDate = `
                            <div class="detail-row">
                                <div class="detail-label">Purpose:</div>
                                <div class="detail-value">${order.purpose || 'N/A'}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Date Needed:</div>
                                <div class="detail-value">${order.pickup_date || 'N/A'}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Time Needed:</div>
                                <div class="detail-value">${order.pickup_time || 'N/A'}</div>
                            </div>`;
                    } else {
                        // Regular orders use pickup/delivery date
                        displayDate = order.order_type === 'Pick-up' ? 
                            `<div class="detail-row">
                                <div class="detail-label">Pickup Date:</div>
                                <div class="detail-value">${order.pickup_date || 'N/A'}</div>
                            </div>` : 
                            `<div class="detail-row">
                                <div class="detail-label">Delivery Date:</div>
                                <div class="detail-value">${order.delivery_date || 'N/A'}</div>
                            </div>`;
                    }
                    
                    // Build order items HTML
                    let itemsHtml = '';
                    if (order.items && order.items.length > 0) {
                        itemsHtml = `
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        order.items.forEach(item => {
                            const subtotal = (item.price * item.quantity).toFixed(2);
                            itemsHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                    <td>₱${subtotal}</td>
                                </tr>
                            `;
                        });
                        
                        itemsHtml += `
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" style="text-align: right; font-weight: 600;">Total Amount:</td>
                                        <td style="font-weight: 600;">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;
                    }
                    
                    // Build delivery address for bulk orders
                    const addressDisplay = isBulkOrder && order.delivery_address 
                        ? order.delivery_address 
                        : (order.customer_address || 'N/A');
                    
                    details.innerHTML = `
                        <div class="detail-row">
                            <div class="detail-label">Order #:</div>
                            <div class="detail-value">${order.order_id}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div class="detail-value">${order.order_date}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Customer Name:</div>
                            <div class="detail-value">${order.customer_name}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Contact:</div>
                            <div class="detail-value">${order.customer_contact || 'N/A'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Address:</div>
                            <div class="detail-value">${addressDisplay}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Delivery Method:</div>
                            <div class="detail-value">${order.delivery_method || order.order_type || 'N/A'}</div>
                        </div>
                        ${displayDate}
                        <div class="detail-row">
                            <div class="detail-label">Payment Method:</div>
                            <div class="detail-value">${formatPaymentMethod(order.payment_method) || 'Cash on Delivery'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">${order.status}</div>
                        </div>
                        ${itemsHtml}
                    `;
                    
                    modal.style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error fetching transaction details:', error);
                    alert('Error loading transaction details. Please try again.');
                });
        }

        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }

        // Chart instances
        let revenueTrendChart = null;
        let paymentMethodsChart = null;
        let revenueBreakdownChart = null;
        let deliveryMethodsChart = null;
        let expenseCategoriesChart = null;
        let topProductsChart = null;

        // Fetch and render all charts
        async function fetchAndRenderCharts() {
            // Show loading states
            const chartCards = document.querySelectorAll('.chart-card');
            chartCards.forEach(card => card.classList.add('loading'));
            
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const queryString = urlParams.toString();
                
                const response = await fetch(`get-chart-data.php?${queryString}`);
                const data = await response.json();
                
                if (data.success) {
                    renderRevenueTrendChart(data.data.revenue_trend);
                    renderPaymentMethodsChart(data.data.payment_methods);
                    renderRevenueBreakdownChart();
                    renderDeliveryMethodsChart(data.data.delivery_methods);
                    renderExpenseCategoriesChart();
                    renderTopProductsChart(data.data.top_products);
                    renderBulkCategoryProductsChart();
                } else {
                    console.error('Error fetching chart data:', data.error);
                }
            } catch (error) {
                console.error('Error fetching chart data:', error);
            } finally {
                // Remove loading states
                chartCards.forEach(card => card.classList.remove('loading'));
            }
        }

        // Render Revenue Trend Chart
        function renderRevenueTrendChart(chartData) {
            const ctx = document.getElementById('revenueTrendChart').getContext('2d');
            
            if (revenueTrendChart) {
                revenueTrendChart.destroy();
            }
            
            if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                // Show empty state
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            revenueTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: chartData.data,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true,
                        spanGaps: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `₱${context.parsed.y.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Render Payment Methods Chart
        function renderPaymentMethodsChart(chartData) {
            const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
            
            if (paymentMethodsChart) {
                paymentMethodsChart.destroy();
            }
            
            if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            paymentMethodsChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Render Revenue Breakdown Chart
        function renderRevenueBreakdownChart() {
            const ctx = document.getElementById('revenueBreakdownChart').getContext('2d');
            
            if (revenueBreakdownChart) {
                revenueBreakdownChart.destroy();
            }
            
            // Get values from summary cards
            const netProfit = parseFloat('<?php echo $net_profit; ?>');
            const expenses = parseFloat('<?php echo $total_expenses; ?>');
            const refunds = parseFloat('<?php echo $total_refunded; ?>');
            const discounts = parseFloat('<?php echo $total_discounts; ?>');
            
            // Create chart data
            const labels = ['Net Profit', 'Expenses', 'Refunds', 'Discounts'];
            const data = [netProfit, expenses, refunds, discounts];
            const colors = ['#22c55e', '#ef4444', '#f59e0b', '#8b5cf6'];
            
            revenueBreakdownChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => Math.abs(a) + Math.abs(b), 0);
                                    const percentage = ((Math.abs(value) / total) * 100).toFixed(1);
                                    return `${context.label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Render Delivery Methods Chart
        function renderDeliveryMethodsChart(chartData) {
            const ctx = document.getElementById('deliveryMethodsChart').getContext('2d');
            
            if (deliveryMethodsChart) {
                deliveryMethodsChart.destroy();
            }
            
            if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            deliveryMethodsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Render Expense Categories Chart
        function renderExpenseCategoriesChart() {
            const ctx = document.getElementById('expenseCategoriesChart').getContext('2d');
            
            if (expenseCategoriesChart) {
                expenseCategoriesChart.destroy();
            }
            
            // Get values from PHP
            const fixedCosts = parseFloat('<?php echo $expense_categories['Fixed Costs'] ?? 0; ?>');
            const variableCosts = parseFloat('<?php echo $expense_categories['Variable Costs'] ?? 0; ?>');
            const overheadCosts = parseFloat('<?php echo $expense_categories['Overhead Costs'] ?? 0; ?>');
            
            const labels = ['Fixed Costs', 'Variable Costs', 'Overhead Costs'];
            const data = [fixedCosts, variableCosts, overheadCosts];
            const colors = ['#3b82f6', '#22c55e', '#f59e0b'];
            
            expenseCategoriesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Render Top Products Chart
        function renderTopProductsChart(chartData) {
            const ctx = document.getElementById('topProductsChart').getContext('2d');
            
            if (topProductsChart) {
                topProductsChart.destroy();
            }
            
            if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            topProductsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Quantity Sold',
                            data: chartData.quantity_data,
                            backgroundColor: 'rgba(34, 197, 94, 0.8)',
                            borderColor: '#22c55e',
                            borderWidth: 1,
                            xAxisID: 'x1'
                        },
                        {
                            label: 'Revenue (₱)',
                            data: chartData.revenue_data,
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            xAxisID: 'x'
                        }
                    ]
                },
                options: {
                    indexAxis: 'y', // This makes it horizontal
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.datasetIndex === 0) {
                                        return `Quantity: ${context.parsed.x} units`;
                                    } else {
                                        return `Revenue: ₱${context.parsed.x.toLocaleString()}`;
                                    }
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            display: true,
                            position: 'bottom',
                            title: {
                                display: true,
                                text: 'Revenue (₱)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x1: {
                            type: 'linear',
                            display: true,
                            position: 'top',
                            title: {
                                display: true,
                                text: 'Quantity Sold'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Products'
                            },
                            ticks: {
                                callback: function(value, index) {
                                    const label = this.getLabelForValue(value);
                                    // Check if screen width is 1440px or below
                                    if (window.innerWidth <= 1440) {
                                        const maxLength = 15;
                                        if (label.length > maxLength) {
                                            // Split label into chunks of maxLength
                                            const chunks = [];
                                            for (let i = 0; i < label.length; i += maxLength) {
                                                chunks.push(label.substr(i, maxLength));
                                            }
                                            return chunks;
                                        }
                                    }
                                    return label;
                                },
                                font: {
                                    size: window.innerWidth <= 1440 ? 11 : 12
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Render Bulk Category Products Chart
        let bulkCategoryProductsChart = null;
        function renderBulkCategoryProductsChart() {
            const ctx = document.getElementById('bulkCategoryProductsChart').getContext('2d');
            
            if (bulkCategoryProductsChart) {
                bulkCategoryProductsChart.destroy();
            }
            
            // Prepare data for the chart
            const bulkData = <?php echo json_encode($bulk_category_products); ?>;
            
            console.log('Bulk Data:', bulkData); // Debug log
            
            // Check if we have any data at all
            if (!bulkData || Object.keys(bulkData).length === 0) {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No bulk order data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            // Build labels and datasets
            const labels = [];
            const quantityData = [];
            const revenueData = [];
            
            // Define colors for each category (with fallback for unknown categories)
            const categoryColors = {
                'Corporate Event': { quantity: 'rgba(59, 130, 246, 0.8)', revenue: 'rgba(96, 165, 250, 0.8)' },
                'Wedding': { quantity: 'rgba(236, 72, 153, 0.8)', revenue: 'rgba(244, 114, 182, 0.8)' },
                'Birthday Party': { quantity: 'rgba(168, 85, 247, 0.8)', revenue: 'rgba(192, 132, 252, 0.8)' },
                'Business Supply': { quantity: 'rgba(34, 197, 94, 0.8)', revenue: 'rgba(74, 222, 128, 0.8)' },
                'Others': { quantity: 'rgba(251, 146, 60, 0.8)', revenue: 'rgba(253, 186, 116, 0.8)' }
            };
            
            // Fallback colors for categories not in predefined list
            const fallbackColors = [
                { quantity: 'rgba(99, 102, 241, 0.8)', revenue: 'rgba(129, 140, 248, 0.8)' },
                { quantity: 'rgba(239, 68, 68, 0.8)', revenue: 'rgba(248, 113, 113, 0.8)' },
                { quantity: 'rgba(59, 130, 246, 0.8)', revenue: 'rgba(96, 165, 250, 0.8)' },
                { quantity: 'rgba(16, 185, 129, 0.8)', revenue: 'rgba(52, 211, 153, 0.8)' },
                { quantity: 'rgba(245, 158, 11, 0.8)', revenue: 'rgba(251, 191, 36, 0.8)' }
            ];
            
            let fallbackIndex = 0;
            
            // Create arrays to store background colors
            const quantityColors = [];
            const revenueColors = [];
            
            // Process each category
            Object.keys(bulkData).forEach(category => {
                const products = bulkData[category];
                
                // Get colors for this category (use predefined or fallback)
                let colors = categoryColors[category];
                if (!colors) {
                    colors = fallbackColors[fallbackIndex % fallbackColors.length];
                    fallbackIndex++;
                }
                
                // Add category separator label
                labels.push(`━━ ${category} ━━`);
                quantityData.push(null);
                revenueData.push(null);
                quantityColors.push('transparent');
                revenueColors.push('transparent');
                
                // Add products for this category (all products since we removed 'No Data' filtering)
                products.forEach(product => {
                    labels.push(`   ${product.product_name}`);
                    quantityData.push(product.quantity);
                    revenueData.push(product.revenue);
                    quantityColors.push(colors.quantity);
                    revenueColors.push(colors.revenue);
                });
            });
            
            bulkCategoryProductsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Quantity Sold',
                            data: quantityData,
                            backgroundColor: quantityColors,
                            borderColor: quantityColors.map(c => c.replace('0.8', '1')),
                            borderWidth: 1,
                            xAxisID: 'x1',
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Revenue (₱)',
                            data: revenueData,
                            backgroundColor: revenueColors,
                            borderColor: revenueColors.map(c => c.replace('0.8', '1')),
                            borderWidth: 1,
                            xAxisID: 'x',
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    size: 13,
                                    weight: '600'
                                },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'rect'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                title: function(context) {
                                    const label = context[0].label;
                                    // Don't show tooltip for category separators
                                    if (label.includes('━━')) {
                                        return '';
                                    }
                                    return label.trim();
                                },
                                label: function(context) {
                                    // Don't show tooltip for category separators
                                    if (context.label.includes('━━')) {
                                        return '';
                                    }
                                    
                                    if (context.datasetIndex === 0) {
                                        return `Quantity: ${context.parsed.x} units`;
                                    } else {
                                        return `Revenue: ₱${context.parsed.x.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                    }
                                },
                                beforeBody: function(context) {
                                    // Don't show tooltip for category separators
                                    if (context[0].label.includes('━━')) {
                                        return '';
                                    }
                                }
                            },
                            filter: function(tooltipItem) {
                                // Hide tooltip for category separators
                                return !tooltipItem.label.includes('━━');
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            display: true,
                            position: 'bottom',
                            title: {
                                display: true,
                                text: 'Revenue (₱)',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x1: {
                            type: 'linear',
                            display: true,
                            position: 'top',
                            title: {
                                display: true,
                                text: 'Quantity Sold',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Products by Category',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            ticks: {
                                callback: function(value, index) {
                                    const label = this.getLabelForValue(value);
                                    
                                    // Style category separators differently
                                    if (label.includes('━━')) {
                                        return label;
                                    }
                                    
                                    // Truncate long product names on smaller screens
                                    if (window.innerWidth <= 1440) {
                                        const maxLength = 20;
                                        if (label.length > maxLength) {
                                            return label.substr(0, maxLength) + '...';
                                        }
                                    }
                                    return label;
                                },
                                font: function(context) {
                                    const label = context.tick.label;
                                    // Bold and larger font for category separators
                                    if (typeof label === 'string' && label.includes('━━')) {
                                        return {
                                            size: 13,
                                            weight: '700',
                                            family: 'Inter, sans-serif'
                                        };
                                    }
                                    return {
                                        size: 11,
                                        weight: '500'
                                    };
                                },
                                color: function(context) {
                                    const label = context.tick.label;
                                    // Different color for category separators
                                    if (typeof label === 'string' && label.includes('━━')) {
                                        return '#1f2937';
                                    }
                                    return '#6b7280';
                                },
                                padding: 8
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    layout: {
                        padding: {
                            left: 10,
                            right: 10,
                            top: 10,
                            bottom: 10
                        }
                    }
                }
            });
        }
        
        // Initialize charts on page load
        // Export functionality
        function exportTransactions() {
            const exportBtn = document.getElementById('exportBtn');
            const normalState = exportBtn.querySelector('.export-normal');
            const loadingState = exportBtn.querySelector('.export-loading');
            
            // Show loading state
            normalState.style.display = 'none';
            loadingState.style.display = 'inline-flex';
            exportBtn.disabled = true;
            
            // Get current URL parameters to maintain the same filters
            const urlParams = new URLSearchParams(window.location.search);
            const exportUrl = 'export-transactions.php?' + urlParams.toString();
            
            // Create a temporary link and click it to download
            const link = document.createElement('a');
            link.href = exportUrl;
            link.download = 'transactions.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Hide loading state after a short delay (since download is instant)
            setTimeout(() => {
                normalState.style.display = 'inline-flex';
                loadingState.style.display = 'none';
                exportBtn.disabled = false;
            }, 1500);
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetchAndRenderCharts();
        });

    </script>
</body>
</html>