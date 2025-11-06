<?php
// Include database first - it handles session configuration
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";

$page_title = "Products";
$additional_css = [
    "/frontend/pages/products/product-dashboard.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../user-includes/preview-mode.php";
require_once __DIR__ . "/../../../backend/pages/products/todays-products-handler.php";

// Try to include Cloudinary image fetcher (may fail if vendor/autoload.php is missing)
try {
    require_once __DIR__ . "/../../../backend/includes/cloudinary-image-fetcher.php";
} catch (Exception $e) {
    error_log("Failed to load cloudinary-image-fetcher.php: " . $e->getMessage());
} catch (Error $e) {
    error_log("Fatal error loading cloudinary-image-fetcher.php: " . $e->getMessage());
}

// Clean up past dates automatically when page loads
try {
    cleanupPastDates();
    // Also clean up today's dates if business hours have closed
    cleanupTodaysDatesAfterBusinessHours();
} catch (Exception $e) {
    error_log("Error cleaning up dates: " . $e->getMessage());
}

// DISABLED: Function to truncate cart_availtoday when business hours are closed OR items are from previous days
// This functionality is commented out for now
/*
function truncateCartIfBusinessClosed() {
    global $conn;
    
    try {
        $truncated = false;
        
        // STEP 1: Remove old date assignments from products (date-based cleanup)
        // Remove dates from previous days so products are no longer marked for same-day delivery
        
        // Remove old dates from Today's products table (but preserve dates with SDO quantities)
        $old_todays_dates = "DELETE tpd FROM todays_products_dates tpd 
                             LEFT JOIN quantity_per_day_sdo qpd ON tpd.product_id = qpd.product_id AND qpd.date = tpd.available_date
                             WHERE tpd.available_date < CURDATE() AND qpd.id IS NULL";
        $result1 = $conn->query($old_todays_dates);
        $removed_todays = ($result1 && $conn->affected_rows > 0) ? $conn->affected_rows : 0;
        
        // Remove old dates from regular products' today dates table (but preserve dates with SDO quantities)
        $old_regular_dates = "DELETE rptd FROM regular_products_today_dates rptd 
                              LEFT JOIN quantity_per_day_sdo qpd ON rptd.product_id = qpd.product_id AND qpd.date = rptd.available_date
                              WHERE rptd.available_date < CURDATE() AND qpd.id IS NULL";
        $result2 = $conn->query($old_regular_dates);
        $removed_regular = ($result2 && $conn->affected_rows > 0) ? $conn->affected_rows : 0;
        
        $total_removed = $removed_todays + $removed_regular;
        if ($total_removed > 0) {
            error_log("Auto-cleanup (product-dashboard): Removed $removed_todays old dates from todays_products_dates, $removed_regular from regular_products_today_dates");
            $truncated = true;
        }
        
        // STEP 2: Remove today's dates if business hours have closed
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Get business hours
        $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
        $business_hours_result = $conn->query($business_hours_query);
        
        if ($business_hours_result && $business_hours_result->num_rows > 0) {
            $business_hours = $business_hours_result->fetch_assoc();
            $closing_time = $business_hours['closing_time'];
            
            // Convert times to minutes for comparison
            $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
            $closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));
            
            // Check if business is closed
            $is_closed = false;
            
            // Handle midnight crossing
            if ($closing_minutes > 1200 && $current_minutes < 600) {
                $is_closed = true;
            } else if ($current_minutes > $closing_minutes) {
                $is_closed = true;
            }
            
            if ($is_closed) {
                // Business is closed, remove today's dates (but preserve dates with SDO quantities)
                $remove_today_todays = "DELETE tpd FROM todays_products_dates tpd 
                                        LEFT JOIN quantity_per_day_sdo qpd ON tpd.product_id = qpd.product_id AND qpd.date = tpd.available_date
                                        WHERE tpd.available_date = ? AND qpd.id IS NULL";
                $stmt1 = $conn->prepare($remove_today_todays);
                $stmt1->bind_param("s", $today);
                $stmt1->execute();
                $removed_today_todays = $stmt1->affected_rows;
                $stmt1->close();
                
                $remove_today_regular = "DELETE rptd FROM regular_products_today_dates rptd 
                                         LEFT JOIN quantity_per_day_sdo qpd ON rptd.product_id = qpd.product_id AND qpd.date = rptd.available_date
                                         WHERE rptd.available_date = ? AND qpd.id IS NULL";
                $stmt2 = $conn->prepare($remove_today_regular);
                $stmt2->bind_param("s", $today);
                $stmt2->execute();
                $removed_today_regular = $stmt2->affected_rows;
                $stmt2->close();
                
                $total_removed_today = $removed_today_todays + $removed_today_regular;
                if ($total_removed_today > 0) {
                    error_log("Business hours closed - Removed today's dates: $removed_today_todays from todays_products_dates, $removed_today_regular from regular_products_today_dates");
                    $truncated = true;
                }
            }
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

    <?php if (false): // Disabled cart truncation notification ?>
        <div class="cart-truncated-notification" id="cartTruncatedNotification">
            <div class="notification-content">
                <span>ðŸ• Business hours closed. Cart has been cleared for the day.</span>
                <button onclick="closeCartTruncatedNotification()" class="close-notification-btn">Ã—</button>
            </div>
        </div>
        <?php unset($_SESSION['cart_truncated_notification']); ?>
    <?php endif; ?>

    <!-- Debug: Manual test button for cart truncation -->
    <?php if (false): // Disabled debug cart truncation button ?>
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
        

        
        <div class="scroll-container" id="scrollContainer" style="display: block;">
            <div class="products-grid" id="productScroll">
                <?php
                    // Get today's date
                    $today_date = date('Y-m-d'); // Returns date in YYYY-MM-DD format
                    
                    /**
                     * Determine product availability based on stock levels, date availability, and visibility flags
                     * 
                     * @param array $product_row Product data from database query
                     * @param string $today_date Current date in Y-m-d format
                     * @return array Availability information with keys: has_preorder, has_sameday, is_unavailable, unavailable_reason, should_display
                     */
                    function determineProductAvailability($product_row, $today_date) {
                        // Initialize result structure with default values
                        // All products start with no capabilities and are assumed available for display
                        $result = [
                            'has_preorder' => false,      // Can customer pre-order this product?
                            'has_sameday' => false,       // Can customer order for same-day delivery?
                            'is_unavailable' => false,    // Is product completely unavailable?
                            'unavailable_reason' => '',   // Why is it unavailable (if applicable)?
                            'should_display' => true      // Should we show this product on the page?
                        ];
                        
                        // Extract and normalize product data from database row
                        // Use null coalescing operator (??) to provide safe defaults for missing values
                        $status_id = $product_row['status_id'];                                      // Product status: 1=Regular, 2=Featured, 3=Limited, 4=Same-Day Only
                        $preorder_stock = $product_row['quantity'] ?? 0;                            // Stock available for pre-orders
                        $sameday_stock = $product_row['sameday_stock_today'] ?? 0;                  // Stock available for same-day orders today
                        $has_availtoday_config = !empty($product_row['availtoday_status_id']);      // Does product have same-day configuration?
                        $todays_dates = $product_row['todays_product_dates'] ? explode(', ', $product_row['todays_product_dates']) : [];  // Dates for status 4 products
                        $regular_dates = $product_row['regular_today_dates'] ? explode(', ', $product_row['regular_today_dates']) : [];   // Dates for status 1/2/3 products with same-day
                        $show_when_unavailable = (bool)($product_row['show_when_unavailable'] ?? 0); // Force show even when unavailable?
                        $hide_when_unavailable = (bool)($product_row['hide_when_unavailable'] ?? 0); // Force hide when unavailable?
                        
                        // ============================================================================
                        // STEP 1: Determine product capabilities
                        // ============================================================================
                        // This section determines WHAT the product CAN do (pre-order, same-day, or both)
                        // We check capabilities independently before determining overall availability
                        
                        // Pre-order capability check:
                        // Products with status 1/2/3 can be pre-ordered if they have stock
                        // Status 4 products are same-day only and cannot be pre-ordered
                        $result['has_preorder'] = in_array($status_id, [1, 2, 3]) && $preorder_stock > 0;
                        
                        // Same-day capability check for pure same-day products (status 4):
                        // These products ONLY support same-day ordering
                        // Requirements: stock available AND today's date must be in the configured dates list
                        if ($status_id == 4) {
                            $has_valid_date = in_array($today_date, $todays_dates);  // Is today in the todays_products_dates table?
                            $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
                        }
                        
                        // Same-day capability check for dual-capability products (status 1/2/3 with availtoday config):
                        // These products support BOTH pre-order AND same-day ordering
                        // Requirements: has availtoday_status_id set, stock available, AND today's date in regular_products_today_dates
                        if (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
                            $has_valid_date = in_array($today_date, $regular_dates);  // Is today in the regular_products_today_dates table?
                            $result['has_sameday'] = ($sameday_stock > 0) && $has_valid_date;
                        }
                        
                        // ============================================================================
                        // STEP 2: Determine availability
                        // ============================================================================
                        // Now that we know the product's capabilities, determine if it's available
                        // A product is unavailable ONLY if it has NO capabilities at all
                        
                        // Unavailability logic:
                        // If product has neither pre-order nor same-day capability, it's unavailable
                        $result['is_unavailable'] = !$result['has_preorder'] && !$result['has_sameday'];
                        
                        if ($result['is_unavailable']) {
                            // Determine the specific reason for unavailability based on product type
                            // This helps customers understand WHY they can't order the product
                            
                            if ($status_id == 4) {
                                // Same-day only product (status 4) unavailability reasons:
                                if ($sameday_stock <= 0) {
                                    $result['unavailable_reason'] = 'Out of Stock';        // No stock available
                                } else {
                                    $result['unavailable_reason'] = 'Not Available Today'; // Has stock but not configured for today
                                }
                            } elseif (in_array($status_id, [1, 2, 3]) && $has_availtoday_config) {
                                // Dual-capability product (status 1/2/3 with same-day config) unavailability:
                                // If we reach here, both pre-order and same-day stock are depleted
                                $result['unavailable_reason'] = 'Out of Stock';
                            } else {
                                // Pre-order only product (status 1/2/3 without same-day config) unavailability:
                                // No pre-order stock available
                                $result['unavailable_reason'] = 'Out of Stock';
                            }
                        }
                        
                        // ============================================================================
                        // STEP 3: Apply visibility rules
                        // ============================================================================
                        // Determine if the product should be displayed on the page
                        // Visibility rules only apply to unavailable products
                        
                        if ($result['is_unavailable']) {
                            // Visibility priority hierarchy:
                            // 1. hide_when_unavailable flag (highest priority - always hide)
                            // 2. show_when_unavailable flag (medium priority - always show)
                            // 3. Default behavior (lowest priority - hide)
                            
                            if ($hide_when_unavailable) {
                                // Explicit hide flag set - never show this product when unavailable
                                $result['should_display'] = false;
                            } elseif ($show_when_unavailable) {
                                // Explicit show flag set - always show this product even when unavailable
                                $result['should_display'] = true;
                            } else {
                                // Default behavior: hide unavailable products to reduce clutter
                                $result['should_display'] = false;
                            }
                            
                            // Log visibility decisions for debugging and troubleshooting
                            // Only log when products are hidden to avoid log spam
                            if (!$result['should_display']) {
                                error_log(sprintf(
                                    "[Product Visibility] Hidden - Product ID: %d, Name: %s, Reason: %s, hide_flag: %d, show_flag: %d",
                                    $product_row['id'],
                                    $product_row['name'],
                                    $result['unavailable_reason'],
                                    $hide_when_unavailable ? 1 : 0,
                                    $show_when_unavailable ? 1 : 0
                                ));
                            }
                        } else {
                            // Available products are always displayed regardless of visibility flags
                            // Visibility flags only control unavailable product display
                            $result['should_display'] = true;
                        }
                        
                        return $result;
                    }
                    
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
                                ps.name AS status_name, 
                                COALESCE(pi.cloud_url, pi.image_url) as image_url,
                                p.quantity, p.show_when_unavailable, p.hide_when_unavailable,
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
                    
                    $sql .= " GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, p.category_id, ps.name, pi.cloud_url, pi.image_url, p.quantity, p.show_when_unavailable, p.hide_when_unavailable, p.availtoday_status_id, ats.name, c.name, qpd.quantity
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
                    
                    // Determine availability and filter products
                    $products_to_display = [];
                    foreach ($all_products as $row) {
                        $availability = determineProductAvailability($row, $today_date);
                        
                        // Skip products that should not be displayed
                        if (!$availability['should_display']) {
                            continue;
                        }
                        
                        // Add availability info to product data
                        $row['is_unavailable'] = $availability['is_unavailable'];
                        $row['unavailable_reason'] = $availability['unavailable_reason'];
                        
                        $products_to_display[] = $row;
                    }
                    
                    // Custom sort: Priority hierarchy - Available Today > Featured > Regular > Unavailable
                    usort($products_to_display, function($a, $b) use ($today_date) {
                        // Use pre-calculated unavailability flags
                        $a_unavailable = $a['is_unavailable'] ?? false;
                        $b_unavailable = $b['is_unavailable'] ?? false;
                        
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

                    if (count($products_to_display) > 0) {
                        // Batch fetch images using CloudinaryImageFetcher for performance
                        $productIds = array_column($products_to_display, 'id');
                        $cloudinaryImages = [];
                        
                        try {
                            if (class_exists('CloudinaryImageFetcher')) {
                                $fetcher = new CloudinaryImageFetcher($conn);
                                
                                // Determine viewport size for responsive transformations
                                // Mobile: 400px, Desktop: 800px (default to desktop)
                                $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && 
                                           preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
                                $imageWidth = $isMobile ? 400 : 800;
                                
                                // Fetch all product images with responsive transformations
                                $cloudinaryImages = $fetcher->fetchMultipleProductImages(
                                    $productIds, 
                                    [
                                        'width' => $imageWidth,
                                        'quality' => 'auto',
                                        'fetch_format' => 'auto',
                                        'crop' => 'limit'
                                    ],
                                    true // Skip products without Cloudinary URLs
                                );
                            } else {
                                error_log("CloudinaryImageFetcher class not found in product-dashboard.php");
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching Cloudinary images: " . $e->getMessage());
                        } catch (Error $e) {
                            error_log("Fatal error fetching Cloudinary images: " . $e->getMessage());
                        }
                        
                        foreach ($products_to_display as $row) {
                            // Get all images for this product (prioritize Cloudinary URLs)
                            $images_sql = "SELECT COALESCE(cloud_url, image_url) as image_url FROM product_images WHERE product_id = ?";
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
                                'hide_when_unavailable' => (bool)($row['hide_when_unavailable'] ?? 0),
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
                            
                            // Use availability data already calculated by determineProductAvailability()
                            $is_unavailable = $row['is_unavailable'];
                            $unavailable_reason = $row['unavailable_reason'];
                            
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
                            
                            // Display badges: Show capabilities based on product configuration
                            if ($is_unavailable) {
                                // Unavailable badge (highest priority, exclusive)
                                echo "<div class='unavailable-badge-left'>" . htmlspecialchars($unavailable_reason) . "</div>";
                            } else {
                                // Determine product capabilities
                                $has_preorder = in_array($row['status_id'], [1, 2, 3]) && $row['quantity'] > 0;
                                $has_sameday = ($row['status_id'] == 4) || (!empty($row['availtoday_status_id']) && $is_available_today);
                                
                                // Show badges based on capabilities
                                if ($has_sameday && $has_preorder) {
                                    // Product has BOTH capabilities
                                    echo "<div class='today-badge-left'>Same Day & Pre-Order</div>";
                                } elseif ($has_sameday) {
                                    // Same-day only
                                    echo "<div class='today-badge-left'>Same Day Order</div>";
                                } elseif ($has_preorder) {
                                    // Pre-order only
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
                            
                            // Use CloudinaryImageFetcher result with fallback to placeholder
                            $image_path = '../../../assets/images/no-image.jpg'; // Default placeholder
                            $image_alt = htmlspecialchars($row['name']);
                            
                            if (isset($cloudinaryImages[$row['id']])) {
                                // Use Cloudinary URL from batch fetch
                                $image_path = htmlspecialchars($cloudinaryImages[$row['id']]['url']);
                            } elseif (!empty($row['image_url'])) {
                                // Fallback: Handle both Cloudinary URLs (full URLs) and local paths
                                $image_src = $row['image_url'];
                                if (strpos($image_src, 'http://') === 0 || strpos($image_src, 'https://') === 0) {
                                    // It's a full URL (Cloudinary)
                                    $image_path = htmlspecialchars($image_src);
                                } else {
                                    // It's a local path
                                    $image_path = '../../../assets/' . htmlspecialchars($image_src);
                                }
                            }
                            
                            // Add lazy loading and error handling
                            echo "    <img src='" . $image_path . "' 
                                           alt='" . $image_alt . "' 
                                           loading='lazy'
                                           onerror=\"this.onerror=null; this.src='../../../assets/images/no-image.jpg';\"
                                           class='product-dashboard-image'>
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
    const isLoggedIn = <?= SessionManager::isUserLoggedIn() ? 'true' : 'false' ?>;
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
        
            // Initialize page display
        initPageDisplay();
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

    // Initialize page display
    function initPageDisplay() {
        // Hide loading message
        const loadingMessage = document.getElementById('loadingMessage');
        if (loadingMessage) {
            loadingMessage.style.display = 'none';
        }
        
        // Show products grid and container
        const productsGrid = document.getElementById('productScroll');
        const scrollContainer = document.getElementById('scrollContainer');
        const headerSection = document.getElementById('headerSection');
        
        if (productsGrid) productsGrid.style.display = 'grid';
        if (scrollContainer) scrollContainer.style.display = 'block';
        if (headerSection) headerSection.style.display = 'block';
        
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

            // Set up images - handle both Cloudinary URLs and local paths
            if (product.images && Array.isArray(product.images) && product.images.length > 0) {
                // Helper function to get proper image path
                const getImagePath = (img) => {
                    if (img.startsWith('http://') || img.startsWith('https://')) {
                        return img; // It's a full URL (Cloudinary)
                    }
                    return '../../../assets/' + img; // It's a local path
                };
                
                mainImage.src = getImagePath(product.images[0]);
                thumbnails.innerHTML = '';
                product.images.forEach((image, index) => {
                    if (image) {
                        const thumb = document.createElement('img');
                        thumb.src = getImagePath(image);
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
            const todayDate = new Date().toISOString().split('T')[0]; // Get today's date in Y-m-d format
            
            // Check stock based on product type
            let stockUnavailable = false;
            
            if (product.status_id == 4) {
                // Status 4: Same Day ONLY product
                stockUnavailable = (samedayStock == 0 || samedayStock === null);
            } else if ([1, 2, 3].includes(product.status_id)) {
                if (hasAvailtoday) {
                    // DUAL capability: unavailable if BOTH stocks are 0
                    stockUnavailable = (preorderStock == 0 && (samedayStock == 0 || samedayStock === null));
                } else {
                    // Pre-order ONLY
                    stockUnavailable = (preorderStock == 0);
                }
            }
            
            // Check date availability
            let dateUnavailable = false;
            
            if (product.status_id == 4) {
                // Same-day ONLY: must have date in todays_product_dates
                const todaysDates = product.todays_product_dates || [];
                dateUnavailable = !todaysDates.includes(todayDate);
            }
            // Note: DUAL capability products (status 1/2/3 with availtoday) are not date-restricted for display
            
            // Determine overall unavailability
            isUnavailable = stockUnavailable || dateUnavailable;
            
            if (stockUnavailable) {
                unavailableReason = 'Out of Stock';
            } else if (dateUnavailable) {
                unavailableReason = 'Not Available Today';
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
    
    // Add loading state handling for images with performance optimizations
    document.addEventListener('DOMContentLoaded', function() {
        const productImages = document.querySelectorAll('.product-dashboard-image');
        
        // Use Intersection Observer for better lazy loading performance
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // Mark image as loaded when it finishes loading
                        if (img.complete) {
                            handleImageLoad(img);
                        } else {
                            img.addEventListener('load', function() {
                                handleImageLoad(this);
                            });
                            
                            // Handle error case
                            img.addEventListener('error', function() {
                                handleImageLoad(this);
                            });
                        }
                        
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px' // Start loading images 50px before they enter viewport
            });
            
            productImages.forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without Intersection Observer
            productImages.forEach(img => {
                if (img.complete) {
                    handleImageLoad(img);
                } else {
                    img.addEventListener('load', function() {
                        handleImageLoad(this);
                    });
                    img.addEventListener('error', function() {
                        handleImageLoad(this);
                    });
                }
            });
        }
        
        function handleImageLoad(img) {
            img.classList.add('loaded');
            const productImageContainer = img.closest('.product-image');
            if (productImageContainer) {
                productImageContainer.classList.add('image-loaded');
            }
        }
    });

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
        
        // Show modal first
        modal.style.display = 'flex';
        
        // Determine which order types to show
        // Status 1, 2, 3 = Pre-order products
        // Status 4 = Same Day Order products
        // If product has both status (1,2,3) AND availtoday_status_id, show both options
        
        if ((statusId == 1 || statusId == 2 || statusId == 3) && availtodayStatusId) {
            // Product has BOTH pre-order and same day order
            // Always show both options, even if one has 0 stock
            orderTypeSelector.style.display = 'block';
            
            // Show loading state
            setQuantityModalLoading(true);
            
            // Fetch pre-order quantity (default selection)
            document.getElementById('quantityModalStock').textContent = 'Loading...';
            quantityInput.value = 1;
            quantityInput.max = 1;
            
            await fetchPreOrderQuantity(pendingCartProduct.id);
            selectOrderType('preorder'); // Default to pre-order
            
            // Remove loading state
            setQuantityModalLoading(false);
        } else if (statusId == 4) {
            // Same Day Order ONLY - Fetch today's quantity from quantity_per_day_sdo
            orderTypeSelector.style.display = 'none';
            dateDisplay.style.display = 'block';
            dateDisplay.textContent = 'For: Today';
            pendingCartProduct.selectedOrderType = 'sameday';
            
            // Show loading state
            setQuantityModalLoading(true);
            
            // Fetch today's quantity from quantity_per_day_sdo table
            document.getElementById('quantityModalStock').textContent = 'Loading...';
            quantityInput.value = 1;
            quantityInput.max = 1;
            
            await fetchTodayQuantity(pendingCartProduct.id);
            
            // Remove loading state
            setQuantityModalLoading(false);
        } else {
            // Pre-order ONLY (status 1, 2, or 3 without availtoday_status_id)
            orderTypeSelector.style.display = 'none';
            dateDisplay.style.display = 'none';
            pendingCartProduct.selectedOrderType = 'preorder';
            
            // Show loading state
            setQuantityModalLoading(true);
            
            // Fetch pre-order quantity from products.quantity
            document.getElementById('quantityModalStock').textContent = 'Loading...';
            quantityInput.value = 1;
            quantityInput.max = 1;
            
            await fetchPreOrderQuantity(pendingCartProduct.id);
            
            // Remove loading state
            setQuantityModalLoading(false);
        }
    }
    
    // Set loading state for quantity modal
    function setQuantityModalLoading(isLoading) {
        const modal = document.getElementById('quantityModal');
        const modalContent = modal.querySelector('.modal-content');
        const quantityInput = document.getElementById('quantityModalInput');
        const buttons = modal.querySelectorAll('button');
        
        if (isLoading) {
            // Add loading overlay
            if (!modal.querySelector('.modal-loading-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'modal-loading-overlay';
                overlay.innerHTML = '<div class="loading-spinner"></div>';
                overlay.style.cssText = 'position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; z-index: 10; border-radius: 8px;';
                modalContent.style.position = 'relative';
                modalContent.appendChild(overlay);
            }
            
            // Disable inputs and buttons
            quantityInput.disabled = true;
            buttons.forEach(btn => btn.disabled = true);
        } else {
            // Remove loading overlay
            const overlay = modal.querySelector('.modal-loading-overlay');
            if (overlay) {
                overlay.remove();
            }
            
            // Enable inputs and buttons
            quantityInput.disabled = false;
            buttons.forEach(btn => btn.disabled = false);
        }
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
            
            // Fetch today's specific quantity from quantity_per_day_sdo table
            if (pendingCartProduct) {
                setQuantityModalLoading(true);
                fetchTodayQuantity(pendingCartProduct.id).then(() => {
                    setQuantityModalLoading(false);
                });
            }
        } else {
            dateDisplay.style.display = 'none';
            
            // Fetch pre-order stock quantity from products.quantity
            if (pendingCartProduct) {
                setQuantityModalLoading(true);
                fetchPreOrderQuantity(pendingCartProduct.id).then(() => {
                    setQuantityModalLoading(false);
                });
            }
        }
        
        if (pendingCartProduct) {
            pendingCartProduct.selectedOrderType = type;
        }
    }

    // Fetch pre-order quantity from products.quantity
    async function fetchPreOrderQuantity(productId) {
        const stockDisplay = document.getElementById('quantityModalStock');
        const confirmBtn = document.querySelector('.btn-confirm');
        stockDisplay.textContent = 'Loading...';
        
        try {
            const response = await fetch(`get-preorder-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const preorderQuantity = data.quantity || 0;
                stockDisplay.textContent = `Stock: ${preorderQuantity}`;
                
                const quantityInput = document.getElementById('quantityModalInput');
                quantityInput.max = preorderQuantity;
                quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, Math.max(preorderQuantity, 1));
                
                // Update pendingCartProduct with fresh stock value
                if (pendingCartProduct) {
                    pendingCartProduct.stock = preorderQuantity;
                }
                
                // Disable/enable Add to Cart button based on stock
                if (preorderQuantity === 0) {
                    stockDisplay.textContent = 'Stock: 0 (Out of stock)';
                    confirmBtn.disabled = true;
                    quantityInput.disabled = true;
                } else {
                    confirmBtn.disabled = false;
                    quantityInput.disabled = false;
                }
            } else {
                console.error('Error fetching pre-order quantity:', data.error);
                stockDisplay.textContent = 'Stock: 0';
                confirmBtn.disabled = true;
                quantityInput.disabled = true;
            }
        } catch (error) {
            console.error('Error:', error);
            stockDisplay.textContent = 'Stock: 0';
            confirmBtn.disabled = true;
            quantityInput.disabled = true;
        }
    }

    // Fetch today's specific quantity for same day orders from quantity_per_day_sdo
    async function fetchTodayQuantity(productId) {
        const stockDisplay = document.getElementById('quantityModalStock');
        const confirmBtn = document.querySelector('.btn-confirm');
        stockDisplay.textContent = 'Loading...';
        
        try {
            const response = await fetch(`get-sdo-quantity.php?product_id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                const todayQuantity = data.quantity || 0;
                stockDisplay.textContent = `Stock: ${todayQuantity}`;
                
                const quantityInput = document.getElementById('quantityModalInput');
                quantityInput.max = todayQuantity;
                quantityInput.value = Math.min(parseInt(quantityInput.value) || 1, Math.max(todayQuantity, 1));
                
                // Disable/enable Add to Cart button based on stock
                if (todayQuantity === 0) {
                    stockDisplay.textContent = 'Stock: 0 (Not available today)';
                    confirmBtn.disabled = true;
                    quantityInput.disabled = true;
                } else {
                    confirmBtn.disabled = false;
                    quantityInput.disabled = false;
                }
            } else {
                console.error('Error fetching today quantity:', data.error);
                stockDisplay.textContent = 'Stock: 0';
                confirmBtn.disabled = true;
                quantityInput.disabled = true;
            }
        } catch (error) {
            console.error('Error:', error);
            stockDisplay.textContent = 'Stock: 0';
            confirmBtn.disabled = true;
            quantityInput.disabled = true;
        }
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
    :root {
        --green-50: #f0fdf4;
        --green-200: #bbf7d0;
        --green-800: #166534;
        --red-50: #fef2f2;
        --red-200: #fecaca;
        --red-800: #991b1b;
    }

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
    
    /* Confirmation Popup */
    .confirmation-popup {
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%) translateY(-100px);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        z-index: 10000;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        font-weight: 500;
        min-width: 280px;
        text-align: center;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .confirmation-popup.success {
        background-color: var(--green-50);
        color: var(--green-800);
        border: 1px solid var(--green-200);
    }

    .confirmation-popup.error {
        background-color: var(--red-50);
        color: var(--red-800);
        border: 1px solid var(--red-200);
    }

    .confirmation-popup.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .confirmation-popup.hide {
        opacity: 0;
        transform: translateX(-50%) translateY(-100px);
    }
    
    @media (max-width: 768px) {
        .cart-truncated-notification {
            top: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
    
    /* Product image loading and error handling */
    .product-dashboard-image {
        transition: opacity 0.3s ease-in-out;
        opacity: 0;
        min-height: 200px;
        object-fit: cover;
    }
    
    .product-dashboard-image[loading='lazy'] {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading-shimmer 1.5s infinite;
    }
    
    @keyframes loading-shimmer {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }
    
    /* Fade in effect when image loads */
    .product-dashboard-image.loaded {
        opacity: 1;
    }
    
    /* Loading overlay for product images */
    .product-image {
        position: relative;
    }
    
    .product-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading-shimmer 1.5s infinite;
        z-index: 1;
        pointer-events: none;
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
    }
    
    .product-image.image-loaded::before {
        opacity: 0;
    }
    
    /* Performance optimization: Use GPU acceleration for animations */
    .product-dashboard-image,
    .product-image::before {
        will-change: opacity;
        transform: translateZ(0);
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
