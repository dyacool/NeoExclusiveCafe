<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: ../../login/admin/admin-login.php");
        exit();
    }

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
    
    $sql = "SELECT o.order_id, o.order_date, o.customer_name, o.payment_method, o.total_amount, o.status, o.delivery_method as order_type,
            o.pickup_date, o.delivery_date, o.customer_contact, o.customer_address
            FROM orders o
            WHERE (o.status IN ('Delivered', 'Picked-up'))
            AND (DATE(o.order_date) BETWEEN ? AND ?)
            ORDER BY o.$sort_field $sort_direction";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
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
                
                <div class="header-actions">
                    <div class="action-group">
                        <button onclick="printReport()" class="btn btn-secondary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Print Report
                        </button>
                    </div>
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
                            <svg class="sort-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
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
                            <svg class="sort-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
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
                            <svg class="sort-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-tbody">
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['order_id']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['order_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], ['_', '_'], $transaction['status'])); ?>">
                                                <?php echo htmlspecialchars($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                        <td>
                                            <button onclick="viewTransaction(<?php echo $transaction['order_id']; ?>)" class="view-btn">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-transactions">
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
                        <td colspan="7" class="no-transactions">
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
                    <tr>
                        <td>${escapeHtml(transaction.order_id)}</td>
                        <td>${formatDate(transaction.order_date)}</td>
                        <td>${escapeHtml(transaction.customer_name)}</td>
                        <td>${escapeHtml(transaction.payment_method)}</td>
                        <td>
                            <span class="status-badge status-${statusClass}">
                                ${escapeHtml(transaction.status)}
                            </span>
                        </td>
                        <td>₱${parseFloat(transaction.total_amount).toFixed(2)}</td>
                        <td>
                            <button onclick="viewTransaction(${transaction.order_id})" class="view-btn">View</button>
                        </td>
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
        }
        


        // Print function
        function printReport() {
            const originalTable = document.getElementById('transactionTable').cloneNode(true);
            const printTable = document.getElementById('printTable');
            
            // Remove actions column from print table
            const actionColumnIndex = 6; // 7th column (0-indexed)
            
            // Remove header
            const headerRow = originalTable.querySelector('thead tr');
            if (headerRow) {
                headerRow.deleteCell(actionColumnIndex);
            }
            
            // Remove action cells from body
            const bodyRows = originalTable.querySelectorAll('tbody tr');
            bodyRows.forEach(row => {
                if (row.cells.length > actionColumnIndex) {
                    row.deleteCell(actionColumnIndex);
                }
            });
            
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
                            <div class="detail-value">${order.payment_method || 'Cash on Delivery'}</div>
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
    </script>
</body>
</html>
