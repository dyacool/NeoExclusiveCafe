<?php
session_start();
require_once "../../php/includes/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT id, firstname, lastname, username, email, created_at, profile_image FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Ensure user data is retrieved
if (!$user) {
    die("Error: Unable to fetch user data");
}

// Ensure created_at has a valid value
if (!isset($user['created_at']) || empty($user['created_at'])) {
    $user['created_at'] = date('Y-m-d H:i:s');
}

// Get user's post statistics
$stats_query = "SELECT 
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_posts
                FROM user_blog_post 
                WHERE user_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get count of user orders
$orders_query = "SELECT COUNT(*) as order_count FROM orders WHERE customer_email = ?";
$orders_stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($orders_stmt, "s", $user['email']);
mysqli_stmt_execute($orders_stmt);
$orders_result = mysqli_stmt_get_result($orders_stmt);
$orders_count = mysqli_fetch_assoc($orders_result)['order_count'];

// Get count of saved posts
$saved_query = "SELECT COUNT(*) as saved_count FROM saved_posts WHERE user_id = ?";
$saved_stmt = mysqli_prepare($conn, $saved_query);
mysqli_stmt_bind_param($saved_stmt, "i", $user_id);
mysqli_stmt_execute($saved_stmt);
$saved_result = mysqli_stmt_get_result($saved_stmt);
$saved_count = mysqli_fetch_assoc($saved_result)['saved_count'];

// Determine profile image path
$profile_image_path = '../../assets/images/profile.svg';
if (isset($user['profile_image']) && !empty(trim($user['profile_image']))) {
    $image_path = ltrim($user['profile_image'], '/');
    $server_path = $_SERVER['DOCUMENT_ROOT'] . '/../../' . $image_path;
    if (file_exists($server_path) && is_readable($server_path)) {
        $profile_image_path = '../../' . $image_path;
    }
}

$user_email = $user['email'];

// Fetch user orders by customer_email
$sql = "SELECT order_id, status, order_date, total_items, total_amount, delivery_method FROM orders WHERE customer_email = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Prepare an array to store all order details including items
$all_orders = [];
$order_items = [];

// Fetch all order items for all orders at once
if (mysqli_num_rows($result) > 0) {
    while ($order = mysqli_fetch_assoc($result)) {
        // Store the order data
        $all_orders[$order['order_id']] = $order;
        
        // Fetch items for this order
        $items_sql = "SELECT product_name, quantity, price FROM order_items WHERE order_id = ?";
        $items_stmt = mysqli_prepare($conn, $items_sql);
        
        if ($items_stmt) {
            mysqli_stmt_bind_param($items_stmt, "i", $order['order_id']);
            mysqli_stmt_execute($items_stmt);
            $items_result = mysqli_stmt_get_result($items_stmt);
            
            $order_items[$order['order_id']] = [];
            while ($item = mysqli_fetch_assoc($items_result)) {
                $order_items[$order['order_id']][] = $item;
            }
            
            mysqli_stmt_close($items_stmt);
        } else {
            // Log error if statement preparation fails
            error_log("Failed to prepare statement for order items: " . mysqli_error($conn));
        }
    }
    
    // Reset the result pointer for the main display loop
    mysqli_data_seek($result, 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/users/profile.css">
    
</head>
<body>
<?php include "../../php/includes/customer-navigation.php"; ?>

    <!-- Profile content with namespaced classes -->
    <div class="neo-profile-container">
        <div class="neo-profile-header">
            <div class="neo-profile-avatar">
                <img src="<?= htmlspecialchars($user['profile_image'] ?: '../../assets/images/default-profile.png') ?>" alt="Profile Image" width="50" />
            </div>
            <div class="neo-profile-info">
                <h1><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h1>
                <div class="neo-profile-meta">
                    <?php 
                    $username_query = "SELECT username FROM users WHERE id = ?";
                    $username_stmt = mysqli_prepare($conn, $username_query);
                    mysqli_stmt_bind_param($username_stmt, "i", $user_id);
                    mysqli_stmt_execute($username_stmt);
                    $username_result = mysqli_stmt_get_result($username_stmt);
                    $username_data = mysqli_fetch_assoc($username_result);
                    echo '<p>@' . htmlspecialchars($username_data['username']) . '</p>';
                    ?>
                    <p>Member since <?php 
                        echo isset($user['created_at']) && !empty($user['created_at']) 
                            ? date('F Y', strtotime($user['created_at'])) 
                            : date('F Y'); 
                    ?></p>
                </div>
                <div class="neo-profile-actions">
                    <a href="account-settings.php" class="neo-profile-btn primary-btn">
                        Account Settings
                    </a>
                    <a href="user-blog-post.php" class="neo-profile-btn secondary-btn">
                        My Posts
                    </a>
                    <a href="../../php/auth/logout.php" class="neo-profile-btn danger-btn">
                        Logout
                    </a>
                </div>
            </div>
        </div>

        <div class="neo-profile-stats">
            <div class="neo-stat-card">
                <div class="neo-stat-number"><?php echo $stats['total_posts']; ?></div>
                <div class="neo-stat-label">Total Posts</div>
            </div>
            <div class="neo-stat-card">
                <div class="neo-stat-number"><?php echo $stats['published_posts']; ?></div>
                <div class="neo-stat-label">Published Posts</div>
            </div>
            <div class="neo-stat-card">
                <div class="neo-stat-number"><?php echo $orders_count; ?></div>
                <div class="neo-stat-label">Total Orders</div>
            </div>

        </div>

        <div class="neo-profile-orders">
            <h2>My Orders</h2>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>Order ID</th>
                        <th>Total Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></td>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['total_items']); ?></td>
                            <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                            <!-- padagdag na din kung kelan pickup time -->
                            <td><span class="status-<?php echo htmlspecialchars(strtolower($order['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                            <td>
                                <button class="btn-view" onclick="openOrderModal(<?php echo $order['order_id']; ?>)">View Details</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No orders found for your account.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Order Details Modal -->
<div id="orderDetailsModal" class="neo-modal">
    <div class="neo-modal-content">
        <span class="neo-modal-close">&times;</span>
        <div id="orderDetailsContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Store each order's details in separate data attributes for reliability -->
<?php foreach ($all_orders as $order_id => $order): ?>
    <div id="order-data-<?php echo $order_id; ?>" style="display: none;" 
         data-order='<?php echo htmlspecialchars(json_encode($order)); ?>'
         data-items='<?php echo htmlspecialchars(json_encode($order_items[$order_id] ?? [])); ?>'>
    </div>
<?php endforeach; ?>
    </div>

    <script>
    // Mobile menu toggle
    document.querySelector('.menu-toggle')?.addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('show');
    });

    // Modal functionality
    const modal = document.getElementById('orderDetailsModal');
    const closeBtn = document.querySelector('.neo-modal-close');
    
    function openOrderModal(orderId) {
        modal.style.display = "block";
        
        try {
            // Show loading state
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="neo-modal-loading">
                    <div class="neo-spinner"></div>
                    <p>Loading order details...</p>
                </div>
            `;
            
            // Get the specific order data element
            const orderDataElement = document.getElementById(`order-data-${orderId}`);
            
            if (!orderDataElement) {
                throw new Error(`Order data element not found for order ID: ${orderId}`);
            }
            
            // Parse the JSON data
            const orderData = orderDataElement.getAttribute('data-order');
            const itemsData = orderDataElement.getAttribute('data-items');
            
            console.log("Order data:", orderData);
            console.log("Items data:", itemsData);
            
            if (!orderData || !itemsData) {
                throw new Error("Order data attributes are missing or empty");
            }
            
            const order = JSON.parse(orderData);
            const items = JSON.parse(itemsData);
            
            console.log("Parsed order:", order);
            console.log("Parsed items:", items);
            
            // Display the order details
            displayOrderDetails(order, items);
        } catch (error) {
            console.error("Error displaying order details:", error);
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="neo-modal-error">
                    <p>Error: Could not load order details. Please try again.</p>
                    <p>Details: ${error.message}</p>
                </div>
            `;
        }
    }
    
    function displayOrderDetails(order, items) {
        try {
            let itemsHtml = '';
            let subtotal = 0;
            
            if (Array.isArray(items) && items.length > 0) {
                items.forEach(item => {
                    const price = parseFloat(item.price || 0);
                    const quantity = parseInt(item.quantity || 1);
                    const itemTotal = price * quantity;
                    subtotal += itemTotal;
                    
                    itemsHtml += `
                        <tr>
                            <td>${item.product_name || 'Unknown product'}</td>
                            <td>${quantity}</td>
                            <td>${price.toFixed(2)}</td>
                            <td>${itemTotal.toFixed(2)}</td>
                        </tr>
                    `;
                });
            } else {
                itemsHtml = '<tr><td colspan="4">No items found for this order</td></tr>';
            }
            
            // Format the date safely
            let formattedDate = 'Unknown date';
            try {
                if (order.order_date) {
                    const orderDate = new Date(order.order_date);
                    formattedDate = orderDate.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            } catch (e) {
                console.error("Error formatting date:", e);
            }
            
            // Get status with appropriate styling
            let statusClass = '';
            switch(order.status?.toLowerCase()) {
                case 'completed':
                    statusClass = 'status-completed';
                    break;
                case 'processing':
                    statusClass = 'status-processing';
                    break;
                case 'pending':
                    statusClass = 'status-pending';
                    break;
                case 'cancelled':
                    statusClass = 'status-cancelled';
                    break;
                default:
                    statusClass = '';
            }
            
            const content = `
                <h2>Order ID - #${order.order_id || 'Unknown'}</h2>
                <div class="neo-order-info">
                    <p><strong>Status:</strong> <span class="${statusClass}">${order.status || 'Unknown'}</span></p>
                    <p><strong>Order Date:</strong> ${formattedDate}</p>
                    <p><strong>Delivery Method:</strong> ${order.delivery_method || 'N/A'}</p>
                    <p><strong>Total Items:</strong> ${order.total_items || items.length || 0}</p>
                    <p><strong>Total Amount:</strong> ${parseFloat(order.total_amount || 0).toFixed(2)}</p>
                </div>
                
                <h3>Order Items</h3>
                <table class="neo-modal-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 600;">Subtotal:</td>
                            <td style="font-weight: 600;">₱${subtotal.toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            `;
            
            document.getElementById('orderDetailsContent').innerHTML = content;
        } catch (error) {
            console.error("Error in displayOrderDetails:", error);
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="neo-modal-error">
                    <p>Error: Could not display order details.</p>
                    <p>Details: ${error.message}</p>
                </div>
            `;
        }
    }
    
    // Close modal when clicking the X
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
</body>
</html>
