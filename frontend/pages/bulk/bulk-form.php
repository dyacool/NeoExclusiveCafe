<?php
session_start();

// Include database connection
require_once "../../user-includes/database.php";

$show_success_modal = false;
$bulk_order_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bulk_order'])) {
    try {
        // Get form data
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $contact = mysqli_real_escape_string($conn, $_POST['contact']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $billing_address = mysqli_real_escape_string($conn, $_POST['billing_address']);
        $order_type = mysqli_real_escape_string($conn, $_POST['order_type']);
        $delivery_address = isset($_POST['delivery_address']) ? mysqli_real_escape_string($conn, $_POST['delivery_address']) : '';
        $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
        $date_needed = mysqli_real_escape_string($conn, $_POST['date_needed']);
        $time_needed = mysqli_real_escape_string($conn, $_POST['time_needed']);
        $note = mysqli_real_escape_string($conn, $_POST['note']);
        $total_amount = floatval($_POST['total_amount']);
        
        // Get selected products
        $selected_products = json_decode($_POST['selected_products'], true);
        
        // Create bulk_orders table if it doesn't exist
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS bulk_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                name VARCHAR(255) NOT NULL,
                contact VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                billing_address TEXT NOT NULL,
                order_type ENUM('delivery', 'pickup') NOT NULL,
                delivery_address TEXT,
                purpose TEXT NOT NULL,
                date_needed DATE NOT NULL,
                time_needed TIME NOT NULL,
                note TEXT,
                total_amount DECIMAL(10,2) NOT NULL,
                total_items INT NOT NULL DEFAULT 0,
                status ENUM('pending', 'approved', 'payment_received', 'ready_for_delivery', 'ready_for_pickup', 'cancelled', 'completed') DEFAULT 'pending',
                proof_of_payment VARCHAR(255) NULL,
                admin_updated BOOLEAN DEFAULT FALSE,
                admin_notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        mysqli_query($conn, $create_table_query);
        
        // Create bulk_order_items table if it doesn't exist
        $create_items_table = "
            CREATE TABLE IF NOT EXISTS bulk_order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bulk_order_id INT,
                product_id INT,
                product_name VARCHAR(255),
                product_price DECIMAL(10,2),
                quantity INT,
                subtotal DECIMAL(10,2),
                FOREIGN KEY (bulk_order_id) REFERENCES bulk_orders(id) ON DELETE CASCADE
            )
        ";
        mysqli_query($conn, $create_items_table);
        
        // Calculate total items
        $total_items = 0;
        foreach ($selected_products as $product) {
            $total_items += intval($product['quantity']);
        }
        
        // Insert bulk order
        $insert_order = "INSERT INTO bulk_orders (user_id, name, contact, email, billing_address, order_type, delivery_address, purpose, date_needed, time_needed, note, total_amount, total_items) 
                        VALUES ('$user_id', '$name', '$contact', '$email', '$billing_address', '$order_type', '$delivery_address', '$purpose', '$date_needed', '$time_needed', '$note', '$total_amount', '$total_items')";
        
        if (mysqli_query($conn, $insert_order)) {
            $bulk_order_id = mysqli_insert_id($conn);
            
            // Insert order items
            foreach ($selected_products as $product) {
                $product_id = intval($product['id']);
                $product_name = mysqli_real_escape_string($conn, $product['name']);
                $product_price = floatval($product['price']);
                $quantity = intval($product['quantity']);
                $subtotal = $product_price * $quantity;
                
                $insert_item = "INSERT INTO bulk_order_items (bulk_order_id, product_id, product_name, product_price, quantity, subtotal) 
                               VALUES ('$bulk_order_id', '$product_id', '$product_name', '$product_price', '$quantity', '$subtotal')";
                mysqli_query($conn, $insert_item);
            }
            
            $show_success_modal = true;
        } else {
            $error_message = "Error submitting order: " . mysqli_error($conn);
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all products from database with proper product details
$products_query = "SELECT p.id, p.name, p.price, ps.name as status_name 
                   FROM products p 
                   LEFT JOIN product_statuses ps ON p.status_id = ps.id 
                   WHERE p.deleted_at IS NULL 
                   ORDER BY p.name";
$products_result = mysqli_query($conn, $products_query);
$products = [];

if ($products_result) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
}

// Calculate minimum date (2 weeks from now)
$min_date = date('Y-m-d', strtotime('+14 days'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Form - NeoCafe</title>
    <link rel="stylesheet" href="bulk-form.css">
</head>
<?php include "../../user-includes/navbar/customer-navigation.php"; ?>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Bulk Order Form</h1>
            <p>Fill out the form below to place your bulk order</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form id="bulkOrderForm" method="POST" action="">
            <!-- Customer Information Section -->
            <div class="form-section">
                <h2>Customer Information</h2>
                
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number *</label>
                    <input type="tel" id="contact" name="contact" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="billing_address">Billing Address *</label>
                    <textarea id="billing_address" name="billing_address" rows="3" required readonly style="resize: none;"></textarea>
                </div>

                <div class="form-group">
                    <label for="order_type">Order Type *</label>
                    <select id="order_type" name="order_type" required>
                        <option value="">Select Order Type</option>
                        <option value="delivery">Delivery</option>
                        <option value="pickup">Pick Up</option>
                    </select>
                </div>

                <div class="form-group" id="delivery_address_group" style="display: none;">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea id="delivery_address" name="delivery_address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="purpose">Purpose of Order *</label>
                    <textarea id="purpose" name="purpose" rows="2" required placeholder="e.g., Corporate event, Wedding, Birthday party, etc." readonly style="resize: none;"></textarea>
                </div>

                <div class="form-group">
                    <label for="date_needed">Date Needed *</label>
                    <input type="date" id="date_needed" name="date_needed" min="<?php echo $min_date; ?>" required>
                    <small>Minimum 2 weeks advance notice required</small>
                </div>

                <div class="form-group">
                    <label for="time_needed">Time Needed *</label>
                    <select id="time_needed" name="time_needed" required>
                        <option value="">Select Time</option>
                        <option value="06:00">6:00 AM</option>
                        <option value="06:30">6:30 AM</option>
                        <option value="07:00">7:00 AM</option>
                        <option value="07:30">7:30 AM</option>
                        <option value="08:00">8:00 AM</option>
                        <option value="08:30">8:30 AM</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="09:30">9:30 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="10:30">10:30 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="11:30">11:30 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="12:30">12:30 PM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="13:30">1:30 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="14:30">2:30 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="15:30">3:30 PM</option>
                        <option value="16:00">4:00 PM</option>
                        <option value="16:30">4:30 PM</option>
                        <option value="17:00">5:00 PM</option>
                        <option value="17:30">5:30 PM</option>
                        <option value="18:00">6:00 PM</option>
                    </select>
                    <small>Available times: 6:00 AM - 6:00 PM</small>
                </div>
            </div>

            <!-- Product Selection Section -->
            <div class="form-section">
                <h2>Product Selection</h2>
                <p>Select the products you want to include in your bulk order:</p>
                
                <div class="products-grid" id="productsGrid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-item">
                                <div class="product-checkbox">
                                    <input type="checkbox" 
                                           id="product_<?php echo $product['id']; ?>" 
                                           value="<?php echo $product['id']; ?>"
                                           data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                           data-price="<?php echo $product['price']; ?>"
                                           data-status="<?php echo isset($product['status_name']) ? $product['status_name'] : 'available'; ?>"
                                           class="product-select">
                                    <label for="product_<?php echo $product['id']; ?>">
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    </label>
                                </div>
                                <div class="quantity-input" style="display: none;">
                                    <label for="quantity_<?php echo $product['id']; ?>">Quantity (Min: 12):</label>
                                    <input type="number" 
                                           id="quantity_<?php echo $product['id']; ?>" 
                                           min="12" 
                                           value="12" 
                                           class="quantity-field">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products-message">
                            <p><strong>No products found in the database.</strong></p>
                            <p>Please contact the administrator to add products.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="form-section">
                <h2>Order Summary</h2>
                <div id="orderSummary" class="order-summary">
                    <p>No products selected</p>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="form-section">
                <h2>Additional Notes</h2>
                <div class="form-group">
                    <label for="note">Special Instructions or Notes</label>
                    <textarea id="note" name="note" rows="4" placeholder="Any special requirements, dietary restrictions, or additional information..."></textarea>
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" id="selectedProducts" name="selected_products" value="">
            <input type="hidden" id="totalAmount" name="total_amount" value="0">

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" id="discardBtn" class="btn btn-secondary">Discard</button>
                <button type="submit" name="submit_bulk_order" class="btn btn-primary" id="submitBtn" disabled>Submit Order</button>
            </div>
        </form>
    </div>

    <!-- Success Modal -->
    <?php if ($show_success_modal): ?>
    <div id="successModal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Submitted Successfully!</h2>
            </div>
            <div class="modal-body">
                <div class="success-icon">✓</div>
                <p>Your bulk order has been submitted successfully.</p>
                <p><strong>Order ID: #<?php echo str_pad($bulk_order_id, 6, '0', STR_PAD_LEFT); ?></strong></p>
                <p>We will review your order and get back to you within 24-48 hours.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='../products/user-products.php'">Back to Products</button>
                <button type="button" class="btn btn-primary" onclick="window.location.href='bulk-order-details.php?id=<?php echo $bulk_order_id; ?>'">View Order Details</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="bulk-form.js"></script>
</body>
</html>
