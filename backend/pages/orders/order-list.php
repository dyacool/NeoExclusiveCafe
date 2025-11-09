<?php
    // Use admin-auth for authentication (it loads database.php and SessionManager in correct order)
    require_once __DIR__ . '/../../login/admin/admin-auth.php';

    // Pagination settings
    $orders_per_page = 15;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $orders_per_page;

    // Get filter parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Base query
    $sql = "SELECT * FROM orders";
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Add status filter if not 'all'
    if ($status_filter !== 'all') {
        // Map display status to database status
        $db_status = ($status_filter == 'Pending') ? 'Confirmed' : $status_filter;
        $where_clauses[] = "LOWER(TRIM(status)) = LOWER(?)";
        $params[] = $db_status;
        $types .= "s";
    }
    
    // Add search filter if provided
    if (!empty($search)) {
        $where_clauses[] = "(customer_name LIKE ? OR order_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    }
    
    // Combine where clauses if any
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add order by and pagination
    $sql .= " ORDER BY order_date DESC LIMIT ? OFFSET ?";
    
    // Add pagination parameters
    $params[] = $orders_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    // Prepare and execute the statement
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM orders";
    $count_where_clauses = [];
    $count_params = [];
    $count_types = "";
    
    // Add same filters for count query
    if ($status_filter !== 'all') {
        // Map display status to database status
        $db_status = ($status_filter == 'Pending') ? 'Confirmed' : $status_filter;
        $count_where_clauses[] = "LOWER(TRIM(status)) = LOWER(?)";
        $count_params[] = $db_status;
        $count_types .= "s";
    }
    
    if (!empty($search)) {
        $count_where_clauses[] = "(customer_name LIKE ? OR order_id LIKE ?)";
        $count_params[] = "%$search%";
        $count_params[] = "%$search%";
        $count_types .= "ss";
    }
    
    if (!empty($count_where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $count_where_clauses);
    }
    
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if (!empty($count_params)) {
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_orders = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_orders / $orders_per_page);
    
    // Count orders by status for filter badges
    $status_counts = [
        'all' => 0,
        'Pending' => 0,
        'Preparing' => 0,
        'Ready for Delivery' => 0,
        'Out for Delivery' => 0,
        'Ready for Pick-up' => 0,
        'Picked-up' => 0,
        'Delivered' => 0
    ];
    
    $count_sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $count_result = mysqli_query($conn, $count_sql);
    
    while ($count_row = mysqli_fetch_assoc($count_result)) {
        // Map database status to display status
        $display_status = ($count_row['status'] == 'Confirmed') ? 'Pending' : $count_row['status'];
        
        if (isset($status_counts[$display_status])) {
            $status_counts[$display_status] = $count_row['count'];
            $status_counts['all'] += $count_row['count'];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="order-list.css">
    <title>Order Management</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <!-- FIXED: Wrap content in container with proper class -->
    <div class="order-list-container">
        <div class="main-container">
            <!-- Header Section -->
            <div class="page-header">
                <div class="header-content">
                    <p class="page-subtitle">View and manage customer orders</p>
                </div>

                                
                <div class="search-group">
                    <div class="search-container">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="search-input" class="search-input" placeholder="Search by order # or customer name" value="<?php echo htmlspecialchars($search); ?>" onkeyup="handleSearch(event)">
                    </div>
                </div>
            </div>
            
            <!-- Controls Section -->
            <div class="controls-section">
                <div class="filter-group">
                    <label class="filter-label">Filter by Status:</label>
                    
                    <!-- Mobile Dropdown (visible on 425px and below) -->
                    <select id="mobile-filter-dropdown" onchange="filterByStatus(this.value)" class="mobile-filter-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Orders (<?php echo $status_counts['all']; ?>)</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending (<?php echo $status_counts['Pending']; ?>)</option>
                        <option value="Preparing" <?php echo $status_filter == 'Preparing' ? 'selected' : ''; ?>>Preparing (<?php echo $status_counts['Preparing']; ?>)</option>
                        <option value="Ready for Delivery" <?php echo $status_filter == 'Ready for Delivery' ? 'selected' : ''; ?>>Ready for Delivery (<?php echo $status_counts['Ready for Delivery']; ?>)</option>
                        <option value="Out for Delivery" <?php echo $status_filter == 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery (<?php echo $status_counts['Out for Delivery']; ?>)</option>
                        <option value="Ready for Pick-up" <?php echo $status_filter == 'Ready for Pick-up' ? 'selected' : ''; ?>>Ready for Pick-up (<?php echo $status_counts['Ready for Pick-up']; ?>)</option>
                        <option value="Picked-up" <?php echo $status_filter == 'Picked-up' ? 'selected' : ''; ?>>Picked-up (<?php echo $status_counts['Picked-up']; ?>)</option>
                        <option value="Delivered" <?php echo $status_filter == 'Delivered' ? 'selected' : ''; ?>>Delivered (<?php echo $status_counts['Delivered']; ?>)</option>
                    </select>
                    
                    <!-- Desktop Buttons (hidden on 425px and below) -->
                    <div class="filter-buttons">
                        <button onclick="filterByStatus('all')" 
                                class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>" 
                                data-status="all">
                            <span class="filter-count" id="count-all"><?php echo $status_counts['all']; ?></span>
                            All Orders
                        </button>
                        <button onclick="filterByStatus('Pending')" 
                                class="filter-btn <?php echo $status_filter == 'Pending' ? 'active' : ''; ?>" 
                                data-status="Pending">
                            <span class="filter-count" id="count-Pending"><?php echo $status_counts['Pending']; ?></span>
                            Pending
                        </button>
                        <button onclick="filterByStatus('Preparing')" 
                                class="filter-btn <?php echo $status_filter == 'Preparing' ? 'active' : ''; ?>" 
                                data-status="Preparing">
                            <span class="filter-count" id="count-Preparing"><?php echo $status_counts['Preparing']; ?></span>
                            Preparing
                        </button>
                        <button onclick="filterByStatus('Ready for Delivery')" 
                                class="filter-btn <?php echo $status_filter == 'Ready for Delivery' ? 'active' : ''; ?>" 
                                data-status="Ready for Delivery">
                            <span class="filter-count" id="count-Ready for Delivery"><?php echo $status_counts['Ready for Delivery']; ?></span>
                            Ready for Delivery
                        </button>
                        <button onclick="filterByStatus('Out for Delivery')" 
                                class="filter-btn <?php echo $status_filter == 'Out for Delivery' ? 'active' : ''; ?>" 
                                data-status="Out for Delivery">
                            <span class="filter-count" id="count-Out for Delivery"><?php echo $status_counts['Out for Delivery']; ?></span>
                            Out for Delivery
                        </button>
                        <button onclick="filterByStatus('Ready for Pick-up')" 
                                class="filter-btn <?php echo $status_filter == 'Ready for Pick-up' ? 'active' : ''; ?>" 
                                data-status="Ready for Pick-up">
                            <span class="filter-count" id="count-Ready for Pick-up"><?php echo $status_counts['Ready for Pick-up']; ?></span>
                            Ready for Pick-up
                        </button>
                        <button onclick="filterByStatus('Picked-up')" 
                                class="filter-btn <?php echo $status_filter == 'Picked-up' ? 'active' : ''; ?>" 
                                data-status="Picked-up">
                            <span class="filter-count" id="count-Picked-up"><?php echo $status_counts['Picked-up']; ?></span>
                            Picked-up
                        </button>
                        <button onclick="filterByStatus('Delivered')" 
                                class="filter-btn <?php echo $status_filter == 'Delivered' ? 'active' : ''; ?>" 
                                data-status="Delivered">
                            <span class="filter-count" id="count-Delivered"><?php echo $status_counts['Delivered']; ?></span>
                            Delivered
                        </button>
                    </div>
                </div>
                
                <!-- Auto-Status Toggle -->
                <div class="auto-status-toggle-container">
                    <label class="toggle-label">
                        <span class="toggle-text">Toggle auto-status</span>
                        <div class="toggle-switch">
                            <input type="checkbox" id="auto-status-toggle" class="toggle-input">
                            <span class="toggle-slider"></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="orders-container">
                <!-- Polling Loading Indicator -->
                <div id="polling-loading-indicator">
                    <div class="spinner"></div>
                    <span>Updating...</span>
                </div>
                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr> 
                                <th>Order #</th>
                                <th>Date Placed</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Delivery/Pickup</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="orders-tbody">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr onclick="window.location.href='view-orders.php?order_id=<?php echo $row['order_id']; ?>'">
                                        <td data-label="Order #"><?php echo htmlspecialchars($row['order_id']); ?></td>
                                        <td data-label="Date Placed"><?php echo date('m-d-Y', strtotime($row['order_date'])); ?></td>
                                        <td data-label="Customer"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td data-label="Contact"><?php echo htmlspecialchars($row['customer_contact']); ?></td>
                                        <td data-label="Items"><?php echo htmlspecialchars($row['total_items']); ?></td>
                                        <td data-label="Total">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td data-label="Payment"><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                        <td data-label="Delivery/Pickup">
                                            <div class="delivery-info-wrapper">
                                                <span class="delivery-method"><?php echo htmlspecialchars($row['delivery_method']); ?></span>
                                                <?php 
                                                    $date = !empty($row['delivery_date']) ? $row['delivery_date'] : $row['pickup_date'];
                                                    $time = !empty($row['delivery_time']) ? $row['delivery_time'] : '00:00:00';
                                                    
                                                    // Display date and time
                                                    echo '<span class="delivery-datetime">';
                                                    if (!empty($date) && $date !== '0000-00-00') {
                                                        echo date('m-d-Y', strtotime($date));
                                                        if (!empty($row['delivery_time'])) {
                                                            echo ' at ' . date('h:i A', strtotime($row['delivery_time']));
                                                        }
                                                    } else {
                                                        echo 'Not specified';
                                                    }
                                                    echo '</span>';
                                                    
                                                    // Calculate warning based on date/time and status
                                                    $warning_html = '';
                                                    $status = trim($row['status']); // Trim whitespace
                                                    
                                                    // Debug: Add HTML comment to see what's happening
                                                    echo '<!-- DEBUG: Order ID: ' . $row['order_id'] . ', Status: [' . $status . '], Length: ' . strlen($status) . ', Date: ' . $date . ' -->';
                                                    
                                                    // Only show warnings if we have a valid date
                                                    if (!empty($date) && $date !== '0000-00-00') {
                                                        try {
                                                            $current_datetime = new DateTime();
                                                            $today = new DateTime(date('Y-m-d'));
                                                            $tomorrow = new DateTime(date('Y-m-d', strtotime('+1 day')));
                                                            $delivery_date_only = new DateTime($date);
                                                            
                                                            // Check if there's a specific time set
                                                            $has_specific_time = !empty($row['delivery_time']) && $row['delivery_time'] !== '00:00:00';
                                                            
                                                            if ($has_specific_time) {
                                                                // Original logic for orders with specific times
                                                                $delivery_datetime = new DateTime($date . ' ' . $time);
                                                                
                                                                // Check if delivery/pickup date has passed and order is still confirmed/preparing/ready for delivery
                                                                if ($delivery_datetime < $current_datetime && 
                                                                    in_array(strtolower($status), ['confirmed', 'preparing', 'ready for delivery', 'ready for pick-up'])) {
                                                                    $warning_html = '<br><span class="warning-badge critical" style="background-color: red; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">OVERDUE</span>';
                                                                }
                                                                // Check if delivery/pickup is tomorrow and status is still confirmed/preparing
                                                                elseif ($delivery_date_only->format('Y-m-d') == $tomorrow->format('Y-m-d') && 
                                                                        in_array(strtolower($status), ['confirmed', 'preparing'])) {
                                                                    $warning_html = '<br><span class="warning-badge urgent" style="background-color: orange; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TOMORROW</span>';
                                                                }
                                                                // Check if delivery/pickup is today and status is still confirmed/preparing
                                                                elseif ($delivery_date_only->format('Y-m-d') == $today->format('Y-m-d') && 
                                                                        in_array(strtolower($status), ['confirmed', 'preparing'])) {
                                                                    $warning_html = '<br><span class="warning-badge today" style="background-color: yellow; color: black; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TODAY</span>';
                                                                }
                                                            } else {
                                                                // Logic for orders without specific times
                                                                $current_hour = (int)$current_datetime->format('H');
                                                                $business_end_hour = 21; // 9 PM - adjust this to your business hours
                                                                
                                                                if (in_array(strtolower($status), ['confirmed', 'preparing', 'ready for delivery', 'ready for pick-up'])) {
                                                                    // If delivery date has passed, it's overdue
                                                                    if ($delivery_date_only->format('Y-m-d') < $today->format('Y-m-d')) {
                                                                        $warning_html = '<br><span class="warning-badge critical" style="background-color: red; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">OVERDUE</span>';
                                                                    }
                                                                    // If delivery is today
                                                                    elseif ($delivery_date_only->format('Y-m-d') == $today->format('Y-m-d')) {
                                                                        // If it's beyond business hours, mark as overdue
                                                                        if ($current_hour >= $business_end_hour) {
                                                                            $warning_html = '<br><span class="warning-badge critical" style="background-color: red; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">OVERDUE</span>';
                                                                        } else {
                                                                            // Still within business hours, mark as due today
                                                                            $warning_html = '<br><span class="warning-badge today" style="background-color: yellow; color: black; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TODAY</span>';
                                                                        }
                                                                    }
                                                                    // If delivery is tomorrow and status is still confirmed/preparing
                                                                    elseif ($delivery_date_only->format('Y-m-d') == $tomorrow->format('Y-m-d') && 
                                                                            in_array(strtolower($status), ['confirmed', 'preparing'])) {
                                                                        $warning_html = '<br><span class="warning-badge urgent" style="background-color: orange; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">DUE TOMORROW</span>';
                                                                    }
                                                                }
                                                            }
                                                        } catch (Exception $e) {
                                                            // If date parsing fails, no warning needed for confirmed orders
                                                        }
                                                    }
                                                    
                                                    echo $warning_html;
                                                ?>
                                            </div>
                                        </td>
                                        <td data-label="Status" onclick="event.stopPropagation();">
                                            <form method="POST" action="update-status.php" class="status-form">
                                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                                <input type="hidden" name="redirect_to" value="order-list.php">
                                                <select name="status" onchange="this.form.submit()" class="status-badge-select status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                                    <?php
                                                        if($row['delivery_method'] == "Pick-up"){
                                                            $statuses = ["Pending", "Preparing", "Ready for Pick-up", "Picked-up"];
                                                        }elseif($row['delivery_method'] == "Delivery"){
                                                            $statuses = ["Pending", "Preparing", "Ready for Delivery", "Out for Delivery", "Delivered"];
                                                        }
                                                        foreach ($statuses as $status) {
                                                            // Map display status to database status for comparison
                                                            $db_status = ($status == 'Pending') ? 'Confirmed' : $status;
                                                            $selected = ($row['status'] == $db_status) ? 'selected' : '';
                                                            $value = ($status == 'Pending') ? 'Confirmed' : $status;
                                                            echo "<option value=\"$value\" $selected>$status</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </form>
                                        </td>

                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="no-orders">
                                        <div class="empty-state">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.35-4.35"></path>
                                            </svg>
                                            <h3>No orders found</h3>
                                            <p>No orders match your current filters</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php 
                    // Build URL parameters
                    $url_params = [];
                    if ($status_filter !== 'all') {
                        $url_params[] = 'status=' . urlencode($status_filter);
                    }
                    if (!empty($search)) {
                        $url_params[] = 'search=' . urlencode($search);
                    }
                    $url_query = !empty($url_params) ? '&' . implode('&', $url_params) : '';
                    ?>
                    
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $url_query; ?>" class="pagination-btn">Previous</a>
                    <?php endif; ?>
                    
                    <?php 
                    // Show page numbers with ellipsis for large ranges
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?page=1<?php echo $url_query; ?>" class="pagination-btn">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $url_query; ?>" 
                           class="pagination-btn <?php echo ($current_page == $i) ? 'current' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $url_query; ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $url_query; ?>" class="pagination-btn">Next</a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    <p>Showing <?php echo min($offset + $orders_per_page, $total_orders); ?> of <?php echo $total_orders; ?> orders</p>
                    
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;
        function fetchOrders() {
            const urlParams = new URLSearchParams(window.location.search);
            const queryString = urlParams.toString();
            
            fetch(`get-orders.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateOrdersTable(data.data.orders);
                        updateStatusCounts(data.data.status_counts);
                        updateFilterStates(data.data.filters);
                    } else {
                        console.error('Error fetching orders:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching orders:', error);
                });
        }
        
        // Update orders table
        function updateOrdersTable(orders) {
            const tbody = document.getElementById('orders-tbody');
            
            if (orders.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="no-orders">
                            <div class="empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                <h3>No orders found</h3>
                                <p>No orders match your current filters</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            orders.forEach(order => {
                const statusClass = order.status.toLowerCase().replace(/ /g, '-');
                const date = !isEmpty(order.delivery_date) ? order.delivery_date : order.pickup_date;
                const time = !isEmpty(order.delivery_time) ? order.delivery_time : '00:00:00';
                const dateFormatted = formatDate(date);
                const timeFormatted = !isEmpty(order.delivery_time) ? ' at ' + formatTime(order.delivery_time) : '';
                
                // Calculate warning based on date/time and status
                let warningHtml = '';
                if (date && date !== '0000-00-00') {
                    const currentDate = new Date();
                    const deliveryDateTime = new Date(date + 'T' + time);
                    const today = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate());
                    const tomorrow = new Date(today.getTime() + 24 * 60 * 60 * 1000);
                    const deliveryDateOnly = new Date(date + 'T00:00:00');
                    
                    const status = order.status;
                    
                    // Check if delivery/pickup date has passed and order is still confirmed/preparing/ready
                    if (deliveryDateTime < currentDate && 
                        ['Confirmed', 'Preparing', 'Ready for Delivery', 'Ready for Pick-up'].includes(status)) {
                        warningHtml = '<br><span class="warning-badge critical">🚨 OVERDUE</span>';
                    }
                    // Check if delivery/pickup is tomorrow and status is still confirmed/preparing
                    else if (deliveryDateOnly.toDateString() === tomorrow.toDateString() && 
                             ['Confirmed', 'Preparing'].includes(status)) {
                        warningHtml = '<br><span class="warning-badge urgent">⚠️ DUE TOMORROW</span>';
                    }
                    // Check if delivery/pickup is today and status is still confirmed/preparing
                    else if (deliveryDateOnly.toDateString() === today.toDateString() && 
                             ['Confirmed', 'Preparing'].includes(status)) {
                        warningHtml = '<br><span class="warning-badge today">⏰ DUE TODAY</span>';
                    }
                }
                
                // Generate status options based on delivery method
                let statusOptions = '';
                const statuses = order.delivery_method === 'Pick-up' 
                    ? ['Pending', 'Preparing', 'Ready for Pick-up', 'Picked-up']
                    : ['Pending', 'Preparing', 'Ready for Delivery', 'Out for Delivery', 'Delivered'];
                
                statuses.forEach(status => {
                    const selected = status === order.status ? 'selected' : '';
                    statusOptions += `<option value="${escapeHtml(status)}" ${selected}>${escapeHtml(status)}</option>`;
                });
                
                html += `
                    <tr onclick="window.location.href='view-orders.php?order_id=${order.order_id}'">
                        <td data-label="Order #">${escapeHtml(order.order_id)}</td>
                        <td data-label="Date Placed">${formatDate(order.order_date)}</td>
                        <td data-label="Customer">${escapeHtml(order.customer_name)}</td>
                        <td data-label="Contact">${escapeHtml(order.customer_contact)}</td>
                        <td data-label="Items">${escapeHtml(order.total_items)}</td>
                        <td data-label="Total">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td data-label="Payment">${escapeHtml(order.payment_method)}</td>
                        <td data-label="Delivery/Pickup">
                            ${escapeHtml(order.delivery_method)}<br>
                            ${dateFormatted}${timeFormatted}${warningHtml}
                        </td>
                        <td data-label="Status" onclick="event.stopPropagation();">
                            <form method="POST" action="update-status.php" class="status-form">
                                <input type="hidden" name="order_id" value="${order.order_id}">
                                <input type="hidden" name="redirect_to" value="order-list.php">
                                <select name="status" onchange="this.form.submit()" class="status-badge-select status-${statusClass}">
                                    ${statusOptions}
                                </select>
                            </form>
                        </td>
                        <td data-label="Actions">
                            <a href="view-orders.php?order_id=${order.order_id}" class="view-btn">View Details</a>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        // Update status counts
        function updateStatusCounts(statusCounts) {
            Object.keys(statusCounts).forEach(status => {
                const countElement = document.getElementById(`count-${status}`);
                if (countElement) {
                    countElement.textContent = statusCounts[status];
                }
            });
        }
        
        // Update filter states
        function updateFilterStates(filters) {
            // Update search input
            document.getElementById('search-input').value = filters.search || '';
            
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.querySelector(`[data-status="${filters.status_filter}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
        }
        
        // Filter by status
        function filterByStatus(status) {
            // Update URL without reload, reset to page 1 when filtering
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('status', status);
            urlParams.delete('page'); // Reset to page 1 when filtering
            window.history.pushState({}, '', '?' + urlParams.toString());
            
            // Reload page to apply filter
            location.reload();
        }
        
        // Handle search input
        function handleSearch(event) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = event.target.value;
                
                // Update URL without reload, reset to page 1 when searching
                const urlParams = new URLSearchParams(window.location.search);
                if (searchTerm) {
                    urlParams.set('search', searchTerm);
                } else {
                    urlParams.delete('search');
                }
                urlParams.delete('page'); // Reset to page 1 when searching
                window.history.pushState({}, '', '?' + urlParams.toString());
                
                // Reload page to apply search
                location.reload();
            }, 300); // Debounce search
        }
        
        // Helper functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateString) {
            if (!dateString || dateString === '0000-00-00') return 'N/A';
            const date = new Date(dateString);
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            return `${month}-${day}-${year}`;
        }
        
        function formatTime(timeString) {
            if (!timeString || timeString === '00:00:00') return '';
            const time = new Date(`2000-01-01T${timeString}`);
            return time.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }
        
        function isEmpty(value) {
            return value === null || value === undefined || value === '';
        }
        
        // Auto-Status Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('auto-status-toggle');
            
            if (!toggle) return;
            
            // Load current toggle state on page load
            fetch('toggle-auto-status.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toggle.checked = data.enabled;
                }
            })
            .catch(error => {
                console.error('Error loading toggle state:', error);
            });
            
            // Handle toggle change
            toggle.addEventListener('change', function() {
                const enabled = this.checked;
                
                fetch('toggle-auto-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ enabled: enabled })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(
                            'Auto-status ' + (enabled ? 'enabled' : 'disabled'), 
                            'success'
                        );
                    } else {
                        // Revert toggle on error
                        this.checked = !enabled;
                        showNotification(
                            'Error updating setting: ' + (data.error || 'Unknown error'), 
                            'error'
                        );
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert toggle on error
                    this.checked = !enabled;
                    showNotification('Connection error. Please try again.', 'error');
                });
            });
        });
        
        // Show notification toast
        function showNotification(message, type = 'success') {
            // Remove existing notification if any
            const existing = document.querySelector('.toggle-notification');
            if (existing) {
                existing.remove();
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `toggle-notification ${type}`;
            notification.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${type === 'success' 
                        ? '<path d="M20 6L9 17l-5-5"></path>' 
                        : '<path d="M18 6L6 18M6 6l12 12"></path>'}
                </svg>
                <span>${message}</span>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>

    <!-- AJAX Polling System for Order Updates -->
    <link rel="stylesheet" href="../../assets/css/order-list-polling.css">
    <script src="../../assets/js/order-list-polling.js"></script>
    <script>
        // Initialize AJAX polling for order list updates
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Order List] Initializing AJAX polling system');
            
            // Get current filter state from URL/page
            const urlParams = new URLSearchParams(window.location.search);
            const currentStatus = urlParams.get('status') || '<?php echo $status_filter; ?>';
            const currentSearch = urlParams.get('search') || '<?php echo $search; ?>';
            const currentPage = parseInt(urlParams.get('page') || '<?php echo $current_page; ?>');
            
            // Initialize poller
            const poller = new OrderListPoller({
                pollInterval: 5000, // 5 seconds
                initialStatus: currentStatus,
                initialSearch: currentSearch,
                initialPage: currentPage
            });
            
            // Start polling
            poller.start();
            
            // Update poller when filters change
            window.updatePollerFilters = function(filters) {
                poller.updateFilters(filters);
            };
            
            console.log('[Order List] Polling system initialized');
        });
    </script>
</body>
</html>
