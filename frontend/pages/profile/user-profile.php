<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once "../../../backend/pages/admin-includes/database.php";
}
require_once "../../../includes/session-manager.php";

// Require user login - redirect if not authenticated
SessionManager::requireUserLogin('../../login/user/login-signup.php');

$user_id = SessionManager::getUserId();

// Get user information
$user_query = "SELECT id, firstname, lastname, username, email, created_at, profile_image, cloud_url, cloud_public_id FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

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

// Store email for queries
$user_email = $user['email'];

// Debug: Log what we got from database
error_log("Profile Debug - User data from DB: " . print_r($user, true));

// Ensure username is always set with fallback
if (!isset($user['username']) || empty($user['username']) || $user['username'] === null || trim($user['username']) === '') {
    error_log("Profile Debug - Username is empty, using fallback");
    // Use email prefix as fallback if username is not available
    $user['username'] = explode('@', $user['email'])[0];
} else {
    error_log("Profile Debug - Username found: " . $user['username']);
}

// Ensure all fields are properly set
$user['email'] = $user['email'] ?? 'N/A';
$user['firstname'] = $user['firstname'] ?? 'User';
$user['lastname'] = $user['lastname'] ?? 'Name';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables for account settings
$settings_message = "";
$settings_error = "";
$settings_profile_public_id = $user['cloud_public_id'] ?? '';

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        // Verify current password
        $pwd_query = "SELECT password FROM users WHERE id = ?";
        $pwd_stmt = mysqli_prepare($conn, $pwd_query);
        mysqli_stmt_bind_param($pwd_stmt, "i", $user_id);
        mysqli_stmt_execute($pwd_stmt);
        $pwd_result = mysqli_stmt_get_result($pwd_stmt);
        $pwd_user = mysqli_fetch_assoc($pwd_result);
        
        if (password_verify($current_password, $pwd_user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $settings_message = "Password updated successfully!";
            } else {
                $settings_error = "Error updating password.";
            }
        } else {
            $settings_error = "Current password is incorrect.";
        }
    } else {
        $settings_error = "New passwords do not match.";
    }
}

// Get message from session if exists
if (isset($_SESSION['message'])) {
    $settings_message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Determine profile image url
$profile_default_image_path = '/assets/images/profile.svg';
$profile_image_url = $profile_default_image_path;

if (isset($user['cloud_url']) && !empty(trim($user['cloud_url']))) {
    $profile_image_url = trim($user['cloud_url']);
} elseif (isset($user['profile_image']) && !empty(trim($user['profile_image']))) {
    $db_path = trim($user['profile_image']);
    if ($db_path[0] !== '/') {
        $db_path = '/' . $db_path;
    }
    $profile_image_url = $db_path;
}

error_log("Profile Debug - Final user array before display: username=" . ($user['username'] ?? 'NONE') . ", email=" . ($user['email'] ?? 'NONE'));

// Pagination settings
$orders_per_page = 6;
$bulk_orders_per_page = 6;
$orders_page = isset($_GET['orders_page']) ? max(1, intval($_GET['orders_page'])) : 1;
$bulk_orders_page = isset($_GET['bulk_orders_page']) ? max(1, intval($_GET['bulk_orders_page'])) : 1;
$orders_offset = ($orders_page - 1) * $orders_per_page;
$bulk_orders_offset = ($bulk_orders_page - 1) * $bulk_orders_per_page;

// Fetch regular orders with product images
$orders_count_query = "SELECT COUNT(*) as total FROM orders WHERE customer_email = ?";
$orders_count_stmt = mysqli_prepare($conn, $orders_count_query);
mysqli_stmt_bind_param($orders_count_stmt, "s", $user_email);
mysqli_stmt_execute($orders_count_stmt);
$orders_count_result = mysqli_stmt_get_result($orders_count_stmt);
$total_orders = mysqli_fetch_assoc($orders_count_result)['total'];
$total_orders_pages = ceil($total_orders / $orders_per_page);
mysqli_stmt_close($orders_count_stmt);

// Fetch orders with delivery/pickup date
$orders_query = "SELECT o.order_id, o.status, o.order_date, o.total_items, o.total_amount, o.delivery_method,
                        COALESCE(o.delivery_date, o.pickup_date) as fulfillment_date
                 FROM orders o
                 WHERE o.customer_email = ?
                 ORDER BY o.order_date DESC
                 LIMIT ? OFFSET ?";
$orders_stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($orders_stmt, "sii", $user_email, $orders_per_page, $orders_offset);
mysqli_stmt_execute($orders_stmt);
$orders_result = mysqli_stmt_get_result($orders_stmt);

$all_orders = [];
while ($order = mysqli_fetch_assoc($orders_result)) {
    // Fetch product images for this order (limit to 3)
    $images_query = "SELECT image_path FROM order_items WHERE order_id = ? LIMIT 3";
    $images_stmt = mysqli_prepare($conn, $images_query);
    mysqli_stmt_bind_param($images_stmt, "i", $order['order_id']);
    mysqli_stmt_execute($images_stmt);
    $images_result = mysqli_stmt_get_result($images_stmt);
    
    $order['product_images'] = [];
    while ($img = mysqli_fetch_assoc($images_result)) {
        $order['product_images'][] = $img['image_path'];
    }
    mysqli_stmt_close($images_stmt);
    
    $all_orders[] = $order;
}
mysqli_stmt_close($orders_stmt);

// Fetch bulk orders
$bulk_count_query = "SELECT COUNT(*) as total FROM bulk_orders WHERE user_id = ?";
$bulk_count_stmt = mysqli_prepare($conn, $bulk_count_query);
if ($bulk_count_stmt) {
    mysqli_stmt_bind_param($bulk_count_stmt, "i", $user_id);
    mysqli_stmt_execute($bulk_count_stmt);
    $bulk_count_result = mysqli_stmt_get_result($bulk_count_stmt);
    $total_bulk_orders = mysqli_fetch_assoc($bulk_count_result)['total'];
    $total_bulk_orders_pages = ceil($total_bulk_orders / $bulk_orders_per_page);
    mysqli_stmt_close($bulk_count_stmt);
} else {
    $total_bulk_orders = 0;
    $total_bulk_orders_pages = 0;
}

$bulk_orders_query = "SELECT id, unique_order_id, date_needed as delivery_date, time_needed, 
                            created_at, status, total_items, total_amount, discount_total, order_type
                     FROM bulk_orders 
                     WHERE user_id = ? 
                     ORDER BY created_at DESC
                     LIMIT ? OFFSET ?";
$bulk_orders_stmt = mysqli_prepare($conn, $bulk_orders_query);
$all_bulk_orders = [];
if ($bulk_orders_stmt) {
    mysqli_stmt_bind_param($bulk_orders_stmt, "iii", $user_id, $bulk_orders_per_page, $bulk_orders_offset);
    mysqli_stmt_execute($bulk_orders_stmt);
    $bulk_orders_result = mysqli_stmt_get_result($bulk_orders_stmt);
    
    while ($bulk = mysqli_fetch_assoc($bulk_orders_result)) {
        $all_bulk_orders[] = $bulk;
    }
    mysqli_stmt_close($bulk_orders_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="user-profile.css">
    <link rel="stylesheet" href="../account/css/profile-picture-ajax.css">
    <?php 
    // Store user data before navbar include (navbar overwrites $user variable)
    $profile_user_data = $user;
    include "../../user-includes/navbar/customer-navigation.php"; 
    // Restore user data after navbar include
    $user = $profile_user_data;
    ?>
</head>

<body>
<?php include "../../user-includes/bread-crumb/bread-crumb.php"; ?>

<div class="profile-wrapper">
    <!-- Sidebar -->
    <aside class="profile-sidebar">
        <div class="sidebar-header">
            <div class="profile-avatar">
                <?php 
                $has_profile_image = ($profile_image_url !== $profile_default_image_path);
                
                if ($has_profile_image): ?>
                    <img src="<?= htmlspecialchars($profile_image_url) ?>" alt="Profile Image" />
                <?php else: 
                    $initials = strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1));
                ?>
                    <span class="profile-initial"><?= htmlspecialchars($initials) ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-text">
                <h2>Hello, <?php echo htmlspecialchars($user['firstname'] ?? 'User'); ?></h2>
                <p class="username">@<?php echo htmlspecialchars($user['username'] ?? 'user'); ?></p>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <button class="nav-item" data-section="account-settings">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Account Settings</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            
            <button class="nav-item active" data-section="orders">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                </svg>
                <span>Orders</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            
            <button class="nav-item" data-section="bulk-orders">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Bulk Orders</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            
            <button class="nav-item" data-section="testimonials">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
                <span>Testimonials</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            
            <button class="nav-item" onclick="showLogoutConfirmation()">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Sign out</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="profile-content">
        <!-- Account Settings Section -->
        <section id="account-settings-section" class="content-section" style="display: none;">
            <h1 class="section-title">Account Settings</h1>
            
            <div class="settings-container">
                <!-- Profile Picture Section -->
                <div class="profile-picture-section">
                    <div class="avatar-upload-container" id="avatar-upload-container">
                        <div class="avatar" id="avatar">
                            <?php if ($has_profile_image): ?>
                                <img id="profile-image" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: 
                                $initials_settings = strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1));
                            ?>
                                <span id="initials"><?php echo $initials_settings; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-overlay">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                                <circle cx="12" cy="13" r="3"></circle>
                            </svg>
                        </div>
                        <?php if ($has_profile_image && !empty($settings_profile_public_id)): ?>
                            <button type="button" class="remove-avatar-btn" id="remove-avatar-btn" data-public-id="<?php echo htmlspecialchars($settings_profile_public_id); ?>" onclick="openRemovePictureModal('<?php echo htmlspecialchars($settings_profile_public_id); ?>')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="avatar-hint">Click to change profile picture</p>
                    <input type="file" id="file-input" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp">
                    
                    <!-- Hidden fields for CSRF token and user ID -->
                    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                </div>

                <div class="settings-card">
                    <div class="card-header">
                        <h3>Personal Information</h3>
                        <p>Your account details</p>
                    </div>
                    <div class="settings-grid">
                        <div class="form-group">
                            <label>Username</label>
                            <div class="readonly-field"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="readonly-field"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label>First Name</label>
                            <div class="readonly-field"><?php echo htmlspecialchars($user['firstname'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Last Name</label>
                            <div class="readonly-field"><?php echo htmlspecialchars($user['lastname'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Security</h3>
                        <p>Manage your password</p>
                    </div>
                    <div class="password-section">
                        <div class="password-info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <div>
                                <div class="password-label">Password</div>
                                <div class="password-value">••••••••••</div>
                            </div>
                        </div>
                        <button type="button" class="btn-change-password" onclick="openPasswordModal()">Change Password</button>
                    </div>
                </div>
            </div>

            <!-- Password Change Modal -->
            <div id="passwordModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closePasswordModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </span>
                    <h2>Change Password</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="current_password">Current Password:</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div style="display: flex; gap: 15px; justify-content: center;">
                            <button type="button" class="btn cancel-btn" onclick="closePasswordModal()">Cancel</button>
                            <button type="submit" name="change_password" class="btn update-btn" id="updatePasswordBtn">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Remove Picture Confirmation Modal -->
            <div id="removePictureModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeRemovePictureModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </span>
                    <h2>Remove Profile Picture</h2>
                    <p style="text-align: center; margin-bottom: 2rem; color: var(--text-muted);">
                        Are you sure you want to remove your profile picture? This action cannot be undone.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="button" class="btn cancel-btn" onclick="closeRemovePictureModal()">Cancel</button>
                        <button type="button" class="btn update-btn" id="confirmRemoveBtn" onclick="confirmRemoveProfilePicture()" style="background: var(--error);">Remove Picture</button>
                    </div>
                </div>
            </div>

            <!-- Confirmation Popup -->
            <div id="confirmationPopup"></div>
        </section>

        <!-- Orders Section -->
        <section id="orders-section" class="content-section active">
            <h1 class="section-title">Orders</h1>
            
            <?php if (count($all_orders) > 0): ?>
                <div class="orders-grid">
                    <?php foreach ($all_orders as $order): ?>
                        <?php
                            $status_lower = strtolower($order['status']);
                            $is_delivered = ($status_lower === 'delivered' || $status_lower === 'picked-up' || $status_lower === 'picked up');
                            $order_number = str_pad($order['order_id'], 7, '0', STR_PAD_LEFT);
                        ?>
                        <div class="order-card">
                            <!-- Product Images -->
                            <div class="order-images">
                                <?php 
                                $image_count = count($order['product_images']);
                                $images_to_show = min($image_count, 3);
                                for ($i = 0; $i < $images_to_show; $i++): 
                                    $img_path = $order['product_images'][$i];
                                    // Handle image path
                                    if (!empty($img_path)) {
                                        if (strpos($img_path, 'http') === 0) {
                                            $display_path = $img_path;
                                        } else {
                                            $display_path = '/' . ltrim($img_path, '/');
                                        }
                                    } else {
                                        $display_path = '/assets/images/placeholder.jpg';
                                    }
                                ?>
                                    <img src="<?php echo htmlspecialchars($display_path); ?>" alt="Product" onerror="this.src='/assets/images/placeholder.jpg'">
                                <?php endfor; ?>
                            </div>
                            
                            <!-- Order Info -->
                            <div class="order-info">
                                <div class="info-row">
                                    <div class="info-item">
                                        <span class="info-label">Order number</span>
                                        <span class="info-value"><?php echo $order_number; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label"><?php echo ucfirst($order['delivery_method']); ?> date</span>
                                        <span class="info-value"><?php echo $order['fulfillment_date'] ? date('d F Y', strtotime($order['fulfillment_date'])) : 'Pending'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-item">
                                        <span class="info-label">Total items</span>
                                        <span class="info-value"><?php echo $order['total_items']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total</span>
                                        <span class="info-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-item full-width">
                                        <span class="info-label">Status</span>
                                        <span class="status-badge status-<?php echo $status_lower; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="order-actions">
                                <a href="../cart/order-details.php?order_id=<?php echo $order['order_id']; ?>" class="btn-view-order">VIEW ORDER</a>
                                <button class="btn-refund" <?php echo !$is_delivered ? 'disabled' : ''; ?> 
                                        <?php if ($is_delivered): ?>onclick="window.location.href='../cart/order-details.php?order_id=<?php echo $order['order_id']; ?>#refund'"<?php endif; ?>>
                                    Refund
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_orders_pages > 1): ?>
                <div class="pagination">
                    <?php if ($orders_page > 1): ?>
                        <a href="?orders_page=<?php echo ($orders_page - 1); ?>" class="page-btn">&laquo;</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_orders_pages; $i++): ?>
                        <a href="?orders_page=<?php echo $i; ?>" class="page-btn <?php echo ($i == $orders_page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($orders_page < $total_orders_pages): ?>
                        <a href="?orders_page=<?php echo ($orders_page + 1); ?>" class="page-btn">&raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="64" height="64">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    </svg>
                    <p>No orders found</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Bulk Orders Section -->
        <section id="bulk-orders-section" class="content-section">
            <h1 class="section-title">Bulk Orders</h1>
            
            <?php if (count($all_bulk_orders) > 0): ?>
                <div class="orders-grid">
                    <?php foreach ($all_bulk_orders as $bulk): ?>
                        <?php
                            $status_lower = strtolower($bulk['status']);
                            $refund_ticket = 'BLK' . str_pad($bulk['id'], 6, '0', STR_PAD_LEFT);
                        ?>
                        <div class="order-card bulk-order-card">
                            <!-- Bulk Order Info -->
                            <div class="order-info">
                                <div class="info-row">
                                    <div class="info-item">
                                        <span class="info-label">Refund ticket number</span>
                                        <span class="info-value"><?php echo $refund_ticket; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label"><?php echo ucfirst($bulk['order_type']); ?> date</span>
                                        <span class="info-value"><?php echo date('d F Y', strtotime($bulk['delivery_date'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-item">
                                        <span class="info-label">Total items</span>
                                        <span class="info-value"><?php echo $bulk['total_items']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total amount</span>
                                        <span class="info-value">₱<?php echo number_format($bulk['discount_total'] > 0 ? $bulk['discount_total'] : $bulk['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-item full-width">
                                        <span class="info-label">Status</span>
                                        <span class="status-badge status-<?php echo $status_lower; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $bulk['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Button -->
                            <div class="order-actions">
                                <a href="../bulk/bulk-order-details.php?id=<?php echo $bulk['unique_order_id']; ?>" class="btn-view-details">View details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_bulk_orders_pages > 1): ?>
                <div class="pagination">
                    <?php if ($bulk_orders_page > 1): ?>
                        <a href="?bulk_orders_page=<?php echo ($bulk_orders_page - 1); ?>" class="page-btn">&laquo;</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_bulk_orders_pages; $i++): ?>
                        <a href="?bulk_orders_page=<?php echo $i; ?>" class="page-btn <?php echo ($i == $bulk_orders_page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($bulk_orders_page < $total_bulk_orders_pages): ?>
                        <a href="?bulk_orders_page=<?php echo ($bulk_orders_page + 1); ?>" class="page-btn">&raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="64" height="64">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <p>No bulk orders found</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials-section" class="content-section">
            <?php include 'testimonials-content.php'; ?>
        </section>
    </main>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <h3>Confirm Logout</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to logout from your account?</p>
            <p class="modal-subtitle">You will need to login again to access your account.</p>
        </div>
        <div class="modal-actions">
            <button onclick="closeLogoutModal()" class="btn-cancel">Cancel</button>
            <a href="/frontend/login/user/logout.php" class="btn-confirm">Yes, Logout</a>
        </div>
    </div>
</div>

<script>
// Section navigation
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const sectionName = this.getAttribute('data-section');
            
            if (!sectionName) return; // Skip if no section (e.g., logout button)
            
            // Remove active class from all nav items and sections
            navItems.forEach(nav => nav.classList.remove('active'));
            sections.forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });
            
            // Add active class to clicked nav item
            this.classList.add('active');
            
            // Show corresponding section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.style.display = 'block';
                setTimeout(() => targetSection.classList.add('active'), 10);
            }
        });
    });
    
    // Check URL hash for direct navigation
    const hash = window.location.hash.substring(1);
    if (hash) {
        const targetNav = document.querySelector(`[data-section="${hash}"]`);
        if (targetNav) {
            targetNav.click();
        }
    }
});

// Logout modal functions
function showLogoutConfirmation() {
    document.getElementById('logoutModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeLogoutModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLogoutModal();
        closePasswordModal();
        closeRemovePictureModal();
    }
});

// Account Settings Functions
<?php if ($settings_message): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showConfirmation('<?php echo addslashes($settings_message); ?>', 'success');
    });
<?php endif; ?>

<?php if ($settings_error): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showConfirmation('<?php echo addslashes($settings_error); ?>', 'error');
    });
<?php endif; ?>

// Confirmation popup function
function showConfirmation(message, type = 'success') {
    const popup = document.getElementById('confirmationPopup');
    
    const icon = type === 'success' ? '✓' : '✕';
    
    popup.innerHTML = `${icon} ${message}`;
    popup.className = `confirmation-popup ${type}`;
    
    // Trigger show animation
    setTimeout(() => {
        popup.classList.add('show');
    }, 10);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        popup.classList.remove('show');
        popup.classList.add('hide');
        setTimeout(() => {
            popup.className = '';
            popup.innerHTML = '';
        }, 400);
    }, 3000);
}

// Handle password form submission
const passwordForm = document.querySelector('form[method="POST"]');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        const btn = document.getElementById('updatePasswordBtn');
        
        // Add loader to button
        if (!btn.querySelector('.btn-loader')) {
            const loader = document.createElement('span');
            loader.className = 'btn-loader';
            btn.insertBefore(loader, btn.firstChild);
            btn.classList.add('loading');
        }
    });
}

function openPasswordModal() {
    document.getElementById('passwordModal').classList.add('show');
}

function closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    modal.classList.remove('show');
    // Reset form
    const form = document.querySelector('form[method="POST"]');
    if (form) form.reset();
    // Remove loader if exists
    const btn = document.getElementById('updatePasswordBtn');
    if (btn) {
        const loader = btn.querySelector('.btn-loader');
        if (loader) {
            loader.remove();
            btn.classList.remove('loading');
        }
    }
}

// Remove Picture Modal Functions
let currentPublicId = '';

function openRemovePictureModal(publicId) {
    if (!publicId || publicId.trim() === '') {
        console.error('No valid public ID provided to modal');
        showConfirmation('Error: No image ID found', 'error');
        return;
    }
    
    currentPublicId = publicId.trim();
    document.getElementById('removePictureModal').classList.add('show');
}

function closeRemovePictureModal() {
    document.getElementById('removePictureModal').classList.remove('show');
    currentPublicId = '';
    
    // Reset the confirm button state
    const confirmBtn = document.getElementById('confirmRemoveBtn');
    if (confirmBtn) {
        confirmBtn.innerHTML = 'Remove Picture';
        confirmBtn.disabled = false;
        confirmBtn.style.background = 'var(--error)';
    }
}

function confirmRemoveProfilePicture() {
    if (!currentPublicId) {
        console.error('No public ID provided');
        showConfirmation('Error: No image ID found', 'error');
        closeRemovePictureModal();
        return;
    }
    
    // Add loading state to confirm button
    const confirmBtn = document.getElementById('confirmRemoveBtn');
    confirmBtn.innerHTML = '<span class="btn-loader"></span> Removing...';
    confirmBtn.disabled = true;
    
    // Call the direct removal function
    removeProfilePictureDirectly(currentPublicId);
}

// Direct removal function
function removeProfilePictureDirectly(publicId) {
    // Call the remove function from the external JS file but tell it to skip confirmation
    if (typeof handleRemoveProfilePicture === 'function') {
        // Temporarily override any confirm dialogs
        const originalConfirm = window.confirm;
        window.confirm = function() { return true; };
        
        try {
            // Call the remove function
            handleRemoveProfilePicture(publicId);
            
            // Close modal after successful call
            setTimeout(() => {
                closeRemovePictureModal();
            }, 500);
            
        } catch (error) {
            console.error('Error removing profile picture:', error);
            showConfirmation('Error removing profile picture', 'error');
            closeRemovePictureModal();
        }
        
        // Restore original confirm function after a short delay
        setTimeout(() => {
            window.confirm = originalConfirm;
        }, 1000);
    } else {
        console.error('handleRemoveProfilePicture function not found');
        showConfirmation('Error: Remove function not available', 'error');
        closeRemovePictureModal();
    }
}

</script>

<!-- Include AJAX JavaScript for profile picture -->
<script src="../account/js/profile-picture-ajax.js"></script>

<div id="footer-container">
    <?php require_once "../../user-includes/user-footer.php"; ?>
</div>
</body>
</html>
