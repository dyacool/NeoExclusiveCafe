<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Debug session status
error_log("[Session Debug] product-dashboard.php - Session Data: " . print_r($_SESSION, true));
error_log("[Session Debug] product-dashboard.php - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set'));
error_log("[Session Debug] product-dashboard.php - User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Not set'));

$page_title = "Products";
$additional_css = [
    "/frontend/pages/products/product-dashboard.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../user-includes/preview-mode.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../backend/pages/products/todays-products-handler.php";

// Clean up past dates automatically when page loads
cleanupPastDates();

// DISABLED: Function to truncate cart_availtoday when business hours are closed OR items are from previous days
// This functionality is commented out for now
/*
function truncateCartIfBusinessClosed() {
    global $conn;
    
    try {
        $truncated = false;
        
        // STEP 1: Remove old date assignments from products (date-based cleanup)
        // Remove dates from previous days so products are no longer marked for same-day delivery
        
        // Remove old dates from Today's products table
        $old_todays_dates = "DELETE FROM todays_products_dates WHERE available_date < CURDATE()";
        $result1 = $conn->query($old_todays_dates);
        $removed_todays = ($result1 && $conn->affected_rows > 0) ? $conn->affected_rows : 0;
        
        // Remove old dates from regular products' today dates table
        $old_regular_dates = "DELETE FROM regular_products_today_dates WHERE available_date < CURDATE()";
        $result2 = $conn->query($old_regular_dates);
        $removed_regular = ($result2 && $conn->affected_rows > 0) ? $conn->affected_rows : 0;
        
        $total_removed = $removed_todays + $removed_regular;
        if ($total_removed > 0) {
            error_log("Auto-cleanup (product-dashboard): Removed $removed_todays old dates from todays_products_dates, $removed_regular from regular_products_today_dates");
            $truncated = true;
        }
        
        // STEP 1B: Clean up cart items for products that no longer have valid same-day dates
        $cleanup_cart = "DELETE FROM availtoday_cart WHERE DATE(created_at) < CURDATE()";
        $cleanup_result = $conn->query($cleanup_cart);
        if ($cleanup_result && $conn->affected_rows > 0) {
            error_log("Auto-cleanup (product-dashboard): Removed {$conn->affected_rows} old cart items from previous days");
            $truncated = true;
        }
        
        // STEP 2: Check if business is closed (time-based truncation)
        // Get current time
        $current_time = date('H:i:s');
        
        // Get business hours
        $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
        $business_hours_result = $conn->query($business_hours_query);
        
        if (!$business_hours_result) {
            error_log("Failed to get business hours: " . $conn->error);
            return $truncated;
        }
        
        if ($business_hours_result->num_rows === 0) {
            // No business hours set, show loading state
            $opening_time = 'Loading...';
            $closing_time = 'Loading...';
            error_log("No business hours set, showing loading state");
        } else {
            $business_hours = $business_hours_result->fetch_assoc();
            $opening_time = $business_hours['opening_time'];
            $closing_time = $business_hours['closing_time'];
            error_log("Business hours: $opening_time - $closing_time");
        }
        
        // Check if current time is after closing time
        // Convert times to minutes for proper comparison (handles midnight crossing)
        $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
        $closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));
        $opening_minutes = (intval(substr($opening_time, 0, 2)) * 60) + intval(substr($opening_time, 3, 2));
        
        // Handle midnight crossing - if current time is much earlier than closing time, we're past midnight
        $is_closed = false;
        
        // Check if we're past midnight (current time is much earlier than closing time)
        if ($closing_minutes > 1200 && $current_minutes < 600) { // If closing time is after 8 PM and current time is before 10 AM
            // We're past midnight, so business is closed
            $is_closed = true;
            error_log("Business closed - past midnight (current: $current_minutes, closing: $closing_minutes)");
        } else if ($current_minutes > $closing_minutes) {
            // Normal case - current time is after closing time
            $is_closed = true;
            error_log("Business closed - after closing time (current: $current_minutes, closing: $closing_minutes)");
        }
        
        error_log("Time analysis: current=$current_time ($current_minutes min), opening=$opening_time ($opening_minutes min), closing=$closing_time ($closing_minutes min), is_closed=" . ($is_closed ? 'Yes' : 'No'));
        
        if ($is_closed) {
            // Check if cart has items before truncating
            $count_query = "SELECT COUNT(*) as cart_count FROM availtoday_cart";
            $count_result = $conn->query($count_query);
            
            if ($count_result) {
                $count_data = $count_result->fetch_assoc();
                $cart_count = $count_data['cart_count'];
                error_log("Cart currently has $cart_count items");
                
                if ($cart_count > 0) {
                    // Business is closed, truncate the availtoday_cart table
                    $truncate_query = "TRUNCATE TABLE availtoday_cart";
                    $truncate_result = $conn->query($truncate_query);
                    
                    if ($truncate_result) {
                        error_log("SUCCESS: Cart truncated successfully - $cart_count items removed");
                        $truncated = true;
                    } else {
                        error_log("ERROR: Failed to truncate cart: " . $conn->error);
                        return $truncated;
                    }
                } else {
                    error_log("Cart is already empty - no action needed");
                    return $truncated;
                }
            } else {
                error_log("ERROR: Failed to count cart items: " . $conn->error);
                return false;
            }
        }
        
        return false; // No action taken
    } catch (Exception $e) {
        error_log("Error truncating cart: " . $e->getMessage());
        return false;
    }
}
*/

// DISABLED: Check and truncate cart if business is closed
// $cart_truncated = truncateCartIfBusinessClosed();
$cart_truncated = false; // Disabled for now

// Debug: Log the result
// error_log("Cart truncation result: " . ($cart_truncated ? 'SUCCESS' : 'No action needed'));

if ($cart_truncated) {
    // Clear any session cart data
    if (isset($_SESSION['availableTodayCart'])) {
        unset($_SESSION['availableTodayCart']);
    }
    if (isset($_SESSION['availableTodayCartTotal'])) {
        unset($_SESSION['availableTodayCartTotal']);
    }
    
    // Set a flag to show notification
    $_SESSION['cart_truncated_notification'] = true;
    
    // Also clear localStorage via JavaScript
    echo "<script>
        if (typeof localStorage !== 'undefined') {
            localStorage.removeItem('availableTodayCart');
            localStorage.removeItem('availableTodayCartTotal');
        }
    </script>";
}
?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>
    <div id="confirmationPopup" class="confirmation-popup"></div>

<div class="wrapper">

    <?php if (isset($_SESSION['cart_truncated_notification']) && $_SESSION['cart_truncated_notification']): ?>
        <div class="cart-truncated-notification" id="cartTruncatedNotification">
            <div class="notification-content">
                <span>ðŸ• Business hours closed. Cart has been cleared for the day.</span>
                <button onclick="closeCartTruncatedNotification()" class="close-notification-btn">Ã—</button>
            </div>
        </div>
        <?php unset($_SESSION['cart_truncated_notification']); ?>
    <?php endif; ?>

    <!-- Debug: Manual test button for cart truncation -->
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
        <div style="position: fixed; top: 80px; right: 20px; z-index: 9999; background: #333; color: white; padding: 10px; border-radius: 5px; font-size: 12px;">
            <button onclick="testCartTruncation()" style="background: #ff6b6b; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Test Cart Truncation</button>
            <div id="truncationDebug" style="margin-top: 10px; font-size: 10px;"></div>
            
            <!-- Time Analysis Display -->
            <div style="margin-top: 10px; padding: 5px; background: #444; border-radius: 3px; font-size: 10px;">
                <strong>Time Analysis:</strong><br>
                Current: <?php echo date('H:i:s'); ?><br>
                <?php
                $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
                $business_hours_result = $conn->query($business_hours_query);
                if ($business_hours_result && $business_hours_result->num_rows > 0) {
                    $business_hours = $business_hours_result->fetch_assoc();
                    echo "Hours: " . $business_hours['opening_time'] . " - " . $business_hours['closing_time'] . "<br>";
                    
                    $current_time = date('H:i:s');
                    $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
                    $closing_minutes = (intval(substr($business_hours['closing_time'], 0, 2)) * 60) + intval(substr($business_hours['closing_time'], 3, 2));
                    
                    $is_closed = false;
                    if ($closing_minutes > 1200 && $current_minutes < 600) {
                        $is_closed = true;
                    } else if ($current_minutes > $closing_minutes) {
                        $is_closed = true;
                    }
                    
                    echo "Status: " . ($is_closed ? '<span style="color: #ff6b6b;">CLOSED</span>' : '<span style="color: #4CAF50;">OPEN</span>') . "<br>";
                } else {
                    echo "Hours: loading... (default)<br>";
                    echo "Status: <span style='color: #4CAF50;'>OPEN</span> (default)<br>";
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="main-container fade-in">
        <!-- Title - Always visible across all states -->
        <h1 class="prdct-title">
            <?php
            // Get category from URL
            $selected_category = isset($_GET['category']) ? $_GET['category'] : null;
            $category_name = 'All Products';
            
            if ($selected_category) {
                // Get category name
                $cat_query = "SELECT name FROM categories WHERE slug = ? AND is_active = 1";
                $cat_stmt = mysqli_prepare($conn, $cat_query);
                mysqli_stmt_bind_param($cat_stmt, "s", $selected_category);
                mysqli_stmt_execute($cat_stmt);
                $cat_result = mysqli_stmt_get_result($cat_stmt);
                if ($cat_row = mysqli_fetch_assoc($cat_result)) {
                    $category_name = $cat_row['name'];
                }
            }
            echo htmlspecialchars($category_name);
            ?>
        </h1>
        
        <!-- Category Tabs -->
        <div class="category-tabs">
            <a href="product-dashboard.php" class="category-tab <?php echo !$selected_category ? 'active' : ''; ?>">All Products</a>
            <?php
            // Fetch categories for tabs
            $tab_query = "SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
            $tab_result = mysqli_query($conn, $tab_query);
            if ($tab_result && mysqli_num_rows($tab_result) > 0) {
                while ($tab_cat = mysqli_fetch_assoc($tab_result)) {
                    $is_active = ($selected_category === $tab_cat['slug']) ? 'active' : '';
                    echo '<a href="product-dashboard.php?category=' . urlencode($tab_cat['slug']) . '" class="category-tab ' . $is_active . '">' . htmlspecialchars($tab_cat['name']) . '</a>';
                }
            }
            ?>
        </div>
        
        <!-- Subtitle - Only shown when business is open -->
        <div class="header-section" id="headerSection" style="display: none;">
            <h2 class="prdct-subtitle" id="currentDate"></h2>
        </div>
        
        <!-- Loading state message -->
        <div id="loadingMessage" class="loading-state-message">
            <div class="loading-spinner-container">
                <div class="loading-spinner"></div>
                <p>Loading Products...</p>
            </div>
        </div>
        
        <!-- Centered message for when business is closed -->
        <div id="closedMessage" class="business-closed-message" style="display: none;">
            <div class="closed-icon"></div>
            <h2>Business Hours Have Ended</h2>
            <p>Check again tomorrow for exciting pre-made breads!</p>
            <div class="business-hours-display" id="businessHoursDisplay">
                <div class="loading-spinner-small"></div>
                <span>Loading hours...</span>
            </div>
        </div>
        
        <div class="scroll-container" id="scrollContainer" style="display: none;">
            <div class="products-grid" id="productScroll">
                <?php
                    // Get today's date
                    $today_date = date('Y-m-d'); // Returns date in YYYY-MM-DD format
                    
                    // Get selected category from URL
                    $selected_category = isset($_GET['category']) ? $_GET['category'] : null;
                    $category_id = null;
                    
                    // If category is selected, get its ID
                    if ($selected_category) {
                        $cat_query = "SELECT id FROM categories WHERE slug = ? AND is_active = 1";
                        $cat_stmt = mysqli_prepare($conn, $cat_query);
                        mysqli_stmt_bind_param($cat_stmt, "s", $selected_category);
                        mysqli_stmt_execute($cat_stmt);
                        $cat_result = mysqli_stmt_get_result($cat_stmt);
                        if ($cat_row = mysqli_fetch_assoc($cat_result)) {
                            $category_id = $cat_row['id'];
                        }
                    }
                    
                    // Query for ALL products (status_id 1, 2, 3, 4) with optional category filter
                    // Status 4 = Same Day Order (changed from 3)
                    $sql = "SELECT 
                                p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id,
                                ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable,
                                p.availtoday_status_id, ats.name AS availtoday_status_name,
                                c.name AS category_name,
                                GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
                                GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates,
                                qpd.quantity as sameday_stock_today
                            FROM products p
                            LEFT JOIN product_statuses ps ON p.status_id = ps.id
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                            LEFT JOIN categories c ON p.category_id = c.id
                            LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id
                            LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id
                            LEFT JOIN quantity_per_day_sdo qpd ON p.id = qpd.product_id AND qpd.date = CURDATE()
                            WHERE p.deleted_at IS NULL 
                            AND p.id > 0 
                            AND p.status_id IN (1, 2, 3, 4)";
                    
                    // Add category filter if selected
                    if ($category_id) {
                        $sql .= " AND p.category_id = ?";
                    }
                    
                    $sql .= " GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, ps.name, pi.image_url, p.quantity, p.show_when_unavailable, p.availtoday_status_id, ats.name, c.name, qpd.quantity
                            ORDER BY p.is_featured DESC, p.name ASC";
                    
                    // Prepare and execute the statement
                    $stmt = $conn->prepare($sql);
                    if ($category_id) {
                        $stmt->bind_param("i", $category_id);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // Fetch all products into an array for custom sorting
                    $all_products = [];
                    while ($row = $result->fetch_assoc()) {
                        $all_products[] = $row;
                    }
                    
                    // Custom sort: Priority hierarchy - Available Today > Featured > Regular > Unavailable
                    usort($all_products, function($a, $b) use ($today_date) {
                        // Calculate unavailability for product A
                        $a_preorder_stock = $a['quantity'] ?? 0;
                        $a_sameday_stock = $a['sameday_stock_today'] ?? 0;
                        $a_has_availtoday = !empty($a['availtoday_status_id']);
                        $a_unavailable = false;
                        
                        if ($a['status_id'] == 4) {
                            $a_unavailable = ($a_sameday_stock == 0 || $a_sameday_stock === null);
                        } elseif (in_array($a['status_id'], [1, 2, 3])) {
                            if ($a_has_availtoday) {
                                $a_unavailable = ($a_preorder_stock == 0 && ($a_sameday_stock == 0 || $a_sameday_stock === null));
                            } else {
                                $a_unavailable = ($a_preorder_stock == 0);
                            }
                        }
                        
                        // Calculate unavailability for product B
                        $b_preorder_stock = $b['quantity'] ?? 0;
                        $b_sameday_stock = $b['sameday_stock_today'] ?? 0;
                        $b_has_availtoday = !empty($b['availtoday_status_id']);
                        $b_unavailable = false;
                        
                        if ($b['status_id'] == 4) {
                            $b_unavailable = ($b_sameday_stock == 0 || $b_sameday_stock === null);
                        } elseif (in_array($b['status_id'], [1, 2, 3])) {
                            if ($b_has_availtoday) {
                                $b_unavailable = ($b_preorder_stock == 0 && ($b_sameday_stock == 0 || $b_sameday_stock === null));
                            } else {
                                $b_unavailable = ($b_preorder_stock == 0);
                            }
                        }
                        
                        // Check if product A is available today
                        $a_available_today = false;
                        if (!empty($a['todays_product_dates'])) {
                            $a_dates = explode(', ', $a['todays_product_dates']);
                            $a_available_today = in_array($today_date, $a_dates);
                        }
                        if (!$a_available_today && !empty($a['regular_today_dates'])) {
                            $a_dates = explode(', ', $a['regular_today_dates']);
                            $a_available_today = in_array($today_date, $a_dates);
                        }
                        
                        // Check if product B is available today
                        $b_available_today = false;
                        if (!empty($b['todays_product_dates'])) {
                            $b_dates = explode(', ', $b['todays_product_dates']);
                            $b_available_today = in_array($today_date, $b_dates);
                        }
                        if (!$b_available_today && !empty($b['regular_today_dates'])) {
                            $b_dates = explode(', ', $b['regular_today_dates']);
                            $b_available_today = in_array($today_date, $b_dates);
                        }
                        
                        // Priority 0: Unavailable products go to the end
                        if ($a_unavailable && !$b_unavailable) return 1;
                        if (!$a_unavailable && $b_unavailable) return -1;
                        
                        // Priority 1: Available today (highest priority for available products)
                        if ($a_available_today && !$b_available_today) return -1;
                        if (!$a_available_today && $b_available_today) return 1;
                        
                        // Priority 2: Featured products
                        if ($a['is_featured'] && !$b['is_featured']) return -1;
                        if (!$a['is_featured'] && $b['is_featured']) return 1;
                        
                        // Priority 3: Alphabetical by name
                        return strcmp($a['name'], $b['name']);
                    });

                    if (count($all_products) > 0) {
                        foreach ($all_products as $row) {
                            // Get all images for this product
                            $images_sql = "SELECT image_url FROM product_images WHERE product_id = ?";
                            $images_stmt = $conn->prepare($images_sql);
                            $images_stmt->bind_param("i", $row['id']);
                            $images_stmt->execute();
                            $images_result = $images_stmt->get_result();
                            $images = [];
                            while ($image = $images_result->fetch_assoc()) {
                                $images[] = $image['image_url'];
                            }
                            
                            $productData = [
                                'id' => $row['id'],
                                'name' => $row['name'],
                                'price' => $row['price'],
                                'description' => $row['description'],
                                'status' => $row['status_name'],
                                'status_id' => $row['status_id'],
                                'images' => $images,
                                'is_featured' => (bool)$row['is_featured'],
                                'quantity' => $row['quantity'],
                                'sameday_stock_today' => $row['sameday_stock_today'] ?? 0,
                                'show_when_unavailable' => (bool)$row['show_when_unavailable'],
                                'availtoday_status_id' => $row['availtoday_status_id'],
                                'availtoday_status_name' => $row['availtoday_status_name'],
                                'todays_product_dates' => $row['todays_product_dates'] ? explode(', ', $row['todays_product_dates']) : [],
                                'regular_today_dates' => $row['regular_today_dates'] ? explode(', ', $row['regular_today_dates']) : []
                            ];
                            
                            $featuredClass = $row['is_featured'] ? 'featured-product' : '';
                            $statusClass = strtolower(str_replace(' ', '-', $row['status_name']));
                            
                            $productDataJson = htmlspecialchars(json_encode($productData), ENT_QUOTES, 'UTF-8');
                            // Get available dates for display
                            // Status 4 = Same Day Order (changed from 3)
                            $available_dates = $row['status_id'] == 4 ? $row['todays_product_dates'] : $row['regular_today_dates'];
                            
                            // Check if product is UNAVAILABLE
                            // Product availability depends on its capabilities:
                            // 1. Status 4 (Same Day ONLY): Check sameday_stock_today
                            // 2. Status 1/2/3 WITHOUT availtoday_status_id (Pre-order ONLY): Check products.quantity
                            // 3. Status 1/2/3 WITH availtoday_status_id (DUAL capability): Check BOTH stocks
                            //    - Unavailable only if BOTH pre-order AND same-day stocks are 0
                            $is_unavailable = false;
                            $unavailable_reason = '';
                            
                            $preorder_stock = $row['quantity'] ?? 0;
                            $sameday_stock = $row['sameday_stock_today'] ?? 0;
                            $has_availtoday = !empty($row['availtoday_status_id']);
                            
                            if ($row['status_id'] == 4) {
                                // Status 4: Same Day ONLY product
                                // Check same-day stock from quantity_per_day_sdo table
                                if ($sameday_stock == 0 || $sameday_stock === null) {
                                    $is_unavailable = true;
                                    $unavailable_reason = 'Out of Stock';
                                }
                            } elseif (in_array($row['status_id'], [1, 2, 3])) {
                                if ($has_availtoday) {
                                    // DUAL capability: Pre-order AND Same-day
                                    // Unavailable only if BOTH stocks are 0
                                    if ($preorder_stock == 0 && ($sameday_stock == 0 || $sameday_stock === null)) {
                                        $is_unavailable = true;
                                        $unavailable_reason = 'Out of Stock';
                                    }
                                } else {
                                    // Pre-order ONLY
                                    // Check regular quantity from products table
                                    if ($preorder_stock == 0) {
                                        $is_unavailable = true;
                                        $unavailable_reason = 'Out of Stock';
                                    }
                                }
                            }
                            
                            // Check if product is available TODAY
                            // Can be from either:
                            // 1. Pure status 4 (Same Day Order only) - uses todays_product_dates
                            // 2. Status 1/2/3 with same-day option - uses regular_today_dates
                            $is_available_today = false;
                            
                            // Check todays_product_dates (for status 4 products)
                            if (!empty($row['todays_product_dates'])) {
                                $todays_dates = explode(', ', $row['todays_product_dates']);
                                if (in_array($today_date, $todays_dates)) {
                                    $is_available_today = true;
                                }
                            }
                            
                            // Check regular_today_dates (for status 1/2/3 products with same-day option)
                            if (!$is_available_today && !empty($row['regular_today_dates'])) {
                                $regular_dates = explode(', ', $row['regular_today_dates']);
                                if (in_array($today_date, $regular_dates)) {
                                    $is_available_today = true;
                                }
                            }
                            
                            // Add unavailable class if product is unavailable
                            $unavailableClass = $is_unavailable ? 'unavailable-product' : '';
                            
                            echo "<div class='product-card {$featuredClass} {$unavailableClass}' data-status='" . htmlspecialchars($row['status_name']) . "' 
                                  data-available-dates='" . htmlspecialchars($available_dates ?? '') . "'
                                  data-product='" . $productDataJson . "' 
                                  data-unavailable='" . ($is_unavailable ? 'true' : 'false') . "'
                                  onclick='openProductModalFromData(this)'>";
                            
                            // Display badges: Show both if product is Available Today AND Featured
                            if ($is_unavailable) {
                                // Unavailable badge (highest priority, exclusive)
                                echo "<div class='unavailable-badge-left'>" . htmlspecialchars($unavailable_reason) . "</div>";
                            } else {
                                // Show today badge if available today
                                if ($is_available_today) {
                                    echo "<div class='today-badge-left'>Same Day Order</div>";
                                } else {
                                    // Show pre-order badge if not available today and not unavailable
                                    echo "<div class='preorder-badge-left'>Pre-Order</div>";
                                }
                                // Note: Featured badge is handled by CSS class 'featured-product' and image overlay
                            }
                            
                            echo "    <div class='product-image'>";
                            
                            // Image overlays: Show both if product is Available Today AND Featured
                            if ($is_unavailable) {
                                // Unavailable overlay (exclusive)
                                echo "<div class='unavailable-overlay'>
                                        <span class='unavailable-text'>UNAVAILABLE</span>
                                        <span class='unavailable-reason'>" . htmlspecialchars($unavailable_reason) . "</span>
                                      </div>";
                            } 
                            echo "    <img src='../../../assets/" . htmlspecialchars($row['image_url'] ?: 'images/no-image.jpg') . "' alt='" . htmlspecialchars($row['name']) . "'>
                                    </div>
                                    <div class='product-info'>
                                        <h3 class='productname'>" . htmlspecialchars($row['name']) . "</h3>
                                        <p class='price'>₱" . number_format($row['price'], 2) . "</p>";
                            
                            // First row: availtoday status badge and stock
                            echo "<div class='info-row-1'>";
                            if (!empty($row['availtoday_status_name'])) {
                                echo "<span class='availtoday-badge'>" . htmlspecialchars($row['availtoday_status_name']) . "</span>";
                            }
                            echo "<p class='stock'>Stock: " . $row['quantity'] . "</p>
                                  </div>";
                            
                            // Second row: status badge and available dates
                            echo "<div class='info-row-2'>
                                    <span class='status-badge status-{$statusClass}'>" . htmlspecialchars($row['status_name']) . "</span>";
                            
                            // Display available dates if product has them
                            if (!empty($available_dates)) {
                                // Format dates for display (e.g., "8/27, 8/28, 8/29")
                                $dates_array = explode(', ', $available_dates);
                                $formatted_dates = [];
                                foreach ($dates_array as $date) {
                                    $dateObj = DateTime::createFromFormat('Y-m-d', trim($date));
                                    if ($dateObj) {
                                        $formatted_dates[] = $dateObj->format('n/j'); // Format as M/D (e.g., 8/27)
                                    }
                                }
                                echo "<p class='available-dates'>Available Dates: " . htmlspecialchars(implode(', ', $formatted_dates)) . "</p>";
                            }
                            
                            echo "</div>";
                            
                            // Add to Cart button - disabled if unavailable
                            if ($is_unavailable) {
                                echo "<button class='add-to-cart unavailable-btn' disabled>Unavailable</button>";
                            } else {
                                echo "<button class='add-to-cart' onclick='event.stopPropagation(); addToCart(" . $row['id'] . ", this)'>Add to Cart</button>";
                            }
                            
                            echo "    </div>
                            </div>";
                        }
                    } else {
                        echo "<div class='no-products'>No products available for " . $today . " at the moment.</div>";
                    }
                    $stmt->close();
                    $conn->close();
                ?>
            </div> 
        </div> 
    </div>
</div>

<!-- Quantity Modal (appears before adding to cart) -->
<div id="quantityModal" class="modal" style="display: none;">
    <div class="modal-content quantity-modal-content fade-in-pop">
        <span class="close" onclick="closeQuantityModal()">&times;</span>
        <div class="quantity-modal-body">
            <h2 id="quantityModalProductName">Product Name</h2>
            <p class="quantity-modal-price" id="quantityModalPrice"></p>
            
            <!-- Order Type Selector (shown only if product has both pre-order and same day order) -->
            <div id="orderTypeSelector" class="order-type-selector" style="display: none;">
                <label>Order Type:</label>
                <div class="order-type-buttons">
                    <button type="button" class="order-type-btn active" data-type="preorder" onclick="selectOrderType('preorder')">
                        Pre-Order
                    </button>
                    <button type="button" class="order-type-btn" data-type="sameday" onclick="selectOrderType('sameday')">
                        Same Day Order
                    </button>
                </div>
            </div>
            
            <p class="quantity-modal-stock" id="quantityModalStock">Stock: 0</p>
            <p class="quantity-modal-date" id="quantityModalDate" style="display: none; font-size: 13px; color: #666; margin-top: 5px;">For: Today</p>
            
            <div class="quantity-modal-controls">
                <label>Quantity:</label>
                <div class="quantity-controls">
                    <button type="button" onclick="updateQuantityModalValue(-1)">-</button>
                    <input type="number" id="quantityModalInput" value="1" min="1" onchange="validateQuantityModalInput()">
                    <button type="button" onclick="updateQuantityModalValue(1)">+</button>
                </div>
            </div>
            
            <div class="quantity-modal-actions">
                <button class="btn-cancel" onclick="closeQuantityModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content fade-in-pop">
        <span class="close" onclick="closeProductModal()">&times;</span>
        <div class="modal-body">
            <div class="product-images">
                <div class="main-image">
                    <img id="modalMainImage" src="../../../assets/images/no-image.jpg" alt="Product Image">
                </div>
                <div class="thumbnail-container" id="thumbnailContainer">
                    <!-- Thumbnails will be added here dynamically -->
                </div>
            </div>
            <div class="product-details">
                <h2 class="modal-title" id="modalProductName"></h2>
                <p class="modal-price" id="modalProductPrice"></p>
                <div class="prdct-qty">
                    <span class="status-badge" id="modalProductStatus"></span>
                    <p class="stock" id="modalProductStock"></p>
                    <p class="available-days" id="modalProductAvailableDays" style="display: none;"></p>
                </div>
                <h3>Description:</h3>
                <div class="description" id="modalProductDescription"></div>
                <button class="add-to-cart" id="modalAddToCart" onclick="addToCartFromModal()">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Available Today Cart JavaScript -->
<script src="availtoday-cart.js"></script>

<script>
    // Check if user is logged in
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    const loginUrl = 'http://neocafe.cafe:8080/frontend/login/user/login-signup.php';
    
    // Function to check login and redirect if needed
    function checkLoginAndRedirect() {
        if (!isLoggedIn) {
            alert('Please login to add items to cart');
            window.location.href = loginUrl;
            return false;
        }
        return true;
    }
    
    // Remove console.log
    let productModalOpen = false;
    let totalProducts = 0;
    const itemsPerRow = 4;

    document.addEventListener('DOMContentLoaded', function() {
        const scrollContainer = document.getElementById('productScroll');
        const products = scrollContainer.querySelectorAll('.product-card');
        
        totalProducts = products.length;
        
        // Show scroll mode if more than 4 products
        if (totalProducts > itemsPerRow) {
            setupScroll();
        } else {
            // For 4 or less products, display them normally in a grid
            scrollContainer.classList.add('normal-grid');
        }
        
            // Initialize business hours functionality
        initBusinessHours();
    });

    function setupScroll() {
        const scrollContainer = document.getElementById('productScroll');
        scrollContainer.classList.add('scroll-mode');
        
        // No additional scroll listeners needed - native scrolling handles everything
    }

    // Smooth scroll to specific position
    function scrollToPosition(scrollContainer, targetPosition) {
        scrollContainer.scrollTo({
            left: targetPosition,
            behavior: 'smooth'
        });
    }

    // Available Today cart functions are now handled by availtoday-cart.js

    // Business Hours Management
    let businessHours = {
        openingTime: null,
        closingTime: null
    };

    function initBusinessHours() {
        loadBusinessHours();
        // Check every minute
        setInterval(checkBusinessHoursAndUpdateDisplay, 60000);
    }

    function loadBusinessHours() {
        fetch('get-business-hours.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.businessHours) {
                    businessHours.openingTime = data.businessHours.opening_time;
                    businessHours.closingTime = data.businessHours.closing_time;
                    updateTimerDisplay();
                    // Check immediately after data loads
                    checkBusinessHoursAndUpdateDisplay();
                    // Hide loading message once data is loaded
                    hideLoadingMessage();
                }
            })
            .catch(error => {
                console.error('Error loading business hours:', error);
                hideLoadingMessage();
            });
    }
    
    function hideLoadingMessage() {
        const loadingMessage = document.getElementById('loadingMessage');
        if (loadingMessage) {
            loadingMessage.style.display = 'none';
        }
    }

    function checkBusinessHoursAndUpdateDisplay() {
        const currentTime = new Date().toTimeString().slice(0, 5);
        const isOpen = isWithinBusinessHours(currentTime);
        updateProductVisibility(isOpen);
        updateTimerDisplay();
    }

    function isWithinBusinessHours(currentTime) {
        if (!businessHours.openingTime || !businessHours.closingTime) return false;
        
        const currentMinutes = parseInt(currentTime.split(':')[0]) * 60 + parseInt(currentTime.split(':')[1]);
        const openingMinutes = parseInt(businessHours.openingTime.split(':')[0]) * 60 + parseInt(businessHours.openingTime.split(':')[1]);
        const closingMinutes = parseInt(businessHours.closingTime.split(':')[0]) * 60 + parseInt(businessHours.closingTime.split(':')[1]);
        
        return currentMinutes >= openingMinutes && currentMinutes < closingMinutes;
    }

    function updateProductVisibility(isOpen) {
        const productsGrid = document.getElementById('productScroll');
        const scrollContainer = document.getElementById('scrollContainer');
        const headerSection = document.getElementById('headerSection');
        const closedMessage = document.getElementById('closedMessage');
        const loadingMessage = document.getElementById('loadingMessage');
        const businessHoursDisplay = document.getElementById('businessHoursDisplay');
        
        // Hide loading message once we know the state
        if (loadingMessage) {
            loadingMessage.style.display = 'none';
        }
        
        if (!isOpen) {
            // Business is closed - show closed message
            productsGrid.style.display = 'none';
            scrollContainer.style.display = 'none';
            headerSection.style.display = 'none';
            closedMessage.style.display = 'flex';
            
            // Update business hours display with formatted time
            if (businessHours.openingTime && businessHours.closingTime) {
                businessHoursDisplay.innerHTML = `<span>Business Hours: ${formatTimeForDisplay(businessHours.openingTime)} - ${formatTimeForDisplay(businessHours.closingTime)}</span>`;
            }
        } else {
            // Business is open - show products
            productsGrid.style.display = 'grid';
            scrollContainer.style.display = 'block';
            headerSection.style.display = 'block';
            closedMessage.style.display = 'none';
            
            // Update subtitle with current date
            const subtitle = document.getElementById('currentDate');
            if (subtitle) {
                subtitle.textContent = new Date().toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }
        }
    }

    function updateTimerDisplay() {
        // Update business hours display in closed message if visible
        const businessHoursDisplay = document.getElementById('businessHoursDisplay');
        if (businessHoursDisplay && businessHours.openingTime && businessHours.closingTime) {
            businessHoursDisplay.innerHTML = `<span>Business Hours: ${formatTimeForDisplay(businessHours.openingTime)} - ${formatTimeForDisplay(businessHours.closingTime)}</span>`;
        }
    }

    function formatTimeForDisplay(timeString) {
        // Convert 24-hour format to 12-hour format
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    function updateQuantity(button, change) {
        const container = button.parentElement;
        const input = container.querySelector('input');
        const newValue = parseInt(input.value) + change;
        if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
            input.value = newValue;
        }
    }

    function validateQuantity(input) {
        const value = parseInt(input.value);
        const max = parseInt(input.max);
        const min = parseInt(input.min);
        
        if (isNaN(value) || value < min) {
            input.value = min;
        } else if (value > max) {
            input.value = max;
        }
    }

    function showConfirmation(message, isError = false) {
        const popup = document.getElementById('confirmationPopup');
        popup.textContent = message;
        popup.className = 'confirmation-popup' + (isError ? ' error' : ' success');
        popup.classList.add('show');
        
        setTimeout(() => {
            popup.classList.remove('show');
            popup.classList.add('hide');
            setTimeout(() => {
                popup.classList.remove('hide');
            }, 300);
        }, 3000);
    }

    function openProductModal(product) {
        try {
            if (!product || typeof product !== 'object') {
                console.error('Invalid product data:', product);
                return;
            }

            currentProductModalData = product; // Store for later use
            const modal = document.getElementById('productModal');
            const mainImage = document.getElementById('modalMainImage');
            const thumbnails = document.getElementById('thumbnailContainer');
            const productName = document.getElementById('modalProductName');
            const productPrice = document.getElementById('modalProductPrice');
            const productStatus = document.getElementById('modalProductStatus');
            const productDescription = document.getElementById('modalProductDescription');
            const productStock = document.getElementById('modalProductStock');
            const productAvailableDays = document.getElementById('modalProductAvailableDays');
            // const quantityInput = document.getElementById('modalQuantity'); // Removed - using quantity modal
            const addToCartBtn = document.getElementById('modalAddToCart');

            // Set main content
            productName.textContent = product.name || 'Unknown Product';
            productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
            productStatus.textContent = product.status || 'Available Today';
            productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
            productDescription.textContent = product.description || 'No description available';
            productStock.textContent = 'Stock: ' + (product.quantity || 0);

            // Handle available dates in modal
            // Status 4 = Same Day Order (changed from 3)
            const availableDates = product.status_id == 4 ? product.todays_product_dates : product.regular_today_dates;
            if (availableDates && availableDates.length > 0) {
                // Format dates for display (e.g., "8/27, 8/28, 8/29")
                const formattedDates = availableDates.map(date => {
                    const dateObj = new Date(date + 'T00:00:00'); // Add time to avoid timezone issues
                    return (dateObj.getMonth() + 1) + '/' + dateObj.getDate(); // Format as M/D
                });
                productAvailableDays.textContent = 'Available: ' + formattedDates.join(', ');
                productAvailableDays.style.display = 'block';
            } else {
                productAvailableDays.style.display = 'none';
            }

            // Set up images
            if (product.images && Array.isArray(product.images) && product.images.length > 0) {
                mainImage.src = '../../../assets/' + product.images[0];
                thumbnails.innerHTML = '';
                product.images.forEach((image, index) => {
                    if (image) {
                        const thumb = document.createElement('img');
                        thumb.src = '../../../assets/' + image;
                        thumb.alt = `${product.name || 'Product'} view ${index + 1}`;
                        thumb.onclick = () => mainImage.src = thumb.src;
                        thumbnails.appendChild(thumb);
                    }
                });
            } else {
                mainImage.src = '../../../assets/images/no-image.jpg';
                thumbnails.innerHTML = '';
            }

            // Check if product is unavailable
            let isUnavailable = false;
            let unavailableReason = '';
            
            const preorderStock = product.quantity || 0;
            const samedayStock = product.sameday_stock_today || 0;
            const hasAvailtoday = product.availtoday_status_id != null && product.availtoday_status_id != '';
            
            if (product.status_id == 4) {
                // Status 4: Same Day ONLY product
                // Check same-day stock (from quantity_per_day_sdo table)
                if (samedayStock == 0 || samedayStock === null) {
                    isUnavailable = true;
                    unavailableReason = 'Out of Stock';
                }
            } else if ([1, 2, 3].includes(product.status_id)) {
                if (hasAvailtoday) {
                    // DUAL capability: Pre-order AND Same-day
                    // Unavailable only if BOTH stocks are 0
                    if (preorderStock == 0 && (samedayStock == 0 || samedayStock === null)) {
                        isUnavailable = true;
                        unavailableReason = 'Out of Stock';
                    }
                } else {
                    // Pre-order ONLY
                    // Check regular quantity from products table
                    if (preorderStock == 0) {
                        isUnavailable = true;
                        unavailableReason = 'Out of Stock';
                    }
                }
            }
            
            // Set up Add to Cart button
            if (isUnavailable) {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Unavailable - ' + unavailableReason;
                addToCartBtn.classList.add('unavailable-btn');
                addToCartBtn.onclick = null;
            } else {
                addToCartBtn.disabled = false;
                addToCartBtn.textContent = 'Add to Cart';
                addToCartBtn.classList.remove('unavailable-btn');
                addToCartBtn.onclick = () => addToCartFromModal();
            }

            modal.classList.add('show');
        } catch (error) {
            console.error('Error in openProductModal:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

function closeProductModal() {
    productModalOpen = false;
    const modal = document.getElementById('productModal');
    modal.classList.remove('show');
}    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target == modal) {
            closeProductModal();
        }
    }

    // Add touch/swipe support for mobile
    // Enhanced touch/swipe support for smooth scrolling
    let isScrolling = false;
    let startX = 0;
    let scrollLeft = 0;

    document.addEventListener('touchstart', function(e) {
        const scrollContainer = document.getElementById('productScroll');
        if (!e.target.closest('#productScroll')) return;
        
        isScrolling = true;
        startX = e.touches[0].clientX;
        scrollLeft = scrollContainer.scrollLeft;
    }, { passive: true });

    document.addEventListener('touchmove', function(e) {
        if (!isScrolling) return;
        
        const scrollContainer = document.getElementById('productScroll');
        if (!e.target.closest('#productScroll')) return;
        
        const x = e.touches[0].clientX;
        const walk = (startX - x) * 2; // Scroll speed multiplier
        scrollContainer.scrollLeft = scrollLeft + walk;
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        isScrolling = false;
    }, { passive: true });
    
    // Function to close cart truncated notification
    function closeCartTruncatedNotification() {
        const notification = document.getElementById('cartTruncatedNotification');
        if (notification) {
            notification.style.display = 'none';
        }
    }
    
    // Function to test cart truncation manually
    function testCartTruncation() {
        const debugDiv = document.getElementById('truncationDebug');
        debugDiv.innerHTML = 'Testing cart truncation...';
        
        fetch('test-truncation.php')
            .then(response => response.text())
            .then(data => {
                debugDiv.innerHTML = data.replace(/\n/g, '<br>');
                // Refresh the page to see if cart was truncated
                setTimeout(() => {
                    location.reload();
                }, 2000);
            })
            .catch(error => {
                debugDiv.innerHTML = 'Error: ' + error.message;
            });
    }

    // Open product modal from data attribute
    window.openProductModalFromData = function(element) {
        const productData = JSON.parse(element.getAttribute('data-product'));
        openProductModal(productData);
    };

    // Quantity Modal Variables
    let pendingCartProduct = null;

    // Add to cart function - Opens quantity modal
    function addToCart(productId, button) {
        // Check if user is logged in
        if (!checkLoginAndRedirect()) {
            return;
        }
        
        const productCard = button.closest('.product-card');
        if (!productCard) {
            console.error('Product card not found');
            return;
        }
        
        const productData = JSON.parse(productCard.getAttribute('data-product'));
        const productName = productData.name;
        const productPrice = '₱' + parseFloat(productData.price).toFixed(2);
        const productStock = productData.quantity;
        const statusId = productData.status_id;
        const availtodayStatusId = productData.availtoday_status_id;
        
        // Store product info for later
        pendingCartProduct = {
            id: productId,
            name: productName,
            price: productPrice,
            stock: productStock,
            button: button,
            statusId: statusId,
            availtodayStatusId: availtodayStatusId,
            selectedOrderType: 'preorder' // Default
        };
        
        // Open quantity modal with order type options
        openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId);
    }

    // Add to cart from modal - Opens quantity modal
    function addToCartFromModal() {
        // Check if user is logged in
        if (!checkLoginAndRedirect()) {
            return;
        }
        
        if (!currentProductModalData) {
            console.error('No product data available');
            return;
        }
        
        const productName = currentProductModalData.name;
        const productPrice = '₱' + parseFloat(currentProductModalData.price).toFixed(2);
        const productStock = currentProductModalData.quantity;
        const statusId = currentProductModalData.status_id;
        const availtodayStatusId = currentProductModalData.availtoday_status_id;
        
        // Store product info for later
        pendingCartProduct = {
            id: currentProductModalData.id,
            name: productName,
            price: productPrice,
            stock: productStock,
            button: null,
            statusId: statusId,
            availtodayStatusId: availtodayStatusId,
            selectedOrderType: 'preorder' // Default
        };
        
        // Close product modal first
        closeProductModal();
        
        // Open quantity modal with order type options
        openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId);
    }

    // Check if same-day option should be available
    async function checkSameDayAvailability(productId) {
        try {
            const response = await fetch(`get-sdo-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                // Check if there's quantity available today
                const todayQuantity = data.quantity || 0;
                const hasDateToday = data.has_date_today || false;
                
                // Same-day is available if:
                // 1. There's a date for today AND
                // 2. There's quantity available
                return hasDateToday && todayQuantity > 0;
            }
            return false;
        } catch (error) {
            console.error('Error checking same-day availability:', error);
            return false;
        }
    }

    // Open quantity modal with order type selection
    async function openQuantityModalWithOrderType(productName, productPrice, productStock, statusId, availtodayStatusId) {
        const modal = document.getElementById('quantityModal');
        document.getElementById('quantityModalProductName').textContent = productName;
        document.getElementById('quantityModalPrice').textContent = productPrice;
        
        const quantityInput = document.getElementById('quantityModalInput');
        const orderTypeSelector = document.getElementById('orderTypeSelector');
        const dateDisplay = document.getElementById('quantityModalDate');
        
        // Determine which order types to show
        // Status 1, 2, 3 = Pre-order products
        // Status 4 = Same Day Order products
        // If product has both status (1,2,3) AND availtoday_status_id, show both options
        
        if ((statusId == 1 || statusId == 2 || statusId == 3) && availtodayStatusId) {
            // Product has BOTH pre-order and same day order
            // Check if same-day is actually available today
            const sameDayAvailable = await checkSameDayAvailability(pendingCartProduct.id);
            
            if (sameDayAvailable) {
                // Show both options
                orderTypeSelector.style.display = 'block';
                document.getElementById('quantityModalStock').textContent = `Stock: ${productStock}`;
                quantityInput.value = 1;
                quantityInput.max = productStock;
                selectOrderType('preorder'); // Default to pre-order
            } else {
                // Same-day not available - show pre-order only
                orderTypeSelector.style.display = 'none';
                dateDisplay.style.display = 'none';
                pendingCartProduct.selectedOrderType = 'preorder';
                document.getElementById('quantityModalStock').textContent = `Stock: ${productStock}`;
                quantityInput.value = 1;
                quantityInput.max = productStock;
            }
        } else if (statusId == 4) {
            // Same Day Order ONLY - Fetch today's quantity
            orderTypeSelector.style.display = 'none';
            dateDisplay.style.display = 'block';
            dateDisplay.textContent = 'For: Today';
            pendingCartProduct.selectedOrderType = 'sameday';
            
            // Fetch today's quantity before showing modal
            document.getElementById('quantityModalStock').textContent = 'Loading...';
            quantityInput.value = 1;
            quantityInput.max = 1;
            
            fetchTodayQuantity(pendingCartProduct.id);
        } else {
            // Pre-order ONLY (status 1, 2, or 3 without availtoday_status_id)
            orderTypeSelector.style.display = 'none';
            dateDisplay.style.display = 'none';
            pendingCartProduct.selectedOrderType = 'preorder';
            document.getElementById('quantityModalStock').textContent = `Stock: ${productStock}`;
            quantityInput.value = 1;
            quantityInput.max = productStock;
        }
        
        modal.style.display = 'flex';
    }

    // Select order type
    function selectOrderType(type) {
        const buttons = document.querySelectorAll('.order-type-btn');
        buttons.forEach(btn => {
            if (btn.getAttribute('data-type') === type) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        const dateDisplay = document.getElementById('quantityModalDate');
        const stockDisplay = document.getElementById('quantityModalStock');
        
        if (type === 'sameday') {
            dateDisplay.style.display = 'block';
            dateDisplay.textContent = 'For: Today';
            
            // Fetch today's specific quantity from database
            if (pendingCartProduct) {
                fetchTodayQuantity(pendingCartProduct.id);
            }
        } else {
            dateDisplay.style.display = 'none';
            
            // Restore original stock quantity for pre-order
            if (pendingCartProduct) {
                stockDisplay.textContent = `Stock: ${pendingCartProduct.stock}`;
                const quantityInput = document.getElementById('quantityModalInput');
                quantityInput.max = pendingCartProduct.stock;
                quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, pendingCartProduct.stock);
            }
        }
        
        if (pendingCartProduct) {
            pendingCartProduct.selectedOrderType = type;
        }
    }

    // Fetch today's specific quantity for same day orders
    function fetchTodayQuantity(productId) {
        const stockDisplay = document.getElementById('quantityModalStock');
        stockDisplay.textContent = 'Loading...';
        
        fetch(`get-sdo-quantity.php?product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const todayQuantity = data.quantity || 0;
                    stockDisplay.textContent = `Stock: ${todayQuantity}`;
                    
                    const quantityInput = document.getElementById('quantityModalInput');
                    quantityInput.max = todayQuantity;
                    quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, todayQuantity);
                    
                    if (todayQuantity === 0) {
                        stockDisplay.textContent = 'Stock: 0 (Not available today)';
                    }
                } else {
                    console.error('Error fetching today quantity:', data.error);
                    stockDisplay.textContent = 'Stock: 0';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                stockDisplay.textContent = 'Stock: 0';
            });
    }

    // Close quantity modal
    function closeQuantityModal() {
        document.getElementById('quantityModal').style.display = 'none';
        pendingCartProduct = null;
    }

    // Update quantity value
    function updateQuantityModalValue(change) {
        const input = document.getElementById('quantityModalInput');
        const currentValue = parseInt(input.value) || 1;
        const newValue = currentValue + change;
        const maxValue = parseInt(input.max);
        
        if (newValue >= 1 && newValue <= maxValue) {
            input.value = newValue;
        }
    }

    // Validate quantity input
    function validateQuantityModalInput() {
        const input = document.getElementById('quantityModalInput');
        const value = parseInt(input.value) || 1;
        const maxValue = parseInt(input.max);
        
        if (value < 1) {
            input.value = 1;
        } else if (value > maxValue) {
            input.value = maxValue;
        }
    }

    // Confirm and add to cart
    function confirmAddToCart() {
        if (!pendingCartProduct) return;
        
        const quantity = parseInt(document.getElementById('quantityModalInput').value) || 1;
        const confirmBtn = document.querySelector('.quantity-modal-actions .btn-confirm');
        
        if (!confirmBtn) return;
        
        // Store original button text
        const originalText = confirmBtn.textContent;
        
        // Disable button and show loading state
        confirmBtn.disabled = true;
        confirmBtn.style.opacity = '0.7';
        confirmBtn.style.cursor = 'not-allowed';
        confirmBtn.innerHTML = '<span class="loading-spinner-small"></span> Adding to cart...';
        
        // Determine which cart to add to based on selectedOrderType
        const orderType = pendingCartProduct.selectedOrderType || 'preorder';
        
        // Simulate async operation (add to cart)
        setTimeout(() => {
            let addSuccess = false;
            
            if (orderType === 'sameday') {
                // Add to same-day cart (availtoday_cart table)
                if (typeof addToAvailableTodayCart === 'function') {
                    addToAvailableTodayCart(pendingCartProduct.id, quantity, pendingCartProduct.button);
                    addSuccess = true;
                } else {
                    console.error('Same-day cart function not available');
                }
            } else {
                // Add to pre-order cart (cart table)
                // Use fetch to add to regular cart
                fetch('../cart/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${pendingCartProduct.id}&quantity=${quantity}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addSuccess = true;
                        
                        // Show success state
                        confirmBtn.innerHTML = '<span class="success-icon">✓</span> Product added!';
                        confirmBtn.style.backgroundColor = '#4CAF50';
                        
                        // Show confirmation message
                        showConfirmation(`Added ${quantity} ${pendingCartProduct.name} to cart`, false);
                        
                        // Close modal after short delay
                        setTimeout(() => {
                            closeQuantityModal();
                            
                            // Reset button state
                            confirmBtn.disabled = false;
                            confirmBtn.style.opacity = '1';
                            confirmBtn.style.cursor = 'pointer';
                            confirmBtn.textContent = originalText;
                            confirmBtn.style.backgroundColor = '';
                        }, 800);
                    } else {
                        console.error('Failed to add to cart:', data.message);
                        showConfirmation(data.message || 'Failed to add to cart', true);
                        
                        // Reset button on error
                        confirmBtn.disabled = false;
                        confirmBtn.style.opacity = '1';
                        confirmBtn.style.cursor = 'pointer';
                        confirmBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                    showConfirmation('Error adding to cart', true);
                    
                    // Reset button on error
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = '1';
                    confirmBtn.style.cursor = 'pointer';
                    confirmBtn.textContent = originalText;
                });
                return; // Exit early for async fetch
            }
            
            // For same-day cart (synchronous)
            if (addSuccess) {
                // Show success state
                confirmBtn.innerHTML = '<span class="success-icon">✓</span> Product added!';
                confirmBtn.style.backgroundColor = '#4CAF50';
                
                // Show confirmation message
                showConfirmation(`Added ${quantity} ${pendingCartProduct.name} to cart`, false);
                
                // Close modal after short delay
                setTimeout(() => {
                    closeQuantityModal();
                    
                    // Reset button state
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = '1';
                    confirmBtn.style.cursor = 'pointer';
                    confirmBtn.textContent = originalText;
                    confirmBtn.style.backgroundColor = '';
                }, 800);
            } else {
                // Reset button on error
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor = 'pointer';
                confirmBtn.textContent = originalText;
            }
        }, 500); // Small delay to show loading state
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const quantityModal = document.getElementById('quantityModal');
        if (event.target === quantityModal) {
            closeQuantityModal();
        }
    });
</script>

<style>
    input[type="number"] {
        -moz-appearance: textfield;
    }
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    /* Loading spinner for add to cart button */
    .loading-spinner-small {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Success icon */
    .success-icon {
        display: inline-block;
        margin-right: 6px;
        font-size: 16px;
        font-weight: bold;
    }
    
    /* Button disabled state */
    .btn-confirm:disabled {
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }
    
    /* Cart Truncated Notification Styles */
    .cart-truncated-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        max-width: 350px;
        animation: slideInRight 0.5s ease-out;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }
    
    .notification-content span {
        font-size: 14px;
        font-weight: 500;
        line-height: 1.4;
    }
    
    .close-notification-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s ease;
    }
    
    .close-notification-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @media (max-width: 768px) {
        .cart-truncated-notification {
            top: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
</style>
            
        </div>
        <div id="footer-container">
    <?php require_once "../../user-includes/user-footer.php"; ?>
</div>



    </div>
</div>

</body>
</html>
