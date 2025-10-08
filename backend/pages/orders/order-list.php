<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: ../../login/admin/admin-login.php");
        exit();
    }

    require_once __DIR__ . "/../admin-includes/database.php";

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
        $where_clauses[] = "status = ?";
        $params[] = $status_filter;
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
    
    // Add order by
    $sql .= " ORDER BY order_date DESC";
    
    // Prepare and execute the statement
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
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
        if (isset($status_counts[$count_row['status']])) {
            $status_counts[$count_row['status']] = $count_row['count'];
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
            </div>

            <!-- Orders Table -->
            <div class="orders-container">
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-tbody">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_contact']); ?></td>
                                        <td><?php echo htmlspecialchars($row['total_items']); ?></td>
                                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                        <td>
                                            <?php 
                                                echo htmlspecialchars($row['delivery_method']) . '<br>';
                                                $date = !empty($row['delivery_date']) ? $row['delivery_date'] : $row['pickup_date'];
                                                $time = !empty($row['delivery_time']) ? $row['delivery_time'] : '00:00:00';
                                                
                                                // Display date and time
                                                echo date('M d, Y', strtotime($date));
                                                if (!empty($row['delivery_time'])) {
                                                    echo ' at ' . date('h:i A', strtotime($row['delivery_time']));
                                                }
                                                
                                                // Calculate warning based on date/time and status
                                                $current_datetime = new DateTime();
                                                $delivery_datetime = new DateTime($date . ' ' . $time);
                                                $today = new DateTime(date('Y-m-d'));
                                                $tomorrow = new DateTime(date('Y-m-d', strtotime('+1 day')));
                                                $delivery_date_only = new DateTime($date);
                                                
                                                $warning_html = '';
                                                $status = $row['status'];
                                                
                                                // Check if delivery/pickup date has passed and order is still pending/preparing/ready for delivery
                                                if ($delivery_datetime < $current_datetime && 
                                                    in_array($status, ['Pending', 'Preparing', 'Ready for Delivery', 'Ready for Pick-up'])) {
                                                    $warning_html = '<br><span class="warning-badge critical">OVERDUE</span>';
                                                }
                                                // Check if delivery/pickup is tomorrow and status is still pending
                                                elseif ($delivery_date_only->format('Y-m-d') == $tomorrow->format('Y-m-d') && $status == 'Pending') {
                                                    $warning_html = '<br><span class="warning-badge urgent">DUE TOMORROW</span>';
                                                }
                                                // Check if delivery/pickup is today and status is still pending
                                                elseif ($delivery_date_only->format('Y-m-d') == $today->format('Y-m-d') && $status == 'Pending') {
                                                    $warning_html = '<br><span class="warning-badge today">DUE TODAY</span>';
                                                }
                                                
                                                echo $warning_html;
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-orders.php?order_id=<?php echo $row['order_id']; ?>" class="view-btn">View Details</a>
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
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;
        
        // Fetch orders data via AJAX
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
                    
                    // Check if delivery/pickup date has passed and order is still pending/preparing/ready
                    if (deliveryDateTime < currentDate && 
                        ['Pending', 'Preparing', 'Ready for Delivery', 'Ready for Pick-up'].includes(status)) {
                        warningHtml = '<br><span class="warning-badge critical">🚨 OVERDUE</span>';
                    }
                    // Check if delivery/pickup is tomorrow and status is still pending
                    else if (deliveryDateOnly.toDateString() === tomorrow.toDateString() && status === 'Pending') {
                        warningHtml = '<br><span class="warning-badge urgent">⚠️ DUE TOMORROW</span>';
                    }
                    // Check if delivery/pickup is today and status is still pending
                    else if (deliveryDateOnly.toDateString() === today.toDateString() && status === 'Pending') {
                        warningHtml = '<br><span class="warning-badge today">⏰ DUE TODAY</span>';
                    }
                }
                
                html += `
                    <tr>
                        <td>${escapeHtml(order.order_id)}</td>
                        <td>${formatDate(order.order_date)}</td>
                        <td>${escapeHtml(order.customer_name)}</td>
                        <td>${escapeHtml(order.customer_contact)}</td>
                        <td>${escapeHtml(order.total_items)}</td>
                        <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>${escapeHtml(order.payment_method)}</td>
                        <td>
                            ${escapeHtml(order.delivery_method)}<br>
                            ${dateFormatted}${timeFormatted}${warningHtml}
                        </td>
                        <td>
                            <span class="status-badge status-${statusClass}">
                                ${escapeHtml(order.status)}
                            </span>
                        </td>
                        <td>
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
            event.preventDefault();
            
            // Update URL without reload
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('status', status);
            window.history.pushState({}, '', '?' + urlParams.toString());
            
            // Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Fetch updated data
            fetchOrders();
        }
        
        // Handle search input
        function handleSearch(event) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = event.target.value;
                
                // Update URL without reload
                const urlParams = new URLSearchParams(window.location.search);
                if (searchTerm) {
                    urlParams.set('search', searchTerm);
                } else {
                    urlParams.delete('search');
                }
                window.history.pushState({}, '', '?' + urlParams.toString());
                
                // Fetch updated data
                fetchOrders();
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
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
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
    </script>
</body>
</html>
