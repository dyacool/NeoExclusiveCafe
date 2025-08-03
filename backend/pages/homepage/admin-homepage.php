<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Fetch statistics
$stats = [];

// Total Users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
$stats['total_users'] = $users_result->fetch_assoc()['total_users'];

// Total Income
$income_query = "SELECT COALESCE(SUM(total_amount), 0) as total_income FROM orders WHERE status IN ('Delivered', 'Picked-up')";
$income_result = $conn->query($income_query);
$stats['total_income'] = $income_result->fetch_assoc()['total_income'];

// Total Orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = $conn->query($orders_query);
$stats['total_orders'] = $orders_result->fetch_assoc()['total_orders'];

// Orders in Progress
$progress_query = "SELECT COUNT(*) as in_progress FROM orders WHERE status NOT IN ('Completed', 'Delivered', 'Picked-up')";
$progress_result = $conn->query($progress_query);
$stats['in_progress'] = $progress_result->fetch_assoc()['in_progress'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <!-- CSS files -->
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="../admin-includes/navbar/admin-navigation.css">
    <link rel="stylesheet" href="admin-homepage.css">
    <link rel="stylesheet" href="calendar.css">
    <link rel="stylesheet" href="chatbot.css">
    <link rel="stylesheet" href="../counts.css">

    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <!-- Load jQuery and jQuery UI first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- Then load Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Finally load FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <!-- Navbar JS -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Spectral", serif !important;
            background-color: #f5f5f5;
        }

        .Main {
            margin-left: 80px; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            background-color: #f5f5f5;
            padding: 0;
        }

        .sidebar:not(.collapsed) ~ .Main {
            margin-left: 250px; 
        }

        .main-container {
            display: flex;
            flex-direction: column;
            padding: 20px;
            margin-top: 20px; 
        }

        .container2{
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 20px;
        }

        /* Calendar Event Styles */
        .fc-event {
            cursor: pointer;
            padding: 2px 4px;
        }

        .fc-event-title {
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-time {
            font-size: 0.8em;
            opacity: 0.8;
        }

        .fc-event.completed {
            opacity: 0.8;
        }

        /* Date Limit Controls */
        .date-limit-controls {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }

        .limit-input-group {
            display: flex;
            gap: 10px;
        }

        .limit-input {
            padding: 0 8px;
            border: 1px solid #ddd;
            height: 40px;
            border-radius: 4px;
            width: 100px;
        }

        .not-accepting-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* Not Accepting Orders Overlay */
        .not-accepting-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: #d32f2f;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .Main {
                margin-left: 0;
            }

            .sidebar:not(.collapsed) ~ .Main {
                margin-left: 0;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 20px;
            }

            .order-limit-control {
                width: 100%;
                justify-content: center;
            }

            .calendar-controls {
                flex-direction: column;
            }

            .calendar-btn {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
            }

            .items-table {
                font-size: 0.9em;
            }

            .confirmation-content {
                width: 95%;
                margin: 20% auto;
            }
        }


        .modal-content {
            position: relative;
            padding-bottom: 70px; /* Make room for footer */
        }



        .confirmation-modal .modal-content {
            padding-bottom: 20px;
        }

        .confirmation-modal .modal-footer {
            position: static;
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        #faq-management-container h2 {
            color: #2f603c;
            margin-bottom: 18px;
        }
        #faq-table th, #faq-table td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        #faq-table td {
            background: #fff;
        }
        .faq-action-btn {
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .faq-action-btn.delete {
            background: #f44336;
        }
        .faq-action-btn.save {
            background: #2196F3;
        }
        .faq-action-btn.cancel {
            background: #888;
        }
        .dashboard-flex {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        @media (max-width: 1100px) {
            .dashboard-flex {
                flex-direction: column;
            }
            .dashboard-right, .dashboard-left {
                width: 100%;
                min-width: 0;
            }
        }

        /* Knowledge Base Styles */
        #knowledge-preview {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            max-height: 300px;
            overflow-y: auto;
        }
        
        #knowledge-preview h4 {
            margin-top: 0;
            color: #2f603c;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        #knowledge-preview a {
            color: #0078ff;
            text-decoration: underline;
            font-weight: bold;
            cursor: pointer;
            word-break: break-all;
        }
        
        #knowledge-preview a:hover {
            text-decoration: none;
        }
        
        #knowledge-content {
            min-height: 200px;
            padding: 12px;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .kb-helper-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
        }

        .fade-in {
        opacity: 0;
        animation: fadeIn 1.5s ease forwards;
        }

    </style>
</head>
<body>
    <?php include "../admin-includes/navbar/navbar.php"; ?>

    <div class="main-container dashboard-flex fade-in">
        <div class="container1">
            <div class="content bg-teal">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['total_users']); ?></h2>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="content bg-orange">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2>₱<?php echo number_format($stats['total_income'], 2); ?></h2>
                    <p>Net Income</p>
                </div>
            </div>

            <div class="content bg-purple">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['total_orders']); ?></h2>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="content bg-blue">
                <div class="container1-icon ibg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['in_progress']); ?></h2>
                    <p>Orders in Progress</p>
                </div>
            </div>
        </div>

        <div class="container2">
            <div class="dashboard-left">
                <div class="dashboard-header">
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="dashboard-content">
                    <!-- Calendar Section -->
                    <div class="calendar-section">
                        <div class="top-controls">
                            <div class="order-limit-control">
                                <h3>Daily Order Limit:</h3>
                                <input type="number" id="dailyLimit" min="1">
                                <button onclick="updateDailyLimit()">Save</button>
                            </div>
                            <div class="calendar-controls">
                                <button class="calendar-btn completed" onclick="toggleCompletedOrders()">
                                    <span>✓</span> Show Completed Orders
                                </button>
                            </div>
                        </div>
                        <div class="calendar-container">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-right" id="knowledge-base-container">
                <h1>Chatbot Knowledge Base</h1>
                <p class="kb-helper-text">Update the information below to teach your chatbot about your cafe. Include details about products, services, hours, policies, etc. URLs will be displayed as clickable links in the chat.</p>
                <form id="knowledge-form">
                    <textarea id="knowledge-content" name="content" class="faq-input" style="width:100%;" placeholder="Enter information about your cafe, products, services, policies, etc..." required></textarea>
                    <button type="submit" class="update-btn" style="margin-top:10px;">Save Edits</button>
                </form>
            </div>
        </div>
    </div>

    <div class="order-details-modal" id="orderModal">
        <div class="modal-content">
            <div class="close-btnn">
                <span class="close">&times;</span>
            </div>
            <div id="orderInfo"></div>
            <div class="modal-footer">
                <button id="completeOrderBtn" class="complete-btn" style="display: none;">
                    Mark Order as Complete
                </button>
            </div>
        </div>
    </div>

    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content2">
            <div class="close-btnn">
                <span class="close ver2">&times;</span>
            </div>
            <div class="confirmation-contents">
                <h3>Complete Order</h3>
                <p>Are you sure you want to mark this order as completed?</p>
            </div>
            <div class="modal-footer">
                <button id="confirmComplete" class="confirm-btn">Yes</button>
                <button id="cancelComplete" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let calendar;
        let dateLimits = {};
        let notAcceptingCount = 0;
        let showCompletedOrders = false;
        
        // Function to convert plain text URLs to clickable links
        function linkifyText(text) {
            // More comprehensive regex for URLs
            const urlRegex = /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/gi;
            
            // Replace URLs with clickable links
            return text.replace(urlRegex, function(url) {
                const href = url.startsWith('http') ? url : 'https://' + url;
                return `<a href="${href}" target="_blank" rel="noopener noreferrer" style="color: #007bff; text-decoration: underline; cursor: pointer;">${url}</a>`;
            });
        }
        
        // Define the cell update function globally so it can be called from anywhere
        function updateCalendarCell(arg) {
            // Check if date is in the past
            const cellDate = new Date(arg.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to start of day for fair comparison
            
            // Clear any previous styling first
            arg.el.style.backgroundColor = '';
            arg.el.style.color = '';
            arg.el.style.cursor = '';
            arg.el.title = '';
            arg.el.classList.remove('not-accepting-orders');
            arg.el.classList.remove('past-date');
            arg.el.style.border = '';
            
            // Remove any existing overlay
            const existingOverlay = arg.el.querySelector('.not-accepting-overlay');
            if (existingOverlay) {
                existingOverlay.remove();
            }
            
            if (cellDate < today) {
                // Style past dates
                arg.el.style.backgroundColor = '#f5f5f5';
                arg.el.style.color = '#999';
                arg.el.style.cursor = 'not-allowed';
                arg.el.classList.add('past-date');
                return;
            }

            // Check if date has limit 0 (not accepting orders)
            const dateStr = arg.date.toISOString().split('T')[0];
            
            if (dateLimits && dateLimits[dateStr]) {
                const dateInfo = dateLimits[dateStr];
                
                if (dateInfo.limit === 0 || dateInfo.status === 'not_accepting') {
                    arg.el.style.backgroundColor = '#ffebee';
                    arg.el.style.color = '#d32f2f';
                    arg.el.style.cursor = 'not-allowed';
                    arg.el.style.border = '2px solid #d32f2f';
                    arg.el.title = 'Not Accepting Orders';
                    arg.el.classList.add('not-accepting-orders');
                    
                    const overlay = document.createElement('div');
                    overlay.className = 'not-accepting-overlay';
                    overlay.innerHTML = '✕';
                    arg.el.appendChild(overlay);
                }
            }
        }
        
        // Define the fetchDateLimits function globally
        function fetchDateLimits(start, end) {
            const startStr = start.toISOString().split('T')[0];
            const endStr = end.toISOString().split('T')[0];
            
            console.log('Fetching date limits for range:', startStr, 'to', endStr);
            
            fetch(`get-date-limits.php?start=${startStr}&end=${endStr}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const jsonStr = text.replace(/<!--[\s\S]*?-->/g, '').trim();
                        const data = JSON.parse(jsonStr);
                        console.log('Parsed date limits:', data);
                        
                        if (data.success) {
                            // Reset the global dateLimits object
                            dateLimits = {};
                            
                            // Clear any previous styling from all cells
                            const allCells = document.querySelectorAll('.fc-daygrid-day');
                            allCells.forEach(cell => {
                                cell.style.backgroundColor = '';
                                cell.style.color = '';
                                cell.style.cursor = '';
                                cell.title = '';
                                cell.classList.remove('not-accepting-orders');
                                cell.style.border = '';
                                
                                // Remove any overlay elements
                                const overlay = cell.querySelector('.not-accepting-overlay');
                                if (overlay) {
                                    overlay.remove();
                                }
                            });
                            
                            // Process each date limit
                            data.dates.forEach(date => {
                                // Check if the date is not accepting orders (either limit=0 or status='not_accepting')
                                const isNotAccepting = parseInt(date.limit) === 0 || date.status === 'not_accepting';
                                console.log(`Date ${date.date}: limit=${date.limit}, status=${date.status}, isNotAccepting=${isNotAccepting}`);
                                
                                dateLimits[date.date] = {
                                    limit: parseInt(date.limit),
                                    is_full: date.is_full,
                                    active_orders: date.active_orders,
                                    status: isNotAccepting ? 'not_accepting' : (date.status || 'accepting')
                                };
                                
                                // Find the cell for this date and update it
                                const cell = document.querySelector(`[data-date="${date.date}"]`);
                                if (cell) {
                                    updateCalendarCell({
                                        date: new Date(date.date),
                                        el: cell
                                    });
                                }
                            });
                            console.log('Updated dateLimits:', dateLimits);
                            
                            // Add debug timeout to check cell status after rendering
                            setTimeout(() => {
                                debugNotAcceptingDateCells();
                            }, 500);
                        } else {
                            console.error('Server returned error:', data.error);
                        }
                    } catch (e) {
                        console.error('Error parsing date limits:', e);
                        console.log('Raw text:', text);
                    }
                })
                .catch(error => {
                    console.error('Error fetching date limits:', error);
                });
        }
        
        function showOrderDetails(orderId) {
            fetch('get-order-details.php?id=' + orderId)
                .then(response => response.json())
                .then(order => {
                    const modal = document.getElementById('orderModal');
                    const orderInfo = document.getElementById('orderInfo');
                    const completeOrderBtn = document.getElementById('completeOrderBtn');
                    
                    // Format display date based on order type
                    const displayDate = order.order_type === 'Pick-up' ? 
                        `<p><strong>Pickup Date:</strong> ${order.pickup_date || 'N/A'}</p>` : 
                        `<p><strong>Delivery Date:</strong> ${order.delivery_date || 'N/A'}</p>`;
                    
                    // Build order items HTML if available
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
                        
                        // Clear any existing items first
                        let processedItems = new Set();
                        order.items.forEach(item => {
                            // Check if this item has already been processed
                            if (!processedItems.has(item.product_name)) {
                                const subtotal = (item.price * item.quantity).toFixed(2);
                                itemsHtml += `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity}</td>
                                        <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                        <td>₱${subtotal}</td>
                                    </tr>
                                `;
                                processedItems.add(item.product_name);
                            }
                        });
                        
                        itemsHtml += `
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="total-label">Total Amount:</td>
                                        <td class="total-value">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;
                    }
                    
                    // Add status badge
                    const statusBadge = `
                        <div class="status-badge ${order.status.toLowerCase()}">
                            ${order.status}
                        </div>
                    `;
                    
                    orderInfo.innerHTML = `
                        <div class="order-details-grid">
                            <div class="order-details-section">
                                <h3>Order Information ${statusBadge}</h3>
                                <p><strong>Order #:</strong> ${order.order_id}</p>
                                <p><strong>Order Date:</strong> ${order.order_date}</p>
                                <p><strong>Delivery Mode:</strong> ${order.order_type}</p>
                                ${displayDate}
                                <p><strong>Time:</strong> ${order.pickup_time}</p>
                                <p><strong>Payment Method:</strong> ${order.payment_method || 'N/A'}</p>
                            </div>
                            
                            <div class="order-details-section">
                                <h3>Customer Information</h3>
                                <p><strong>Name:</strong> ${order.customer_name}</p>
                                <p><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                                <p><strong>Contact:</strong> ${order.customer_contact || 'N/A'}</p>
                                <p><strong>Address:</strong> ${order.customer_address || 'N/A'}</p>
                                ${order.notes ? `<p><strong>Notes:</strong> ${order.notes}</p>` : ''}
                            </div>
                        </div>
                        
                        ${itemsHtml}
                    `;
                    
                    // Show/hide complete order button based on status
                    if (order.status === 'Pending') {
                        completeOrderBtn.style.display = 'block';
                        completeOrderBtn.onclick = () => showCompletionConfirmation(order.order_id);
                    } else {
                        completeOrderBtn.style.display = 'none';
                    }
                    
                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    alert('Error loading order details. Please try again.');
                });
        }

        function showCompletionConfirmation(orderId) {
            const confirmationModal = document.getElementById('confirmationModal');
            const confirmBtn = document.getElementById('confirmComplete');
            const cancelBtn = document.getElementById('cancelComplete');
            
            // Store the order ID for later use
            confirmBtn.dataset.orderId = orderId;
            
            // Show the confirmation modal
            confirmationModal.style.display = 'block';
            
            // Handle confirmation
            confirmBtn.onclick = function() {
                completeOrder(this.dataset.orderId);
                confirmationModal.style.display = 'none';
                // Also close the order details modal
                document.getElementById('orderModal').style.display = 'none';
            };
            
            // Handle cancellation
            cancelBtn.onclick = function() {
                confirmationModal.style.display = 'none';
            };
        }

        function completeOrder(orderId) {
            fetch('get-done-orders.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the calendar event color
                    const event = calendar.getEventById(orderId);
                    if (event) {
                        event.setProp('backgroundColor', '#4CAF50');
                        event.setProp('borderColor', '#388E3C');
                        event.setProp('classNames', ['completed']);
                    }
                    
                    // Refresh the calendar
                    calendar.refetchEvents();
                    
                    // Show success message
                    alert(data.message || 'Order marked as completed successfully!');
                    
                    // Close the order details modal
                    document.getElementById('orderModal').style.display = 'none';
                } else {
                    alert('Error updating order: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(error => {
                console.error('Error completing order:', error);
                alert('Error completing order. Please try again.');
            });
        }

        function showDateLimit(date) {
            // Print the raw input date for debugging
            console.log('showDateLimit input date:', date);
            
            // Hide the complete order button if present
            var completeOrderBtn = document.getElementById('completeOrderBtn');
            if (completeOrderBtn) {
                completeOrderBtn.style.display = 'none';
            }
            
            // Fix date handling - ensuring we use the exact date from the calendar
            let formattedDate;
            let displayDate;
            
            if (typeof date === 'string') {
                // Use the exact date string from the calendar
                formattedDate = date;
                
                // Convert for display only, not for data operations
                const tempDate = new Date(formattedDate + 'T12:00:00');
                displayDate = tempDate.toLocaleDateString();
                
                console.log('Using string date:', formattedDate, 'Display format:', displayDate);
            } else {
                // If somehow it's still a Date object, extract the formatted date string
                // but ensure we're getting the right day regardless of timezone
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                formattedDate = `${year}-${month}-${day}`;
                displayDate = date.toLocaleDateString();
                
                console.log('Converted from Date object:', formattedDate, 'Display format:', displayDate);
            }

            console.log('Final date for API calls:', formattedDate);

            const modal = document.getElementById('orderModal');
            const orderInfo = document.getElementById('orderInfo');
            orderInfo.innerHTML = `
                <h3 style= "text-align: center">Set Order Limit for ${displayDate}</h3>
                <p style="font-size: 0.8em; color: #666; margin-bottom: 15px; text-align: center">Date: ${formattedDate}</p>
                <div id="dateLimitContainer" class="date-limit-controls">
                    <div class="limit-input-group">
                        <input type="number" id="dateLimit" min="0" class="limit-input">
                        <button onclick="updateDateLimit('${formattedDate}')" class="update-btn">Update Limit</button>
                    </div>
                    <div class="not-accepting-group">
                        <button onclick="setNotAcceptingOrders('${formattedDate}')" class="not-accepting-btn">Not Accepting Orders</button>
                    </div>
                </div>
            `;
            modal.style.display = 'block';

            // Fetch current limit for the date
            fetch('get-date-limits.php?date=' + formattedDate)
                .then(response => response.text())
                .then(text => {
                    // Clean the response by removing any HTML comments
                    const jsonStr = text.replace(/<!--[\s\S]*?-->/g, '').trim();
                    try {
                        const data = JSON.parse(jsonStr);
                        console.log('Date limit API response for', formattedDate, ':', data);
                        if (data.success) {
                            const limit = data.dates && data.dates[0] ? data.dates[0].limit : data.default_limit;
                            document.getElementById('dateLimit').value = limit;
                            console.log('Set limit input to:', limit);
                        } else {
                            throw new Error('Invalid response format');
                        }
                    } catch (e) {
                        console.error('Error parsing JSON:', e, 'Response:', jsonStr);
                        document.getElementById('dateLimitContainer').innerHTML = 'Error loading limit settings. Please try refreshing the page.';
                    }
                })
                .catch(error => {
                    console.error('Error fetching default limit:', error);
                    document.getElementById('dateLimitContainer').innerHTML = 'Error loading limit settings. Please try refreshing the page.';
                });
        }

        function setNotAcceptingOrders(date) {
            if (confirm('Are you sure you want to set this date to not accept orders?')) {
                // Use the exact date string passed from the calendar
                console.log('setNotAcceptingOrders input date:', date);
                let formattedDate = date;
                
                // Ensure we use the exact calendar date
                // If there's a T in the string, extract just the date part
                if (formattedDate.includes('T')) {
                    formattedDate = formattedDate.split('T')[0];
                }
                
                console.log('Setting date to not accept orders:', formattedDate);
                
                fetch('update-limit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: 'date',
                        date: formattedDate,
                        limit: 0,
                        status: 'not_accepting'  // Explicitly set the status
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw update response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed update response for date', formattedDate, ':', data);
                        
                        if (data.success) {
                            // Update only the specific date in the dateLimits object
                            dateLimits[formattedDate] = {
                                limit: 0,
                                is_full: true,
                                active_orders: 0,
                                remaining_slots: 0,
                                status: 'not_accepting'
                            };
                            
                            // Update cell directly for immediate feedback
                            const cell = document.querySelector(`[data-date="${formattedDate}"]`);
                            console.log('Updating cell for date', formattedDate, 'cell found:', !!cell);
                            if (cell) {
                                // Clear previous styling
                                cell.style.backgroundColor = '';
                                cell.style.color = '';
                                cell.style.cursor = '';
                                cell.title = '';
                                cell.classList.remove('not-accepting-orders');
                                
                                // Remove any existing overlay
                                const existingOverlay = cell.querySelector('.not-accepting-overlay');
                                if (existingOverlay) {
                                    existingOverlay.remove();
                                }
                                
                                // Apply not accepting orders styling
                                cell.style.backgroundColor = '#ffebee';
                                cell.style.color = '#d32f2f';
                                cell.style.cursor = 'not-allowed';
                                cell.style.border = '2px solid #d32f2f';
                                cell.title = 'Not Accepting Orders';
                                cell.classList.add('not-accepting-orders');
                                
                                // Add the X mark overlay
                                const overlay = document.createElement('div');
                                overlay.className = 'not-accepting-overlay';
                                overlay.innerHTML = '✕';
                                cell.appendChild(overlay);
                                console.log('Applied not-accepting styling to cell');
                            }
                            
                            // Close any modal
                            document.getElementById('orderModal').style.display = 'none';
                            
                            // Force refresh the entire calendar to reflect changes correctly
                            if (calendar) {
                                // Force reload date limits for current view
                                const viewStart = calendar.view.activeStart;
                                const viewEnd = calendar.view.activeEnd;
                                console.log('Refreshing date limits for current view');
                                fetchDateLimits(viewStart, viewEnd);
                                
                                // Also refetch events since they might be affected
                                calendar.refetchEvents();
                            }
                            
                            alert('Date set to not accept orders successfully!');
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    } catch (e) {
                        console.error('Error updating date limit:', e);
                        alert('Error updating date limit: ' + e.message);
                    }
                })
                .catch(error => {
                    console.error('Error updating limit:', error);
                    alert('Error updating limit. Please try again.');
                });
            }
        }

        function updateDateLimit(date) {
            const limit = document.getElementById('dateLimit').value;
            if (limit === '') {
                alert('Please enter a valid limit');
                return;
            }

            // Always use the exact date string passed from the calendar
            console.log('updateDateLimit input date:', date);
            let formattedDate = date;
            
            // Ensure we use the exact calendar date
            // If there's a T in the string, extract just the date part
            if (formattedDate.includes('T')) {
                formattedDate = formattedDate.split('T')[0];
            }
            
            console.log('Using exact date for update:', formattedDate);
            const limitValue = parseInt(limit);
            
            fetch('update-limit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'date',
                    date: formattedDate,
                    limit: limitValue
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Update limit response for date', formattedDate, ':', data);
                if (data.success) {
                    // Update the dateLimits object with new limit
                    if (limitValue === 0) {
                        // If limit is 0, mark as not accepting orders
                        dateLimits[formattedDate] = {
                            limit: 0,
                            is_full: false,
                            active_orders: 0,
                            status: 'not_accepting'
                        };
                        
                        // Update cell directly for immediate feedback
                        const cell = document.querySelector(`[data-date="${formattedDate}"]`);
                        console.log('Updating cell for date', formattedDate, 'cell found:', !!cell);
                        if (cell) {
                            // Clear previous styling
                            cell.style.backgroundColor = '';
                            cell.style.color = '';
                            cell.style.cursor = '';
                            cell.title = '';
                            cell.classList.remove('not-accepting-orders');
                            
                            // Remove any existing overlay
                            const existingOverlay = cell.querySelector('.not-accepting-overlay');
                            if (existingOverlay) {
                                existingOverlay.remove();
                            }
                            
                            // Apply not accepting orders styling
                            cell.style.backgroundColor = '#ffebee';
                            cell.style.color = '#d32f2f';
                            cell.style.cursor = 'not-allowed';
                            cell.style.border = '2px solid #d32f2f';
                            cell.title = 'Not Accepting Orders';
                            cell.classList.add('not-accepting-orders');
                            
                            // Add the X mark overlay
                            const overlay = document.createElement('div');
                            overlay.className = 'not-accepting-overlay';
                            overlay.innerHTML = '✕';
                            cell.appendChild(overlay);
                            console.log('Applied not-accepting styling to cell');
                        }
                    } else {
                        // Regular limit - remove not accepting orders styling
                        dateLimits[formattedDate] = {
                            limit: limitValue,
                            is_full: false,
                            active_orders: 0,
                            status: 'accepting'
                        };
                        
                        // Update cell directly for immediate feedback
                        const cell = document.querySelector(`[data-date="${formattedDate}"]`);
                        console.log('Updating cell for date', formattedDate, 'cell found:', !!cell);
                        if (cell) {
                            // Clear previous styling
                            cell.style.backgroundColor = '';
                            cell.style.color = '';
                            cell.style.cursor = '';
                            cell.title = '';
                            cell.classList.remove('not-accepting-orders');
                            cell.style.border = '';
                            
                            // Remove any existing overlay
                            const existingOverlay = cell.querySelector('.not-accepting-overlay');
                            if (existingOverlay) {
                                existingOverlay.remove();
                            }
                            console.log('Removed not-accepting styling from cell');
                        }
                    }
                    
                    alert('Date limit updated successfully!');
                    document.getElementById('orderModal').style.display = 'none';
                    
                    // Ensure calendar refreshes with the latest data
                    if (calendar) {
                        // Force reload date limits for current view
                        const viewStart = calendar.view.activeStart;
                        const viewEnd = calendar.view.activeEnd;
                        console.log('Refreshing date limits for current view');
                        fetchDateLimits(viewStart, viewEnd);
                        
                        // Refresh calendar events
                        calendar.refetchEvents();
                    }
                } else {
                    alert('Error updating limit: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating limit:', error);
                alert('Error updating limit. Please try again.');
            });
        }

        function updateDailyLimit() {
            const limit = document.getElementById('dailyLimit').value;
            if (!limit || parseInt(limit) <= 0) {
                alert('Please enter a valid limit greater than 0');
                return;
            }

            fetch('update-limit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'daily',
                    limit: parseInt(limit)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Daily limit updated successfully!');
                    // Refresh the calendar to show updated limits
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                    // Refresh the default limit display
                    refreshDefaultLimit();
                } else {
                    alert('Error updating limit: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating limit:', error);
                alert('Error updating limit. Please try again.');
            });
        }

        function toggleCompletedOrders() {
            showCompletedOrders = !showCompletedOrders;
            const btn = document.querySelector('.calendar-btn.completed');
            if (showCompletedOrders) {
                btn.innerHTML = '<span>✕</span> Hide Completed Orders';
                btn.style.backgroundColor = '#f44336';
            } else {
                btn.innerHTML = '<span>✓</span> Show Completed Orders';
                btn.style.backgroundColor = '#4CAF50';
            }
            calendar.refetchEvents();
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: 'get-orders.php',
                    extraParams: function() {
                        return {
                            showCompleted: showCompletedOrders
                        };
                    },
                    failure: function(error) {
                        console.error('Error fetching calendar events:', error);
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger';
                        errorDiv.style.margin = '10px';
                        errorDiv.style.padding = '10px';
                        errorDiv.style.borderRadius = '4px';
                        errorDiv.style.backgroundColor = '#f8d7da';
                        errorDiv.style.color = '#721c24';
                        errorDiv.style.border = '1px solid #f5c6cb';
                        errorDiv.innerHTML = 'Failed to load calendar events. Please try refreshing the page.';
                        calendarEl.appendChild(errorDiv);
                    }
                },
                datesSet: function(arg) {
                    fetchDateLimits(arg.start, arg.end);
                },
                dayCellDidMount: function(arg) {
                    updateCalendarCell(arg);
                },
                eventDidMount: function(info) {
                    $(info.el).tooltip({
                        title: `Order #${info.event.id} - ${info.event.extendedProps.customer}
                                ${info.event.extendedProps.type} - ${info.event.extendedProps.status}
                                Time: ${info.event.extendedProps.time}`,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                },
                eventContent: function(arg) {
                    return {
                        html: `<div class="fc-event-main-frame">
                                <div class="fc-event-title-container">
                                    <div class="fc-event-title fc-sticky">
                                        #${arg.event.id} - ${arg.event.extendedProps.customer}
                                        <div class="order-time">${arg.event.extendedProps.time}</div>
                                    </div>
                                </div>
                            </div>`
                    };
                },
                eventClick: function(info) {
                    const orderId = info.event.id;
                    showOrderDetails(orderId);
                },
                dateClick: function(info) {
                    const clickedDateStr = info.dateStr;
                    const clickedDate = new Date(clickedDateStr + 'T12:00:00');
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (clickedDate < today) {
                        return;
                    }
                    
                    showDateLimit(clickedDateStr);
                },
                eventClassNames: function(arg) {
                    const classes = ['order-status-' + (arg.event.extendedProps.status || 'pending').toLowerCase()];
                    if (arg.event.extendedProps.status === 'Picked-up' || arg.event.extendedProps.status === 'Delivered') {
                        classes.push('completed');
                    }
                    return classes;
                }
            });

            calendar.render();
            refreshDefaultLimit();

            // Modal close functionality
            const orderModal = document.getElementById('orderModal');
            const confirmationModal = document.getElementById('confirmationModal');

            // Close button handlers
            document.querySelectorAll('.close').forEach(closeBtn => {
                closeBtn.onclick = function() {
                    orderModal.style.display = 'none';
                    confirmationModal.style.display = 'none';
                };
            });

            // Window click handler for overlay close
            window.onclick = function(event) {
                if (event.target === orderModal || event.target === confirmationModal) {
                    orderModal.style.display = 'none';
                    confirmationModal.style.display = 'none';
                }
            };

            // Cancel button handler
            const cancelBtn = document.getElementById('cancelComplete');
            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    confirmationModal.style.display = 'none';
                };
            }

            // Confirm button handler
            const confirmBtn = document.getElementById('confirmComplete');
            if (confirmBtn) {
                confirmBtn.onclick = function() {
                    const orderId = this.dataset.orderId;
                    if (orderId) {
                        completeOrder(orderId);
                        confirmationModal.style.display = 'none';
                        orderModal.style.display = 'none';
                    }
                };
            }
        });

        // Add this function after the DOMContentLoaded event
        function debugNotAcceptingDateCells() {
            console.log('Checking all date cells...');
            const cells = document.querySelectorAll('.fc-daygrid-day');
            let notAcceptingCount = 0;
            
            cells.forEach(cell => {
                const dateStr = cell.getAttribute('data-date');
                const hasClass = cell.classList.contains('not-accepting-orders');
                const hasOverlay = cell.querySelector('.not-accepting-overlay') !== null;
                const bgColor = cell.style.backgroundColor;
                
                if (hasClass || hasOverlay || bgColor === 'rgb(255, 235, 238)') {
                    console.log(`Date ${dateStr} marked as not accepting:`, {
                        hasClass: hasClass,
                        hasOverlay: hasOverlay,
                        bgColor: bgColor,
                        limitInfo: dateLimits[dateStr]
                    });
                    notAcceptingCount++;
                }
            });
            
            console.log(`Found ${notAcceptingCount} not-accepting date cells`);
            return true;
        }

        // Add this function call at the end of fetchDateLimits
        setTimeout(() => {
            debugNotAcceptingDateCells();
        }, 500);

        // Function to refresh the default limit display
        function refreshDefaultLimit() {
            fetch('get-date-limits.php?get_default=true')
                .then(response => response.text())
                .then(text => {
                    // Clean the response by removing any HTML comments
                    const jsonStr = text.replace(/<!--[\s\S]*?-->/g, '').trim();
                    try {
                        const data = JSON.parse(jsonStr);
                        if (data.success && data.default_limit !== undefined) {
                            document.getElementById('dailyLimit').value = data.default_limit;
                        } else {
                            console.error('Invalid response format:', data);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON:', e, 'Response:', jsonStr);
                    }
                })
                .catch(error => console.error('Error fetching default limit:', error));
        }

        $(document).ready(function() {
            // Load current knowledge base
            fetch('get-knowledge.php')
                .then(response => response.json())
                .then(res => {
                if (res.success) {
                        $('#knowledge-content').val(res.content);
                        $('#knowledge-form').data('id', res.id);
                        
                        // Add a preview div to show how links will appear in chat
                        const previewDiv = $('<div id="knowledge-preview" style="margin-top: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><h3 style="margin-top: 0;">Preview (with clickable links):</h3><div></div></div>');
                        $('#knowledge-form').after(previewDiv);
                        
                        // Update preview when content changes
                        $('#knowledge-content').on('input', function() {
                            const content = $(this).val();
                            const linkified = linkifyText(content);
                            $('#knowledge-preview div').html(linkified).addClass('prev-p');
                        });
                        
                        // Trigger initial preview
                        $('#knowledge-content').trigger('input');
                    } else {
                        console.error('Error loading knowledge base:', res.error);
                        alert('Error loading knowledge base: ' + res.error);
                    }
                })
                .catch(error => {
                    console.error('Failed to load knowledge base:', error);
                    alert('Failed to load knowledge base. Please try again.');
            });

            // Save knowledge base
            $('#knowledge-form').on('submit', function(e) {
                e.preventDefault();
                const content = $('#knowledge-content').val();
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Saving...');
                
                fetch('save-knowledge.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'content=' + encodeURIComponent(content)
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        alert('Knowledge base updated successfully!');
                    } else {
                        alert('Failed to update: ' + (res.error || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Failed to save knowledge base:', error);
                    alert('Failed to save knowledge base. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.prop('disabled', false).text(originalText);
                });
            });
        });

        const pickupEvents = document.querySelectorAll('.fc-daygrid-event.pick-up.active');

// Remove tooltip functionality
pickupEvents.forEach(event => {
  // Remove the attributes that trigger tooltips
  event.removeAttribute('data-bs-original-title');
  event.removeAttribute('title');
  event.removeAttribute('aria-describedby');
  
  // If using Bootstrap's tooltip initialization
  // Destroy any tooltip instances that might be attached
  if (window.bootstrap && bootstrap.Tooltip) {
    const tooltipInstance = bootstrap.Tooltip.getInstance(event);
    if (tooltipInstance) {
      tooltipInstance.dispose();
    }
  }
});
    </script>
</body>
</html>