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
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total 
                  FROM orders o
                  WHERE (o.status IN ('Delivered', 'Picked-up', 'Completed'))
                  AND (DATE(o.order_date) BETWEEN ? AND ?)";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $total_pages = ceil($total_records / $records_per_page);
    mysqli_stmt_close($count_stmt);
    
    $sql = "SELECT o.order_id, o.order_date, o.customer_name, o.payment_method, o.total_amount, o.status, o.delivery_method as order_type,
            o.pickup_date, o.delivery_date, o.customer_contact, o.customer_address
            FROM orders o
            WHERE (o.status IN ('Delivered', 'Picked-up', 'Completed'))
            AND (DATE(o.order_date) BETWEEN ? AND ?)
            ORDER BY o.$sort_field $sort_direction
            LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) { 
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ssii", $start_date, $end_date, $records_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Calculate totals
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
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <h3 onclick="sortSummary('revenue')" class="<?php echo $summary_sort === 'revenue' ? 'sorted ' . $summary_direction : ''; ?>">
                            Net Income
                        </h3>
                        <p class="amount" id="total-revenue">₱<?php echo number_format($total_revenue, 2); ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Revenue growth
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
                        <h3 onclick="sortSummary('orders')" class="<?php echo $summary_sort === 'orders' ? 'sorted ' . $summary_direction : ''; ?>">
                            Total Orders
                        </h3>
                        <p class="amount" id="total-orders"><?php echo $total_orders; ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Order volume up
                        </div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20m8-10H4"></path>
                            </svg>
                        </div>
                        <h3 onclick="sortSummary('average')" class="<?php echo $summary_sort === 'average' ? 'sorted ' . $summary_direction : ''; ?>">
                            Average Order Value
                        </h3>
                        <p class="amount" id="average-order-value">₱<?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?></p>
                        <p class="period"><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                        <div class="trend positive">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                <polyline points="17,6 23,6 23,12"></polyline>
                            </svg>
                            Value improving
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
                            Total Refunded
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
                <div class="charts-grid">
                    <div class="chart-card wide">
                        <div class="chart-header">
                            <span class="chart-title">Revenue by Transaction Type</span>
                            <span class="chart-subtitle">Total revenue from delivered and picked-up orders</span>
                        </div>
                        <canvas id="orderStatusChart"></canvas>
                    </div>
                    <div class="chart-card pie-narrow">
                        <div class="chart-header">
                            <span class="chart-title">Delivery Methods</span>
                            <span class="chart-subtitle">Pickup vs Delivery orders</span>
                        </div>
                        <canvas id="deliveryMethodsChart"></canvas>
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
                                    <tr onclick="viewTransaction(<?php echo $transaction['order_id']; ?>)" style="cursor: pointer;">
                                        <td><?php echo htmlspecialchars($transaction['order_id']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['order_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                        <td><?php 
                                            $paymentMethods = [
                                                '0' => 'Cash on Delivery',
                                                '1' => 'GCash',
                                                '2' => 'PayMaya',
                                                '3' => 'Bank Transfer'
                                            ];
                                            echo htmlspecialchars($paymentMethods[$transaction['payment_method']] ?? $transaction['payment_method']);
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
                    <tr onclick="viewTransaction(${transaction.order_id})" style="cursor: pointer;">
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
            fetch(`../homepage/get-order-details.php?id=${orderId}`)
                .then(response => response.json())
                .then(order => {
                    const modal = document.getElementById('transactionModal');
                    const details = document.getElementById('transactionDetails');
                    
                    // Format display date based on order type
                    const displayDate = order.order_type === 'Pick-up' ? 
                        `<div class="detail-row">
                            <div class="detail-label">Pickup Date:</div>
                            <div class="detail-value">${order.pickup_date || 'N/A'}</div>
                        </div>` : 
                        `<div class="detail-row">
                            <div class="detail-label">Delivery Date:</div>
                            <div class="detail-value">${order.delivery_date || 'N/A'}</div>
                        </div>`;
                    
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
                            <div class="detail-value">${order.customer_address || 'N/A'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Delivery Method:</div>
                            <div class="detail-value">${order.order_type}</div>
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
        let orderStatusChart = null;
        let deliveryMethodsChart = null;
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
                    renderOrderStatusChart(data.data.order_status);
                    renderDeliveryMethodsChart(data.data.delivery_methods);
                    renderTopProductsChart(data.data.top_products);
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

        // Render Order Status Chart
        function renderOrderStatusChart(chartData) {
            const ctx = document.getElementById('orderStatusChart').getContext('2d');
            
            if (orderStatusChart) {
                orderStatusChart.destroy();
            }
            
            if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            orderStatusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: chartData.data,
                        backgroundColor: chartData.colors,
                        borderColor: chartData.colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
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