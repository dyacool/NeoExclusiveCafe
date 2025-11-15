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
            <?php
            // Fetch categories from database
            $category_query = "SELECT id, name, slug, description FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
            $category_result = mysqli_query($conn, $category_query);
            
            if ($category_result && mysqli_num_rows($category_result) > 0) {
                while ($category = mysqli_fetch_assoc($category_result)) {
                    $category_url = 'product-dashboard.php?category=' . urlencode($category['slug']);
                    $category_name = htmlspecialchars($category['name']);
                    $category_desc = htmlspecialchars($category['description'] ?: 'Browse our ' . strtolower($category['name']) . ' selection');
                    
                    echo '<a href="' . $category_url . '" class="category-card">';
                    echo '<div class="category-content">';
                    echo '<h3 class="category-title">' . $category_name . '</h3>';
                    echo '<p class="category-description">' . $category_desc . '</p>';
                    echo '</div>';
                    echo '</a>';
                }
            } else {
                // Fallback if no categories found
                echo '<div class="no-categories">';
                echo '<p>No categories available at the moment.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../user-includes/user-footer.php"; ?>
