<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);
session_start();

// Include database connection
require_once '../../../backend/pages/admin-includes/database.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../login/user/login-signup.php");
    exit();
}
require_once '../../user-includes/navbar/customer-navigation.php';
$user_id = $_SESSION['user_id'];

// Auto-truncate cart if business is closed
// This ensures cart is emptied even without cron job
function checkAndTruncateCart($conn) {
    // Get business hours
    $hours_query = "SELECT opening_time, closing_time FROM business_hours ORDER BY id DESC LIMIT 1";
    $hours_result = $conn->query($hours_query);
    
    if ($hours_result && $hours_result->num_rows > 0) {
        $hours = $hours_result->fetch_assoc();
        $closing_time = $hours['closing_time'];
        $current_time = date('H:i:s');
        
        // Convert to minutes for comparison
        $current_minutes = (intval(substr($current_time, 0, 2)) * 60) + intval(substr($current_time, 3, 2));
        $closing_minutes = (intval(substr($closing_time, 0, 2)) * 60) + intval(substr($closing_time, 3, 2));
        
        // Check if business is closed
        $is_closed = false;
        if ($current_minutes < $closing_minutes && $current_minutes < 600) {
            // Past midnight
            $is_closed = true;
        } else if ($current_minutes >= $closing_minutes) {
            // After closing time
            $is_closed = true;
        }
        
        // Truncate cart if closed
        if ($is_closed) {
            $count_query = "SELECT COUNT(*) as count FROM availtoday_cart";
            $count_result = $conn->query($count_query);
            if ($count_result) {
                $count_data = $count_result->fetch_assoc();
                if ($count_data['count'] > 0) {
                    // Truncate the cart
                    $conn->query("TRUNCATE TABLE availtoday_cart");
                    error_log("Auto-truncate: Cart cleared (business closed at $closing_time, current time $current_time)");
                }
            }
        }
    }
}

// Run the auto-truncate check
checkAndTruncateCart($conn);

// Function to convert day names to abbreviations
function getDayAbbreviations($availableDays) {
    if (empty($availableDays)) {
        return '';
    }
    
    $dayMap = [
        'Sunday' => 'S',
        'Monday' => 'M',
        'Tuesday' => 'T',
        'Wednesday' => 'W',
        'Thursday' => 'Th',
        'Friday' => 'F',
        'Saturday' => 'Sa'
    ];
    
    $days = explode(', ', $availableDays);
    $abbreviations = [];
    
    foreach ($days as $day) {
        if (isset($dayMap[trim($day)])) {
            $abbreviations[] = $dayMap[trim($day)];
        }
    }
    
    return implode(', ', $abbreviations);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="shopping-cart.css" />
    <link rel="stylesheet" href="../../confirmations.css" />
    <title>Shopping Cart - Same-Day Order</title>
    <style>
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
        }
        .confirmation-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            min-width: 300px;
        }
        .confirmation-modal-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .confirmation-modal-buttons button {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .confirm-btn {
            background-color: #f44336;
            color: white;
        }
        .cancel-btn {
            background-color: #e0e0e0;
        }
        
        /* Page transition overlay */
        .page-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(230, 126, 34, 0.95), rgba(211, 84, 0, 0.95));
            z-index: 9999;
            display: none;
            opacity: 0;
            transition: opacity 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .page-transition-overlay.active {
            opacity: 1;
        }

        .transition-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .transition-content p {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Smooth page entry animation */
        .wrapper {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInPage 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        @keyframes fadeInPage {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Preload indicator */
        .preload-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(230, 126, 34, 0.9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .preload-indicator.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .cart-section {
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #256035, #1a4a2a);
            color: white;
            padding: 20px 15px 15px 25px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h3 {
            margin: 0;
            font-size: 1.3em;
        }
        
        .section-select-all {
            order: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-select-all input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: white;
        }
        
        .section-select-all label {
            color: white;
            font-weight: 500;
            cursor: pointer;
        }
        
        .section-controls {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-right: 15px;
            padding-left: 250px;
        }
        
        .days-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .days-filter label {
            padding-left: 150px;
            margin-left: auto;
            color: white;
            font-weight: 500;
            font-size: 14px;
        }
        
        .days-filter select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            font-size: 14px;
            cursor: pointer;
        }
        
        .days-column {
            text-align: center;
            font-weight: 500;
            color: #2f603c;
        }
        
        .day-abbreviations {
            font-size: 13px;
            white-space: nowrap;
        }
        
        /* Smart filtering visual feedback */
        .cart-table tbody tr.filtered-out {
            opacity: 0.3;
            background-color: #f8f8f8;
        }
        
        .cart-table tbody tr:not(.filtered-out) {
            background-color: #f0fff0;
            border-left: 3px solid #4CAF50;
        }
        
        .cart-table tbody tr:not(.filtered-out) .days-column {
            background-color: #e8f5e8;
            font-weight: bold;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .section-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .days-filter {
                justify-content: center;
            }
            
            .days-filter select {
                flex: 1;
                max-width: 200px;
            }
            
            .days-column {
                font-size: 12px;
            }
            
            .day-abbreviations {
                font-size: 11px;
            }
        }
        
        .cart-table {
            border-radius: 0 0 8px 8px;
        }
        
        .cart-table.pickup-table {
            border-top: 3px solid #256035;
        }
        
        .cart-table.delivery-table {
            border-top: 3px solid #e67e22;
        }
        
        .no-items-message {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
            background: #f9f9f9;
            border-radius: 0 0 8px 8px;
        }
        
        .mixed-selection-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }

        /* Same-day specific styling */
        .same-day-info {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Available Today section styling */
        .available-today-section .section-header {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        .available-today-section .cart-table {
            border-top: 3px solid #e67e22;
        }

        .available-today-day {
            color: #e67e22;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div id="confirmationPopup" class="confirmation-popup"></div>

<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-modal-content">
        <h3>Remove Item</h3>
        <p>Are you sure you want to remove this item from your cart?</p>
        <div class="confirmation-modal-buttons">
            <button class="cancel-btn" onclick="closeConfirmationModal()">Cancel</button>
            <button class="confirm-btn" id="confirmRemoveBtn">Remove</button>
        </div>
    </div>
</div>

<?php include '../../user-includes/user-header.php'; ?>

<div class="wrapper fade-in">
    <div class="main-container">
        <h2>Shopping Cart</h2>
        <p class="cart-info">
            Same-day orders are available for items marked as "Available Today".<br>
            These items can be ordered and picked up or delivered on the same day.
        </p>

        <!-- Cart Type Navigation -->
        <div class="cart-type-navigation">
            <a href="shopping-cart-preorder.php" class="cart-tab" id="preorderTab" onclick="smoothSwitchToPage('shopping-cart-preorder.php', event); return false;">Pre-Order</a>
            <a href="shopping-cart-sameday.php" class="cart-tab active" id="samedayTab" onclick="smoothSwitchToPage('shopping-cart-sameday.php', event); return false;">Same-Day Order</a>
        </div>

        <!-- Page transition overlay -->
        <div id="pageTransitionOverlay" class="page-transition-overlay">
            <div class="transition-content">
                <div class="loading-spinner"></div>
                <p>Switching to cart...</p>
            </div>
        </div>

        <div id="mixedSelectionWarning" class="mixed-selection-warning">
            <strong>⚠️ Mixed Selection Warning:</strong> You cannot mix Pickup and Delivery products in the same order. Please select items from only one category.
        </div>

        <?php
        // Fetch same-day cart items (Available Today products from availtoday_cart table)
        // Business logic for same-day cart:
        // 1. Products with status_id = 3 (Available Today)
        // 2. Products with status_id IN (1, 2) AND availtoday_status_id IS NOT NULL (1, 2, or 3)
        $stmt = $conn->prepare("
            SELECT cat.id AS cart_id, cat.quantity, p.price,
                   p.id AS product_id, p.name AS product_name, p.quantity as product_stock,
                   ps.name as status_name,
                   p.availtoday_status_id,
                   ats.name as availtoday_status_name,
                   'Today' as available_days,
                   (
                      SELECT image_url FROM product_images pi2
                      WHERE pi2.product_id = p.id AND pi2.is_primary = 1
                      LIMIT 1
                   ) AS image_url
            FROM availtoday_cart cat
            JOIN products p ON cat.product_id = p.id
            LEFT JOIN product_statuses ps ON p.status_id = ps.id
            LEFT JOIN availtoday_status ats ON p.availtoday_status_id = ats.id
            WHERE cat.user_id = ? 
            AND (
                p.status_id = 3 
                OR (p.status_id IN (1, 2) AND p.availtoday_status_id IS NOT NULL)
            )
            ORDER BY p.name ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        
        if ($result->num_rows > 0): 
            // For same-day orders, all items are treated as "Available Today" items
            $sameday_items = [];
            
            while ($row = $result->fetch_assoc()) {
                $sameday_items[] = $row;
            }
        ?>
        <form method="POST" action="availtoday-checkout.php" id="cartForm">
            <input type="hidden" name="valid_cart_ids" value="<?= implode(',', array_column($sameday_items, 'cart_id')) ?>">
            <input type="hidden" name="cart_type" value="sameday">
            <div class="cart-layout">
                <div class="cart-container">
                    <div class="cart-content">
                        <!-- Available Today Products Section -->
                        <div class="cart-section available-today-section">
                            <div class="section-header">
                                <h3>Available Today</h3>
                                <div class="section-controls">
                                    <div class="section-select-all">
                                        <input type="checkbox" id="selectAllSameDay" class="section-checkbox" data-section="sameday">
                                        <label for="selectAllSameDay">Select All Available Today</label>
                                    </div>
                                </div>
                            </div>
                        
                        <?php if (!empty($sameday_items)): ?>
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th></th>
                                    <th>Product Name</th>
                                    <th>Availability</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($sameday_items as $row):
                                    $imageUrl = $row['image_url'] ? "/assets/" . $row['image_url'] : "/assets/images/no-image.jpg";
                                    $item_total = $row['price'] * $row['quantity'];
                                ?>
                                <tr data-cart-id="<?= $row['cart_id'] ?>" data-product-id="<?= $row['product_id'] ?>" data-stock="<?= $row['product_stock'] ?>" data-price="<?= $row['price'] ?>" data-quantity="<?= $row['quantity'] ?>" data-status="sameday">
                                    <td>
                                        <input type="checkbox" name="selected_cart_ids[]" value="<?= $row['cart_id'] ?>" class="item-checkbox sameday-checkbox" data-total="<?= $item_total ?>" data-status="sameday">
                                    </td>
                                    <td><img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" style="width: 60px; height: 60px; object-fit: cover;"></td>
                                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                                    <td class="days-column">
                                        <span class="day-abbreviations available-today-day">Today</span>
                                    </td>
                                    <td>
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" type="button" onclick="updateQuantitySameDay(<?= $row['cart_id'] ?>, <?= $row['quantity'] - 1 ?>)">-</button>
                                            <span class="quantity"><?= $row['quantity'] ?></span>
                                            <button class="quantity-btn" type="button" onclick="updateQuantitySameDay(<?= $row['cart_id'] ?>, <?= $row['quantity'] + 1 ?>)">+</button>
                                        </div>
                                    </td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td class="item-total">₱<?= number_format($item_total, 2) ?></td>
                                    <td>
                                        <button class="remove-btn" type="button" onclick="showConfirmationModalSameDay(<?= $row['cart_id'] ?>)">
                                            <svg viewBox="0 0 448 512" class="svgIcon"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="no-items-message">
                            No same-day products in your cart
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cart-sidebar">
                    <div class="cart-summary">
                        <div class="cart-total"><h3>Subtotal: ₱<span id="displaySubtotal">0.00</span></h3></div>
                        <label><input type="checkbox" id="termsCheckbox" required /> I have read and agreed with the Terms and Conditions</label>
                        <button type="submit" name="checkout" class="checkout-btn" onclick="return validateCart()">Proceed to Checkout</button>
                        <input type="hidden" name="subtotal" id="subtotalInput" value="0">
                        <input type="hidden" name="cart_items" id="cartItemsInput" value="">
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
            <div class="empty-cart">
                <p>Your same-day cart is empty</p>
                <a href="/frontend/pages/products/product-dashboard.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="shopping-cart-sameday.js"></script>

<script>
// Additional page-specific functionality


// Enhanced page load experience
document.addEventListener('DOMContentLoaded', function() {
    // Hide transition overlay if coming from another cart page
    const overlay = document.getElementById('pageTransitionOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
    
    // Enhanced tab hover effects
    const tabs = document.querySelectorAll('.cart-tab');
    tabs.forEach(tab => {
        tab.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(-1px)';
                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
            }
        });
        
        tab.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = '';
                this.style.boxShadow = '';
            }
        });
    });
    
    // Prefetch pre-order cart page for fast switching
    const prefetchLink = document.createElement('link');
    prefetchLink.rel = 'prefetch';
    prefetchLink.href = 'shopping-cart-preorder.php';
    document.head.appendChild(prefetchLink);
});
</script>

</body>
</html>