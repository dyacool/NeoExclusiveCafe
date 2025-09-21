<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

$page_title = "Products";
$additional_css = [
    "/frontend/pages/products/products-categories.css"
];

require_once __DIR__ . "/../../user-includes/navbar/customer-navigation.php";
require_once __DIR__ . "/../../user-includes/user-header.php";
require_once __DIR__ . "/../../user-includes/preview-mode.php";
require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";

// Function to get business hours
function getBusinessHours() {
    global $conn;
    
    try {
        // Get business hours
        $business_hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
        $business_hours_result = $conn->query($business_hours_query);
        
        if (!$business_hours_result) {
            error_log("Failed to get business hours: " . $conn->error);
            return ['08:00', '17:00']; // Default hours
        }
        
        if ($business_hours_result->num_rows === 0) {
            // No business hours set, use default
            return ['08:00', '17:00'];
        } else {
            $business_hours = $business_hours_result->fetch_assoc();
            return [$business_hours['opening_time'], $business_hours['closing_time']];
        }
    } catch (Exception $e) {
        error_log("Error getting business hours: " . $e->getMessage());
        return ['08:00', '17:00']; // Default hours
    }
}

// Get business hours for display
list($opening_time, $closing_time) = getBusinessHours();

// Format time for display (remove seconds if present)
function formatTime($time) {
    return date('g:i A', strtotime($time));
}
?>

<div class="wrapper">

    <!-- Products Header Section -->
    <div class="products-header">
        <div class="header-content">
            <h1 class="products-title">Products</h1>
            <p class="products-description">Browse our delicious selection of artisan breads and pastries. Choose from delivery, pickup, or explore our special offers.</p>
            <div class="business-hours">
                <span class="hours-label">Store Hours:</span>
                <span class="hours-time"><?php echo formatTime($opening_time) . ' to ' . formatTime($closing_time); ?></span>
            </div>
        </div>
    </div>

    <!-- Product Categories Grid -->
    <div class="categories-container">
        <div class="categories-grid">
            <a href="product-dashboard.php" class="category-card">
                <div class="category-content">
                    <h3 class="category-title">Same Day Order</h3>
                    <p class="category-description">Order fresh items for today's pickup or delivery</p>
                </div>
            </a>

            <a href="weekly-product.php" class="category-card">
                <div class="category-content">
                    <h3 class="category-title">For Delivery</h3>
                    <p class="category-description">Get fresh baked goods delivered straight to you</p>
                </div>
            </a>

            <a href="user-products.php" class="category-card">
                <div class="category-content">
                    <h3 class="category-title">For Pickup</h3>
                    <p class="category-description">Pre-order and pick up at your convenience</p>
                </div>
            </a>

            <a href="../bulk/bulk-form.php" class="category-card">
                <div class="category-content">
                    <h3 class="category-title">Bulk Order Form</h3>
                    <p class="category-description">Order large quantities for events or businesses</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../user-includes/user-footer.php"; ?>
