<?php
session_start();

// Connect to online database
$conn = new mysqli("mysql-neoexclusivecafe.alwaysdata.net", "429123", "NeoCafe123", "neoexclusivecafe_crud");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
        
        // Create bulk_orders table with unique_order_id as primary key
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS bulk_orders (
                unique_order_id VARCHAR(20) PRIMARY KEY,
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
                bulk_order_id VARCHAR(20),
                product_id INT,
                product_name VARCHAR(255),
                product_price DECIMAL(10,2),
                quantity INT,
                subtotal DECIMAL(10,2),
                FOREIGN KEY (bulk_order_id) REFERENCES bulk_orders(unique_order_id) ON DELETE CASCADE
            )
        ";
        mysqli_query($conn, $create_items_table);
        
        // Calculate total items
        $total_items = 0;
        foreach ($selected_products as $product) {
            $total_items += intval($product['quantity']);
        }
        
        // Generate next unique order ID
        $get_next_id_query = "SELECT COUNT(*) + 1 as next_id FROM bulk_orders";
        $next_id_result = mysqli_query($conn, $get_next_id_query);
        $next_id_row = mysqli_fetch_assoc($next_id_result);
        $next_id = $next_id_row['next_id'];
        $unique_order_id = 'BO' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
        
        // Insert bulk order with unique_order_id as primary key
        $insert_order = "INSERT INTO bulk_orders (unique_order_id, user_id, name, contact, email, billing_address, order_type, delivery_address, purpose, date_needed, time_needed, note, total_amount, total_items) 
                        VALUES ('$unique_order_id', '$user_id', '$name', '$contact', '$email', '$billing_address', '$order_type', '$delivery_address', '$purpose', '$date_needed', '$time_needed', '$note', '$total_amount', '$total_items')";
        
        if (mysqli_query($conn, $insert_order)) {
            $bulk_order_id = $unique_order_id;
            
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
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="-1">
    <meta http-equiv="Clear-Site-Data" content="cache,storage">
    <title>Bulk Order Form - NeoCafe</title>
    <link rel="stylesheet" href="bulk-form.css">
    <script>
        // Clear form data immediately when the page starts loading
        document.addEventListener('DOMContentLoaded', function() {
            history.replaceState(null, '', document.location.href);
        });
        
        // Prevent bfcache
        window.addEventListener('unload', function() {});
        
        // Force reload on back navigation
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                window.location.reload();
            }
        });
    </script>
</head>
<?php include "../../user-includes/navbar/customer-navigation.php"; ?>
<body>
    <div class="wrapper">
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

        <form id="bulkOrderForm" method="POST" action="" autocomplete="off" data-form-restore="false" onpageshow="if(event.persisted) window.location.reload()">
            <!-- Customer Information Section -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Customer Information</h2>
                    <p>Please provide your contact details and billing information</p>
                </div>
                
                <div class="section-content">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="contact">Contact Number <span class="required">*</span></label>
                        <input type="tel" id="contact" name="contact" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="billing_address">Billing Address <span class="required">*</span></label>
                        <textarea id="billing_address" name="billing_address" rows="3" required style="resize: none;"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="order_type">Order Type <span class="required">*</span></label>
                        <select id="order_type" name="order_type" required>
                            <option value="">Select Order Type</option>
                            <option value="delivery">Delivery</option>
                            <option value="pickup">Pick Up</option>
                        </select>
                    </div>

                    <div class="form-group" id="delivery_address_group" style="display: none;">
                        <label for="delivery_address">Delivery Address <span class="required">*</span></label>
                        <textarea id="delivery_address" name="delivery_address" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="purpose">Purpose of Order <span class="required">*</span></label>
                        <textarea id="purpose" name="purpose" rows="2" required placeholder="e.g., Corporate event, Wedding, Birthday party, etc." style="resize: none;"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date_needed">Date Needed <span class="required">*</span></label>
                        <input type="date" id="date_needed" name="date_needed" min="<?php echo $min_date; ?>" required>
                        <small>Minimum 2 weeks advance notice required</small>
                    </div>

                    <div class="form-group">
                        <label for="time_needed">Time Needed <span class="required">*</span></label>
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
            </div>

            <!-- Product Selection Section -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Product Selection</h2>
                    <p>Choose the products you want to include in your bulk order (minimum 12 pieces per item)</p>
                </div>
                
                <div class="section-content">
                    <div class="products-grid" id="productsGrid">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <div class="product-card" id="card_<?php echo $product['id']; ?>">
                                    <div class="product-header">
                                        <div class="product-checkbox-wrapper">
                                            <input type="checkbox" 
                                                   id="product_<?php echo $product['id']; ?>" 
                                                   value="<?php echo $product['id']; ?>"
                                                   data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                   data-price="<?php echo $product['price']; ?>"
                                                   class="product-checkbox product-select">
                                        </div>
                                        <div class="product-info">
                                            <label for="product_<?php echo $product['id']; ?>" class="product-name">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </label>
                                            <div class="product-price"><?php echo number_format($product['price'], 2); ?></div>
                                        </div>
                                    </div>
                                    <div class="quantity-section" id="quantity_section_<?php echo $product['id']; ?>">
                                        <label class="quantity-label">Quantity (Min: 12 pieces)</label>
                                        <div class="quantity-controls">
                                            <div class="quantity-input-group">
                                                <button type="button" class="quantity-btn" onclick="decreaseQuantity(<?php echo $product['id']; ?>)">−</button>
                                                <input type="number" 
                                                       id="quantity_<?php echo $product['id']; ?>" 
                                                       min="12" 
                                                       value="12" 
                                                       class="quantity-field"
                                                       onchange="updateQuantity(<?php echo $product['id']; ?>)">
                                                <button type="button" class="quantity-btn" onclick="increaseQuantity(<?php echo $product['id']; ?>)">+</button>
                                            </div>
                                            <div class="item-subtotal" id="subtotal_<?php echo $product['id']; ?>">
                                                <?php echo number_format($product['price'] * 12, 2); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-products-message">
                                <h3>No Products Available</h3>
                                <p>We're currently updating our product inventory.</p>
                                <p>Please contact the administrator to add products or try again later.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="form-section order-summary-section">
                <div class="summary-header">
                    <h3>Order Summary</h3>
                </div>
                <div class="summary-content">
                    <div id="orderSummary" class="order-summary">
                        <div class="summary-empty">
                            <h4>No Products Selected</h4>
                            <p>Choose products from the selection above to see your order summary</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="form-section">
                <div class="section-header">
                    <h2>Additional Notes</h2>
                    <p>Any special instructions or requirements for your order</p>
                </div>
                
                <div class="section-content">
                    <div class="form-group">
                        <label for="note">Special Instructions or Notes</label>
                        <textarea id="note" name="note" rows="4" placeholder="Any special requirements, dietary restrictions, packaging instructions, or additional information..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" id="selectedProducts" name="selected_products" value="">
            <input type="hidden" id="totalAmount" name="total_amount" value="0">

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" id="discardBtn" class="btn btn-secondary">
                    Discard Order
                </button>
                <button type="button" id="reviewOrderBtn" class="btn btn-primary" disabled>
                    Review Order
                </button>
            </div>
        </form>
    </div>

    <!-- Order Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <h2>Review Your Bulk Order</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Customer Information -->
                <div class="confirmation-section">
                    <h3>Customer Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Name:</label>
                            <span id="confirm-name"></span>
                        </div>
                        <div class="info-item">
                            <label>Contact:</label>
                            <span id="confirm-contact"></span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span id="confirm-email"></span>
                        </div>
                        <div class="info-item">
                            <label>Billing Address:</label>
                            <span id="confirm-billing-address"></span>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="confirmation-section">
                    <h3>Order Details</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Order Type:</label>
                            <span id="confirm-order-type"></span>
                        </div>
                        <div class="info-item" id="delivery-address-section" style="display: none;">
                            <label>Delivery Address:</label>
                            <span id="confirm-delivery-address"></span>
                        </div>
                        <div class="info-item">
                            <label>Purpose:</label>
                            <span id="confirm-purpose"></span>
                        </div>
                        <div class="info-item">
                            <label>Date Needed:</label>
                            <span id="confirm-date-needed"></span>
                        </div>
                        <div class="info-item">
                            <label>Time Needed:</label>
                            <span id="confirm-time-needed"></span>
                        </div>
                        <div class="info-item">
                            <label>Special Notes:</label>
                            <span id="confirm-note"></span>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="confirmation-section">
                    <h3>Order Items</h3>
                    <div class="order-summary-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="confirm-order-items">
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3"><strong>Total Amount:</strong></td>
                                    <td><strong id="confirm-total-amount">₱0.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="editOrderBtn">
                    Edit Order
                </button>
                <button type="button" class="btn btn-primary" id="confirmSubmitBtn">
                    Confirm & Submit Order
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Final Submission -->
    <form id="finalSubmissionForm" method="POST" style="display: none;">
        <input type="hidden" name="submit_bulk_order" value="1">
        <input type="hidden" name="name" id="final-name">
        <input type="hidden" name="contact" id="final-contact">
        <input type="hidden" name="email" id="final-email">
        <input type="hidden" name="billing_address" id="final-billing-address">
        <input type="hidden" name="order_type" id="final-order-type">
        <input type="hidden" name="delivery_address" id="final-delivery-address">
        <input type="hidden" name="purpose" id="final-purpose">
        <input type="hidden" name="date_needed" id="final-date-needed">
        <input type="hidden" name="time_needed" id="final-time-needed">
        <input type="hidden" name="note" id="final-note">
        <input type="hidden" name="selected_products" id="final-selected-products">
        <input type="hidden" name="total_amount" id="final-total-amount">
    </form>

    <!-- Success Modal -->
    <?php if ($show_success_modal): ?>
    <div id="successModal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🎉 Order Submitted Successfully!</h2>
            </div>
            <div class="modal-body">
                <div class="success-icon">✓</div>
                <p><strong>Thank you for your bulk order!</strong></p>
                <p><strong>Order ID: #<?php echo str_pad($bulk_order_id, 6, '0', STR_PAD_LEFT); ?></strong></p>
                <div class="success-details">
                    <p>✉️ <strong>What happens next?</strong></p>
                    <ul>
                        <li>Our team will review your order within <strong>24-72 hours</strong></li>
                        <li>We'll contact you at <strong><?php echo htmlspecialchars($_POST['email'] ?? ''); ?></strong> with pricing and availability</li>
                        <li>You can track your order status in your profile</li>
                    </ul>
                </div>
                <p class="contact-note">📞 For urgent inquiries, please contact us directly.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='../products/user-products.php'">Back to Products</button>
                <button type="button" class="btn btn-primary" onclick="window.location.href='../profile/profile.php'">View in Profile</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    </div> <!-- End of wrapper -->

    <script src="bulk-form.js"></script>
    <script>
        // Comprehensive form restoration prevention
        (function() {
            // Clear all storage
            localStorage.clear();
            sessionStorage.clear();
            
            // Reset form and prevent restoration
            const form = document.getElementById('bulkOrderForm');
            form.reset();
            form.setAttribute('autocomplete', 'off');
            
            // Prevent back-forward cache
            window.addEventListener('pageshow', function(e) {
                if (e.persisted || performance.navigation.type === 2) {
                    window.location.reload();
                }
            });
            
            // Clear history state
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Disable form storage
            window.addEventListener('beforeunload', function() {
                form.reset();
            });
            
            // Force manual scroll restoration
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
        })();
    </script>
</body>
</html>
