<?php
session_start();
require_once "../../../backend/pages/admin-includes/database.php";

if (!isset($_SESSION['user_id'])) {
	header("Location: ../../login/user/login-signup.php");
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
	error_log("Profile: user record not found for user_id=" . $user_id);
	if (isset($user_stmt) && $user_stmt instanceof mysqli_stmt) {
		mysqli_stmt_close($user_stmt);
	}
	session_unset();
	session_destroy();
	header("Location: ../../login/user/login-signup.php?relogin=1");
	exit();
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

// Get count of bulk orders
$bulk_orders_count_query = "SELECT COUNT(*) as bulk_count FROM bulk_orders WHERE user_id = ?";
$bulk_orders_count_stmt = mysqli_prepare($conn, $bulk_orders_count_query);

if ($bulk_orders_count_stmt === false) {
    // Handle case where bulk_orders table doesn't exist yet
    $bulk_orders_count = 0;
} else {
    mysqli_stmt_bind_param($bulk_orders_count_stmt, "i", $user_id);
    mysqli_stmt_execute($bulk_orders_count_stmt);
    $bulk_orders_count_result = mysqli_stmt_get_result($bulk_orders_count_stmt);
    $bulk_orders_count = mysqli_fetch_assoc($bulk_orders_count_result)['bulk_count'];
    mysqli_stmt_close($bulk_orders_count_stmt);
}

// Determine profile image url (root-relative stored in DB like /assets/public/profile-images/xxxx.jpg)
$profile_default_image_path = '/assets/images/profile.svg';
$profile_image_url = $profile_default_image_path;
if (isset($user['profile_image']) && !empty(trim($user['profile_image']))) {
    $db_path = trim($user['profile_image']);
    if ($db_path[0] !== '/') {
        $db_path = '/' . $db_path;
    }
    // Use root-relative url stored in DB (e.g., /assets/public/profile-images/abc.jpg)
    $profile_image_url = $db_path;
}

$user_email = $user['email'];

// Pagination settings
$orders_per_page = 3;
$bulk_orders_per_page = 4;
$orders_page = isset($_GET['orders_page']) ? max(1, intval($_GET['orders_page'])) : 1;
$bulk_orders_page = isset($_GET['bulk_orders_page']) ? max(1, intval($_GET['bulk_orders_page'])) : 1;
$orders_offset = ($orders_page - 1) * $orders_per_page;
$bulk_orders_offset = ($bulk_orders_page - 1) * $bulk_orders_per_page;

// Check if order_refunds table exists
$refunds_table_check = "SHOW TABLES LIKE 'order_refunds'";
$refunds_table_result = $conn->query($refunds_table_check);
$refunds_table_exists = $refunds_table_result && $refunds_table_result->num_rows > 0;

// Get total count of orders first
if ($refunds_table_exists) {
    $count_sql = "SELECT COUNT(*) as total FROM orders o WHERE o.customer_email = ?";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "s", $user_email);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM orders WHERE customer_email = ?";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($count_stmt, "s", $user_email);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_orders = mysqli_fetch_assoc($count_result)['total'];
$total_orders_pages = ceil($total_orders / $orders_per_page);
mysqli_stmt_close($count_stmt);

// Fetch user orders by customer_email with refund information (with pagination)
if ($refunds_table_exists) {
    $sql = "SELECT o.order_id, o.status, o.order_date, o.total_items, o.total_amount, o.delivery_method,
                   r.refund_id, r.refund_status, r.refund_amount, r.created_at as refund_created_at
            FROM orders o
            LEFT JOIN order_refunds r ON o.order_id = r.order_id AND r.user_id = ?
            WHERE o.customer_email = ? 
            ORDER BY o.order_date DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isii", $user_id, $user_email, $orders_per_page, $orders_offset);
} else {
    $sql = "SELECT order_id, status, order_date, total_items, total_amount, delivery_method FROM orders WHERE customer_email = ? ORDER BY order_date DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $user_email, $orders_per_page, $orders_offset);
}
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

// Get total count of bulk orders first
$bulk_count_sql = "SELECT COUNT(*) as total FROM bulk_orders WHERE user_id = ?";
$bulk_count_stmt = mysqli_prepare($conn, $bulk_count_sql);

if ($bulk_count_stmt === false) {
    // Handle case where bulk_orders table doesn't exist yet
    $total_bulk_orders = 0;
    $total_bulk_orders_pages = 0;
} else {
    mysqli_stmt_bind_param($bulk_count_stmt, "i", $user_id);
    mysqli_stmt_execute($bulk_count_stmt);
    $bulk_count_result = mysqli_stmt_get_result($bulk_count_stmt);
    $total_bulk_orders = mysqli_fetch_assoc($bulk_count_result)['total'];
    $total_bulk_orders_pages = ceil($total_bulk_orders / $bulk_orders_per_page);
    mysqli_stmt_close($bulk_count_stmt);
}

// Fetch user bulk orders (with pagination)
$bulk_orders_sql = "SELECT id, 
                           unique_order_id as display_order_id,
                           name, contact, email, billing_address, order_type, delivery_address, purpose, date_needed, time_needed, created_at, status, total_items, total_amount, proof_of_payment, admin_updated, note, admin_notes 
                    FROM bulk_orders 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
$bulk_orders_stmt = mysqli_prepare($conn, $bulk_orders_sql);

// Check if bulk_orders table exists and statement prepared successfully
if ($bulk_orders_stmt === false) {
    // Handle case where bulk_orders table doesn't exist yet
    $bulk_orders_count = 0;
    $all_bulk_orders = [];
    $bulk_order_items = [];
    // Create empty result for later use
    $bulk_orders_result = mysqli_query($conn, "SELECT 1 WHERE 0"); // Empty result set
} else {
    mysqli_stmt_bind_param($bulk_orders_stmt, "iii", $user_id, $bulk_orders_per_page, $bulk_orders_offset);
    mysqli_stmt_execute($bulk_orders_stmt);
    $bulk_orders_result = mysqli_stmt_get_result($bulk_orders_stmt);

    // Prepare arrays to store bulk order details and items
    $all_bulk_orders = [];
    $bulk_order_items = [];

    // Reset the result pointer for the main display loop if needed
    if (mysqli_num_rows($bulk_orders_result) > 0) {
        mysqli_data_seek($bulk_orders_result, 0);
    }
    
    mysqli_stmt_close($bulk_orders_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
    <style>
        .refund-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .refund-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .refund-status-approved {
            background: #d1e7dd;
            color: #0f5132;
        }
        .refund-status-rejected {
            background: #f8d7da;
            color: #842029;
        }
        .refund-status-completed {
            background: #d1e7dd;
            color: #0a3622;
        }
        .refund-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }
        .refund-modal.show {
            display: flex !important;
        }
        .refund-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .refund-modal-header {
            background: linear-gradient(135deg, #0f5132, #2d5a27);
            color: white;
            padding: 20px 24px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .refund-modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .refund-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .refund-modal-body {
            padding: 24px;
        }
    </style>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>

</head>

<body>
<?php include "../../user-includes/bread-crumb/bread-crumb.php"; ?>


    <!-- Profile content with namespaced classes -->
    <div class="neo-profile-container">
        <div class="neo-profile-header-card">
            <div class="neo-profile-header-content">
                <div class="neo-profile-avatar">
                    <img src="<?= htmlspecialchars($profile_image_url) ?>" alt="Profile Image" />
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
                        echo '<p>
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12,4c-4.411,0-8,3.589-8,8s3.589,8,8,8c1.616,0,3.172-0.479,4.499-1.384c0.456-0.312,0.574-0.934,0.263-1.39c-0.311-0.457-0.932-0.572-1.39-0.263C14.378,17.642,13.212,18,12,18c-3.309,0-6-2.691-6-6s2.691-6,6-6s6,2.691,6,6v0.5c0,0.552-0.448,1-1,1s-1-0.448-1-1v-3c0-0.553-0.447-1-1-1c-0.441,0-0.805,0.29-0.938,0.688C13.482,8.761,12.773,8.5,12,8.5c-1.93,0-3.5,1.57-3.5,3.5s1.57,3.5,3.5,3.5c1.045,0,1.975-0.47,2.616-1.199C15.164,15.024,16.024,15.5,17,15.5c1.654,0,3-1.346,3-3V12C20,7.589,16.411,4,12,4z M12,13.5c-0.827,0-1.5-0.673-1.5-1.5s0.673-1.5,1.5-1.5s1.5,0.673,1.5,1.5S12.827,13.5,12,13.5z"/>
                            </svg>
                            ' . htmlspecialchars($username_data['username']) . '</p>';
                        ?>
                        <p>
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z"/>
                            </svg>
                            Member since <?php 
                            echo isset($user['created_at']) && !empty($user['created_at']) 
                                ? date('F Y', strtotime($user['created_at'])) 
                                : date('F Y'); 
                        ?></p>
                    </div>
                </div>
            </div>

            <div class="neo-profile-actions">
                <a href="account-settings.php" class="neo-profile-link">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
                    </svg>
                    Account Settings
                </a>
                <a href="../blog/user-blog-post.php" class="neo-profile-link">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/>
                    </svg>
                    My Posts
                </a>
                <button onclick="showLogoutConfirmation()" class="neo-profile-link logout-link">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
                    </svg>
                    Logout
                </button>
            </div>
        </div>

        <div class="neo-profile-stats">
            <div class="neo-stat-card">
                <div class="neo-stat-content">
                    <div class="neo-stat-number"><?php echo $stats['total_posts']; ?></div>
                    <div class="neo-stat-label">Total Posts</div>
                </div>
            </div>
            <div class="neo-stat-card">
                <div class="neo-stat-content">
                    <div class="neo-stat-number"><?php echo $orders_count; ?></div>
                    <div class="neo-stat-label">Total Orders</div>
                </div>
            </div>
            <div class="neo-stat-card">
                <div class="neo-stat-content">
                    <div class="neo-stat-number"><?php echo $bulk_orders_count; ?></div>
                    <div class="neo-stat-label">Bulk Orders</div>
                </div>
            </div>
        </div>

        <div class="neo-profile-orders" id="orders-section">
            <h2>My Orders</h2>
            <table class="orders-table regular-orders-table" id="orders-table">
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>Order ID</th>
                        <th>Total Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Refund Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                        <?php
                            $status_lower = strtolower($order['status']);
                            $is_delivered = ($status_lower === 'delivered' || $status_lower === 'picked-up' || $status_lower === 'picked up');
                            $has_refund = isset($order['refund_id']) && !empty($order['refund_id']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date("M j, Y", strtotime($order['order_date']))); ?></td>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['total_items']); ?> items</td>
                            <td>₱<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars($status_lower); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                            <td>
                                <?php if ($has_refund): ?>
                                    <div class="refund-status-container">
                                        <span class="refund-status-badge refund-status-<?php echo htmlspecialchars($order['refund_status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($order['refund_status'])); ?>
                                        </span>
                                        <small class="refund-date">
                                            <?php echo date("M j, Y", strtotime($order['refund_created_at'])); ?>
                                        </small>
                                    </div>
                                <?php elseif ($is_delivered): ?>
                                    <span class="refund-available">Available</span>
                                <?php else: ?>
                                    <span class="refund-na">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-column">
                                <a href="../cart/order-details.php?order_id=<?php echo $order['order_id']; ?>" class="btn-view">View Order</a>
                                <?php if ($is_delivered): ?>
                                    <?php if ($has_refund): ?>
                                        <button onclick="viewRefundDetails(<?php echo $order['order_id']; ?>)" class="btn-refund">View Refund</button>
                                    <?php else: ?>
                                        <button onclick="openRefundRequestModal(<?php echo $order['order_id']; ?>)" class="btn-refund">Request Refund</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No orders found for your account.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Orders Pagination -->
            <?php if ($total_orders_pages > 1): ?>
            <div class="pagination-container" id="orders-pagination">
                <div class="pagination">
                    <?php if ($orders_page > 1): ?>
                        <button type="button" data-page="<?php echo ($orders_page - 1); ?>" data-type="orders" class="pagination-btn pagination-link">&laquo; Previous</button>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_orders_pages; $i++): ?>
                        <?php if ($i == $orders_page): ?>
                            <span class="pagination-btn active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <button type="button" data-page="<?php echo $i; ?>" data-type="orders" class="pagination-btn pagination-link"><?php echo $i; ?></button>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($orders_page < $total_orders_pages): ?>
                        <button type="button" data-page="<?php echo ($orders_page + 1); ?>" data-type="orders" class="pagination-btn pagination-link">Next &raquo;</button>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    Showing <?php echo min($orders_offset + $orders_per_page, $total_orders); ?> of <?php echo $total_orders; ?> orders
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bulk Order History Section -->
        <div class="neo-profile-orders" id="bulk-orders-section">
            <h2>Bulk Order History</h2>
            <table class="orders-table bulk-orders-table" id="bulk-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date Submitted</th>
                        <th>Total Items</th>
                        <th>Total Amount</th>
                        <th>Order Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($bulk_orders_result) > 0): ?>
                        <?php while ($bulk_order = mysqli_fetch_assoc($bulk_orders_result)): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($bulk_order['display_order_id']); ?></td>
                            <td><?php echo htmlspecialchars(date("M j, Y", strtotime($bulk_order['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($bulk_order['total_items']); ?> items</td>
                            <td>₱<?php echo htmlspecialchars(number_format($bulk_order['total_amount'], 2)); ?></td>
                            <td><span class="status-badge status-<?php echo htmlspecialchars(strtolower($bulk_order['status'])); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $bulk_order['status']))); ?></span></td>
                            <td class="actions-column">
                                <a href="../bulk/bulk-order-details.php?id=<?php echo $bulk_order['display_order_id']; ?>" class="btn-view">View Details</a>
                                <?php if ($bulk_order['status'] == 'approved' && empty($bulk_order['proof_of_payment'])): ?>
                                    <a href="../bulk/bulk-order-details.php?id=<?php echo $bulk_order['display_order_id']; ?>#proof-upload" class="btn-upload">Attach Proof</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No bulk orders found for your account.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Bulk Orders Pagination -->
            <?php if ($total_bulk_orders_pages > 1): ?>
            <div class="pagination-container" id="bulk-orders-pagination">
                <div class="pagination">
                    <?php if ($bulk_orders_page > 1): ?>
                        <button type="button" data-page="<?php echo ($bulk_orders_page - 1); ?>" data-type="bulk_orders" class="pagination-btn pagination-link">&laquo; Previous</button>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_bulk_orders_pages; $i++): ?>
                        <?php if ($i == $bulk_orders_page): ?>
                            <span class="pagination-btn active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <button type="button" data-page="<?php echo $i; ?>" data-type="bulk_orders" class="pagination-btn pagination-link"><?php echo $i; ?></button>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($bulk_orders_page < $total_bulk_orders_pages): ?>
                        <button type="button" data-page="<?php echo ($bulk_orders_page + 1); ?>" data-type="bulk_orders" class="pagination-btn pagination-link">Next &raquo;</button>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    Showing <?php echo min($bulk_orders_offset + $bulk_orders_per_page, $total_bulk_orders); ?> of <?php echo $total_bulk_orders; ?> bulk orders
                </div>
            </div>
            <?php endif; ?>
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

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="neo-modal">
    <div class="neo-modal-content neo-confirmation-modal">
        <div class="neo-modal-header">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
            </svg>
            <h3>Confirm Logout</h3>
        </div>
        <div class="neo-modal-body">
            <p>Are you sure you want to logout from your account?</p>
            <p class="neo-modal-subtitle">You will need to login again to access your account.</p>
        </div>
        <div class="neo-modal-actions">
            <button onclick="closeLogoutModal()" class="neo-modal-btn cancel-btn">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                </svg>
                Cancel
            </button>
            <a href="../../php/auth/logout.php" class="neo-modal-btn confirm-btn">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
                </svg>
                Yes, Logout
            </a>
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
    const logoutModal = document.getElementById('logoutModal');
    const closeBtn = document.querySelector('.neo-modal-close');
    
    // Logout confirmation functions
    function showLogoutConfirmation() {
        logoutModal.style.display = "block";
        document.body.style.overflow = "hidden";
    }
    
    function closeLogoutModal() {
        logoutModal.style.display = "none";
        document.body.style.overflow = "auto";
    }
    
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
        if (event.target == logoutModal) {
            closeLogoutModal();
        }
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (modal.style.display === "block") {
                modal.style.display = "none";
            }
            if (logoutModal.style.display === "block") {
                closeLogoutModal();
            }
        }
    });

    // Refund request and view functions
    function openRefundRequestModal(orderId) {
        // Redirect to order-details.php which has the refund request form
        window.location.href = `../cart/order-details.php?order_id=${orderId}`;
    }

    function viewRefundDetails(orderId) {
        // Redirect to order-details.php which will show the refund details modal
        window.location.href = `../cart/order-details.php?order_id=${orderId}#view-refund`;
    }

    // AJAX Pagination Functions
    function loadOrdersPage(page) {
        console.log('Loading orders page:', page); // Debug log
        
        // Show loading indicator
        const ordersTable = document.getElementById('orders-table');
        const ordersPagination = document.getElementById('orders-pagination');
        
        if (!ordersTable) {
            console.error('Orders table not found');
            return false;
        }
        
        ordersTable.innerHTML = '<tbody><tr><td colspan="7" style="text-align: center; padding: 20px;">Loading...</td></tr></tbody>';
        
        // Make AJAX request
        fetch(`ajax-pagination.php?type=orders&page=${page}`)
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data); // Debug log
                ordersTable.innerHTML = data.table_html;
                if (data.pagination_html && ordersPagination) {
                    ordersPagination.innerHTML = data.pagination_html;
                    ordersPagination.style.display = 'flex';
                } else if (ordersPagination) {
                    ordersPagination.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                ordersTable.innerHTML = '<tbody><tr><td colspan="7" style="text-align: center; padding: 20px; color: red;">Error loading orders. Please try again.</td></tr></tbody>';
            });
        
        return false; // Prevent default action
    }

    function loadBulkOrdersPage(page) {
        console.log('Loading bulk orders page:', page); // Debug log
        
        // Show loading indicator
        const bulkOrdersTable = document.getElementById('bulk-orders-table');
        const bulkOrdersPagination = document.getElementById('bulk-orders-pagination');
        
        if (!bulkOrdersTable) {
            console.error('Bulk orders table not found');
            return false;
        }
        
        bulkOrdersTable.innerHTML = '<tbody><tr><td colspan="6" style="text-align: center; padding: 20px;">Loading...</td></tr></tbody>';
        
        // Make AJAX request
        fetch(`ajax-pagination.php?type=bulk_orders&page=${page}`)
            .then(response => {
                console.log('Bulk orders response status:', response.status); // Debug log
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Bulk orders data received:', data); // Debug log
                bulkOrdersTable.innerHTML = data.table_html;
                if (data.pagination_html && bulkOrdersPagination) {
                    bulkOrdersPagination.innerHTML = data.pagination_html;
                    bulkOrdersPagination.style.display = 'flex';
                } else if (bulkOrdersPagination) {
                    bulkOrdersPagination.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading bulk orders:', error);
                bulkOrdersTable.innerHTML = '<tbody><tr><td colspan="6" style="text-align: center; padding: 20px; color: red;">Error loading bulk orders. Please try again.</td></tr></tbody>';
            });
        
        return false; // Prevent default action
    }

    // Event delegation for pagination buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Handle pagination button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('pagination-link')) {
                e.preventDefault();
                e.stopPropagation();
                
                const page = parseInt(e.target.dataset.page);
                const type = e.target.dataset.type;
                
                console.log('Pagination button clicked:', {page, type}); // Debug log
                
                if (type === 'orders') {
                    loadOrdersPage(page);
                } else if (type === 'bulk_orders') {
                    loadBulkOrdersPage(page);
                }
            }
        });
    });
</script>

    <div id="footer-container">
        <?php require_once "../../user-includes/user-footer.php"; ?>
    </div>
</body>
</html>
