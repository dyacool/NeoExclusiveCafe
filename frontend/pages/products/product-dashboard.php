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

// Function to truncate cart_availtoday when business hours are closed OR items are from previous days
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

// Check and truncate cart if business is closed
$cart_truncated = truncateCartIfBusinessClosed();

// Debug: Log the result
error_log("Cart truncation result: " . ($cart_truncated ? 'SUCCESS' : 'No action needed'));

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
<div class="wrapper">
    <div id="confirmationPopup" class="confirmation-popup"></div>

    <?php if (isset($_SESSION['cart_truncated_notification']) && $_SESSION['cart_truncated_notification']): ?>
        <div class="cart-truncated-notification" id="cartTruncatedNotification">
            <div class="notification-content">
                <span>🕐 Business hours closed. Cart has been cleared for the day.</span>
                <button onclick="closeCartTruncatedNotification()" class="close-notification-btn">×</button>
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
        <h1 class="prdct-title">Same Day Orders</h1>
        <div class="header-section">
            <h2 class="prdct-subtitle" id="currentDate">Check again tomorrow for exciting pre-made breads!<br><span style="color:rgb(18, 110, 41); font-weight: 600;">Loading...</span></h2>
            <div class="cart-dropdown" style="display: none;">
                <button class="cart-btn" id="availableTodayCartBtn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="m1 1 4 4 6.5 13h9"></path>
                        <path d="m7 13 10-10-1.5-1.5L5.5 11.5"></path>
                    </svg>
                    <span class="cart-count" id="availableTodayCartCount">0</span>
                </button>
                <div class="cart-dropdown-content" id="availableTodayCartContent">
                    <div class="cart-header">
                        <h3>Available Today Cart</h3>
                    </div>
                    <div class="availToday_timer" id="availToday_timer">
                        <span class="timer-label">Order before:</span>
                        <span class="timer-value" id="availTodayTimerValue">Loading...</span>
                    </div>
                    <div class="cart-items" id="availableTodayCartItems">
                        <p class="empty-cart">No items in cart</p>
                    </div>
                    <div class="cart-footer">
                        <div class="cart-total" id="availableTodayCartTotal">Total: ₱0.00</div>
                        <button class="checkout-btn" id="availableTodayCheckoutBtn" disabled>Checkout</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-container">
            <div class="products-grid" id="productScroll" style="display: none;">
                <?php
                    // Get today's date
                    $today_date = date('Y-m-d'); // Returns date in YYYY-MM-DD format
                    
                    // Query for products with availtoday_status that are available on today's date
                    // This includes both "Today's Products" (status_id = 3) and regular products with today availability
                    $sql = "SELECT 
                                p.id, p.name, p.price, p.description, p.status_id, p.is_featured,
                                ps.name AS status_name, pi.image_url, p.quantity, p.show_when_unavailable,
                                p.availtoday_status_id, ats.name AS availtoday_status_name,
                                GROUP_CONCAT(DISTINCT tpd.available_date ORDER BY tpd.available_date SEPARATOR ', ') as todays_product_dates,
                                GROUP_CONCAT(DISTINCT rptd.available_date ORDER BY rptd.available_date SEPARATOR ', ') as regular_today_dates
                            FROM products p
                            LEFT JOIN product_statuses ps ON p.status_id = ps.id
                            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
                            LEFT JOIN todays_products_dates tpd ON p.id = tpd.product_id AND tpd.available_date = ?
                            LEFT JOIN regular_products_today_dates rptd ON p.id = rptd.product_id AND rptd.available_date = ?
                            WHERE p.deleted_at IS NULL AND p.id > 0 
                            AND p.quantity > 0
                            AND p.availtoday_status_id IS NOT NULL
                            AND (tpd.available_date = ? OR rptd.available_date = ?)
                            GROUP BY p.id, p.name, p.price, p.description, p.status_id, p.is_featured, ps.name, pi.image_url, p.quantity, p.show_when_unavailable, p.availtoday_status_id, ats.name
                            ORDER BY p.is_featured DESC, p.name ASC";
                    
                    // Prepare and execute the statement with today's date parameter (4 times)
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $today_date, $today_date, $today_date, $today_date);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
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
                                'images' => $images,
                                'is_featured' => (bool)$row['is_featured'],
                                'quantity' => $row['quantity'],
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
                            $available_dates = $row['status_id'] == 3 ? $row['todays_product_dates'] : $row['regular_today_dates'];
                            
                            echo "<div class='product-card {$featuredClass}' data-status='" . htmlspecialchars($row['status_name']) . "' 
                                  data-available-dates='" . htmlspecialchars($available_dates ?? '') . "'
                                  data-product='" . $productDataJson . "' onclick='openProductModalFromData(this)'>
                                    <div class='product-image'>
                                        <img src='../../../assets/" . htmlspecialchars($row['image_url'] ?: 'images/no-image.jpg') . "' alt='" . htmlspecialchars($row['name']) . "'>
                                    </div>
                                    <div class='product-info'>
                                        <h3>" . htmlspecialchars($row['name']) . "</h3>";
                            
                            // Display availtoday status badge if available
                            if (!empty($row['availtoday_status_name'])) {
                                echo "<span class='availtoday-badge'>" . htmlspecialchars($row['availtoday_status_name']) . "</span>";
                            }
                            
                            echo "<p class='price'>₱" . number_format($row['price'], 2) . "</p>
                                  
                                    <div class='prdct-availability'>
                                        <span class='status-badge status-{$statusClass}'>" . htmlspecialchars($row['status_name']) . "</span>
                                        <p class='stock'>Stock: " . $row['quantity'] . "</p>";
                            
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
                                echo "<p class='available-dates'>Available: " . htmlspecialchars(implode(', ', $formatted_dates)) . "</p>";
                            }
                            
                            echo "</div>
                                  
                                    <div class='quantity-controls'>
                                        <button type='button' onclick='event.stopPropagation(); updateQuantity(this, -1)'>-</button>
                                        <input type='number' value='1' min='1' max='" . $row['quantity'] . "' onclick='event.stopPropagation()' onchange='validateQuantity(this)'>
                                        <button type='button' onclick='event.stopPropagation(); updateQuantity(this, 1)'>+</button>
                                    </div>
                                  
                                    <button class='add-to-cart' onclick='event.stopPropagation(); addToCart(" . $row['id'] . ", this)'>Add to Cart</button>
                                </div>
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
                <div class="quantity-controls modal-quantity">
                    <button type="button" onclick="updateModalQuantity(-1)">-</button>
                    <input type="number" id="modalQuantity" value="1" min="1" onchange="validateModalQuantity()">
                    <button type="button" onclick="updateModalQuantity(1)">+</button>
                </div>
                <button class="add-to-cart" id="modalAddToCart">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Confirmation Modal -->
<div id="checkoutConfirmModal" class="modal" style="display: none;">
    <div class="modal-content fade-in-pop">
        <span class="close" onclick="closeCheckoutConfirmModal()">&times;</span>
        <div class="modal-body">
            <h2 style="color: #2d5016; margin-bottom: 1rem;">Confirm Checkout</h2>
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <p style="margin: 0.5rem 0; font-size: 1.1rem;"><strong>Items:</strong> <span id="confirmItemCount">0</span></p>
                <p style="margin: 0.5rem 0; font-size: 1.2rem; color: #2d5016;"><strong>Total:</strong> ₱<span id="confirmTotal">0.00</span></p>
            </div>
            <p style="margin-bottom: 1.5rem; color: #666;">Are you ready to proceed to checkout?</p>
            <div class="modal-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="cancel-btn" onclick="closeCheckoutConfirmModal();" style="padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: white; color: #666; border-radius: 4px; cursor: pointer; font-size: 1rem;">Cancel</button>
                <button type="button" id="proceedCheckoutBtn" class="confirm-btn" style="padding: 0.75rem 1.5rem; border: none; background: #2d5016; color: white; border-radius: 4px; cursor: pointer; font-size: 1rem;">Proceed to Checkout</button>
            </div>
        </div>
    </div>
</div>
                        </div>
            
<!-- Available Today Cart JavaScript -->
<script src="availtoday-cart.js"></script>

<script>
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
                }
            })
            .catch(error => console.error('Error loading business hours:', error));
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
        const title = document.querySelector('.prdct-title');
        const subtitle = document.querySelector('.prdct-subtitle');
        const cartDropdown = document.querySelector('.cart-dropdown');
        
        if (!isOpen) {
            productsGrid.style.display = 'none';
            title.textContent = 'Same Day Orders';
            subtitle.innerHTML = `Check again tomorrow for exciting pre-made breads!<br><span style="color:rgb(18, 110, 41); font-weight: 600;">${formatTimeForDisplay(businessHours.openingTime)} - ${formatTimeForDisplay(businessHours.closingTime)}</span>`;
            
            if (cartDropdown) {
                cartDropdown.style.display = 'none';
                if (typeof clearAvailableTodayCart === 'function') {
                    clearAvailableTodayCart();
                }
            }
        } else {
            productsGrid.style.display = 'grid';
            title.textContent = 'Same Day Order Products';
            subtitle.textContent = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            if (cartDropdown) {
                cartDropdown.style.display = 'block';
                if (typeof updateAvailableTodayCartDisplay === 'function') {
                    updateAvailableTodayCartDisplay();
                }
            }
        }
    }

    function updateTimerDisplay() {
        const timerValue = document.getElementById('availTodayTimerValue');
        if (timerValue) {
            // Format the time to be more readable (e.g., "5:00 PM" instead of "17:00")
            const formattedTime = formatTimeForDisplay(businessHours.closingTime);
            timerValue.textContent = formattedTime;
        }
        
        // Also update the subtitle with business hours if they're loaded
        const subtitle = document.querySelector('.prdct-subtitle');
        if (subtitle && businessHours.openingTime && businessHours.closingTime) {
            // Only update if still showing the "Check again tomorrow" message
            if (subtitle.innerHTML.includes('Check again tomorrow') || subtitle.innerHTML.includes('Loading...')) {
                subtitle.innerHTML = `Check again tomorrow for exciting pre-made breads!<br><span style="color:rgb(18, 110, 41); font-weight: 600;">${formatTimeForDisplay(businessHours.openingTime)} - ${formatTimeForDisplay(businessHours.closingTime)}</span>`;
            }
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

    function updateModalQuantity(change) {
        const input = document.getElementById('modalQuantity');
        const max = parseInt(input.max);
        const newValue = parseInt(input.value) + change;
        if (newValue >= 1 && newValue <= max) {
            input.value = newValue;
        }
    }

    function validateModalQuantity() {
        const input = document.getElementById('modalQuantity');
        const value = parseInt(input.value);
        const max = parseInt(input.max);
        
        if (isNaN(value) || value < 1) {
            input.value = 1;
        } else if (value > max) {
            input.value = max;
        }
    }

    function showConfirmation(message, isError = false) {
        const popup = document.getElementById('confirmationPopup');
        popup.textContent = message;
        popup.className = 'confirmation-popup' + (isError ? ' error' : '');
        popup.classList.add('show');
        
        setTimeout(() => {
            popup.classList.remove('show');
            popup.classList.add('hide');
            setTimeout(() => {
                popup.classList.remove('hide');
            }, 300);
        }, 3000);
    }

    function addToCart(productId, button, quantity = null) {
        console.log('addToCart called with:', { productId, button, quantity });
        
        let finalQuantity;
        
        if (button) {
            // Called from product card button
            const productCard = button.closest('.product-card');
            if (!productCard) {
                showConfirmation("Error: Product not found", true);
                return;
            }
            
            const quantityInput = button.parentElement.querySelector('input');
            finalQuantity = quantity || (quantityInput ? parseInt(quantityInput.value) : 1);
        } else {
            // Called from modal
            finalQuantity = quantity || 1;
        }
        
        // All products on product-dashboard.php are pre-filtered to be Available Today
        // No need to validate - they all have availtoday_status_id and valid dates
        
        console.log('Final quantity:', finalQuantity);

        // Use Available Today cart API instead of main cart API
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', finalQuantity);
        
        const apiUrl = "availtoday-cart-api.php";
        console.log('[DEBUG] Fetching API:', apiUrl);
        console.log('[DEBUG] Current location:', window.location.href);
        console.log('[DEBUG] FormData contents:', {
            action: 'add',
            product_id: productId,
            quantity: finalQuantity
        });
        
        fetch(apiUrl, {
          method: "POST",
          body: formData,
          credentials: "include"
        })
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
          })
          .then(text => {
            console.log('[DEBUG] API Response:', text);
            try {
              return JSON.parse(text);
            } catch (e) {
              console.error('[DEBUG] Failed to parse JSON. Response was:', text);
              throw new Error('Invalid JSON response from server');
            }
          })
          .then(data => {
            if (data && data.success) {
                console.log('Product added to Available Today cart successfully');
                showConfirmation(`${data.product_name || 'Product'} added to Available Today cart!`);
                
                // Update local cart immediately for responsive UI
                if (button && typeof updateLocalCart === 'function') {
                    const productCard = button.closest('.product-card');
                    if (productCard) {
                        updateLocalCart(productId, finalQuantity, productCard);
                        if (typeof updateAvailableTodayCartDisplay === 'function') {
                            updateAvailableTodayCartDisplay();
                        }
                    }
                }
                
                // Sync with server to ensure consistency (works for both modal and card cases)
                if (typeof syncWithServer === 'function') {
                    syncWithServer();
                }
                
                if (productModalOpen) closeProductModal();
            } else if (data) {
                console.log('Error in Available Today cart response:', data.error);
                showConfirmation("Error: " + (data.error || "Unknown error"), true);
            }
          })
        .catch(error => {
            console.error('[DEBUG] API Fetch Error:', error);
            console.error("Error message:", error.message);
            // Don't show error message if it's a redirect (user will be redirected to login)
            if (!error.message.includes('redirect')) {
                showConfirmation("An error occurred while adding to cart", true);
            }
        });
    }

    function openProductModalFromData(cardElement) {
        try {
            const productData = cardElement.getAttribute('data-product');
            if (!productData) {
                console.error('No product data found');
                return;
            }

            const product = JSON.parse(productData);
            openProductModal(product);
        } catch (error) {
            console.error('Error parsing product data:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function openProductModal(product) {
        try {
            if (!product || typeof product !== 'object') {
                console.error('Invalid product data:', product);
                return;
            }

            productModalOpen = true;
            const modal = document.getElementById('productModal');
            const mainImage = document.getElementById('modalMainImage');
            const thumbnails = document.getElementById('thumbnailContainer');
            const productName = document.getElementById('modalProductName');
            const productPrice = document.getElementById('modalProductPrice');
            const productStatus = document.getElementById('modalProductStatus');
            const productDescription = document.getElementById('modalProductDescription');
            const productStock = document.getElementById('modalProductStock');
            const productAvailableDays = document.getElementById('modalProductAvailableDays');
            const quantityInput = document.getElementById('modalQuantity');
            const addToCartBtn = document.getElementById('modalAddToCart');

            // Set main content
            productName.textContent = product.name || 'Unknown Product';
            productPrice.textContent = '₱' + (parseFloat(product.price) || 0).toFixed(2);
            productStatus.textContent = product.status || 'Available Today';
            productStatus.className = 'status-badge status-' + (product.status || '').toLowerCase().replace(' ', '-');
            productDescription.textContent = product.description || 'No description available';
            productStock.textContent = 'Stock: ' + (product.quantity || 0);

            // Handle available dates in modal
            const availableDates = product.status_id == 3 ? product.todays_product_dates : product.regular_today_dates;
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

            // Set quantity input max value
            quantityInput.max = product.quantity || 0;
            quantityInput.value = 1;

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

            // Set up Add to Cart button
            addToCartBtn.disabled = false;
            addToCartBtn.textContent = 'Add to Cart';
            addToCartBtn.classList.remove('unavailable');
            quantityInput.disabled = false;
            addToCartBtn.onclick = () => {
                if (product.id) {
                    addToCart(product.id, null, parseInt(quantityInput.value));
                }
            };

            modal.style.display = 'block';
        } catch (error) {
            console.error('Error in openProductModal:', error);
            showConfirmation('An error occurred while opening the product details', true);
        }
    }

    function closeProductModal() {
        productModalOpen = false;
        document.getElementById('productModal').style.display = 'none';
    }

    // Close modal when clicking outside
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

        <div class="footer">
            <a href="about-page.php">About Us</a>
            <a href="terms.php">Terms and Conditions</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </div>
</div>