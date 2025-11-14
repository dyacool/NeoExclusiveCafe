<?php
// Enable error reporting and logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors on page (could break JSON)
ini_set('log_errors', 1);

// Load database first (starts session)
if (!isset($conn)) {
    require_once __DIR__ . "/../admin-includes/database.php";
}
require_once __DIR__ . "/../../../includes/session-manager.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: /backend/login/admin/admin-login.php");
    exit();
}
require_once __DIR__ . "/../admin-includes/activity-logger.php";
require_once __DIR__ . "/../admin-includes/notifications/notification.php";
require_once __DIR__ . "/../admin-includes/mailer.php";

// Handle redirect messages from update-bulk-status.php
$success_message = '';
$error_message = '';

if (isset($_GET['status_updated']) && $_GET['status_updated'] === '1') {
    $success_message = "Order status updated successfully!";
}
if (isset($_GET['error']) && $_GET['error'] === '1') {
    $error_message = "Error updating order status. Please try again.";
}

// SIMPLE FORM-BASED DISCOUNT PRICE HANDLER (No AJAX!)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_discount_prices'])) {
    $order_id = (int)$_POST['order_id'];
    $success = true;
    $error_message = '';
    $discount_total = 0;
    
    try {
        // Process each discount price
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'discount_price_') === 0) {
                $item_id = (int)str_replace('discount_price_', '', $key);
                $discount_price = floatval($value);
                $retail_price = floatval($_POST['retail_price_' . $item_id] ?? 0);
                $quantity = intval($_POST['quantity_' . $item_id] ?? 0);
                
                // Validate discount price
                if ($discount_price > 0 && $discount_price >= $retail_price) {
                    throw new Exception("Discount price (₱" . number_format($discount_price, 2) . ") must be lower than retail price (₱" . number_format($retail_price, 2) . ") for item #$item_id");
                }
                
                // Update the discount price
                $discount_price_value = $discount_price > 0 ? $discount_price : null;
                $update_sql = "UPDATE bulk_order_items SET discount_price = ? WHERE id = ? AND bulk_order_id = ?";
                $stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($stmt, "dii", $discount_price_value, $item_id, $order_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to update item #$item_id: " . mysqli_error($conn));
                }
                
                if ($discount_price > 0) {
                    $discount_total += $discount_price * $quantity;
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        // Update the order total and auto-approve
        $discount_total_value = $discount_total > 0 ? $discount_total : null;
        $update_order_sql = "UPDATE bulk_orders SET discount_total = ?, status = 'approved', admin_updated = NOW() WHERE id = ?";
        $order_stmt = mysqli_prepare($conn, $update_order_sql);
        mysqli_stmt_bind_param($order_stmt, "di", $discount_total_value, $order_id);
        
        if (!mysqli_stmt_execute($order_stmt)) {
            throw new Exception("Failed to update order: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($order_stmt);
        
        // Log the activity
        logAdminActivity($conn, 'UPDATE', "Updated discount pricing for bulk order #$order_id (Total: ₱" . number_format($discount_total, 2) . ") and auto-approved", 'bulk_orders', $order_id);
        
        // Get order details for notification
        $order_info_sql = "SELECT user_id, unique_order_id FROM bulk_orders WHERE id = ?";
        $order_info_stmt = mysqli_prepare($conn, $order_info_sql);
        mysqli_stmt_bind_param($order_info_stmt, "i", $order_id);
        mysqli_stmt_execute($order_info_stmt);
        $order_info_result = mysqli_stmt_get_result($order_info_stmt);
        $order_info = mysqli_fetch_assoc($order_info_result);
        mysqli_stmt_close($order_info_stmt);
        
        // Send approval email to customer
        try {
            sendBulkOrderApprovalEmail($order_id, $conn);
        } catch (Exception $e) {
            error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
        }
        
        // Create user notification for approval
        if ($order_info) {
            try {
                $notificationHandler = new NotificationHandler($conn);
                $notificationHandler->createUserBulkOrderNotification(
                    $order_info['user_id'],
                    $order_id,
                    'bulk_approved',
                    $order_info['unique_order_id']
                );
                error_log("✓ User notification created for bulk order #$order_id approval");
            } catch (Exception $e) {
                error_log("Failed to create approval notification: " . $e->getMessage());
            }
        }
        
        $success_message = "Discount prices saved successfully! Order automatically approved.";
        
    } catch (Exception $e) {
        $success = false;
        $error_message = $e->getMessage();
    }
}

// Check if this is an AJAX request
$is_ajax_request = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']);

// Debug logging for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("Action: " . ($_POST['action'] ?? 'none'));
    error_log("Order ID from POST: " . ($_POST['order_id'] ?? 'none'));
    error_log("Is AJAX request: " . ($is_ajax_request ? 'yes' : 'no'));
}

// HANDLE ALL AJAX REQUESTS FIRST (before any database operations that might produce output)
if ($is_ajax_request) {
    // Simple test endpoint for debugging AJAX
    if ($_POST['action'] === 'test_ajax') {
        error_log("TEST AJAX ENDPOINT REACHED!");
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'AJAX is working!', 'timestamp' => date('Y-m-d H:i:s')]);
        exit();
    }
    
    // Get order ID for AJAX requests
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    
    error_log("=== AJAX REQUEST START ===");
    error_log("Action: " . $_POST['action']);
    error_log("Order ID: " . $order_id);
    error_log("POST data: " . json_encode($_POST));
    
    // Status updates now handled by update-bulk-status.php (form submission)
    
    // Handle discount price updates
    if ($_POST['action'] === 'update_discount_prices') {
        error_log("=== DISCOUNT PRICE UPDATE REQUEST ===");
        error_log("POST data: " . print_r($_POST, true));
        
        $target_id = $order_id;
        $items_data = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
        
        error_log("Target ID: $target_id");
        error_log("Items data: " . print_r($items_data, true));
        
        $ok = true;
        $err = '';
        $discount_total = 0;
        
        if (empty($items_data)) {
            $ok = false;
            $err = 'No items data received';
            error_log("ERROR: No items data");
        } elseif ($target_id <= 0) {
            $ok = false;
            $err = 'Invalid order ID';
            error_log("ERROR: Invalid order ID");
        } else {
            foreach ($items_data as $item_data) {
                $item_id = (int)$item_data['id'];
                $discount_price = floatval($item_data['discount_price'] ?? 0);
                $quantity = intval($item_data['quantity']);
                $retail_price = floatval($item_data['retail_price'] ?? 0);
                
                error_log("Processing item ID: $item_id, discount_price: $discount_price, retail_price: $retail_price, quantity: $quantity");
                
                // Backend validation: Ensure discount price is lower than retail price (cannot be equal or higher)
                if ($discount_price > 0 && $discount_price >= $retail_price) {
                    $ok = false;
                    $err = "Item #$item_id: Discount price (₱" . number_format($discount_price, 2) . ") must be lower than retail price (₱" . number_format($retail_price, 2) . ")";
                    error_log("ERROR: " . $err);
                    break;
                }
                
                // Allow NULL for discount_price if it's 0 or empty
                $discount_price_value = $discount_price > 0 ? $discount_price : null;
                
                if ($discount_price > 0) {
                    $discount_total += $discount_price * $quantity;
                }
                
                $update_item_sql = "UPDATE bulk_order_items SET discount_price = ? WHERE id = ? AND bulk_order_id = ?";
                $update_item_stmt = mysqli_prepare($conn, $update_item_sql);
                
                if (!$update_item_stmt) {
                    $ok = false;
                    $err = "Failed to prepare statement: " . mysqli_error($conn);
                    error_log("ERROR: " . $err);
                    break;
                }
                
                mysqli_stmt_bind_param($update_item_stmt, "dii", $discount_price_value, $item_id, $target_id);
                
                if (!mysqli_stmt_execute($update_item_stmt)) {
                    $ok = false;
                    $err = "Failed to execute: " . mysqli_error($conn);
                    error_log("ERROR: " . $err);
                    mysqli_stmt_close($update_item_stmt);
                    break;
                }
                
                $affected_rows = mysqli_stmt_affected_rows($update_item_stmt);
                error_log("Updated item $item_id, affected rows: $affected_rows");
                mysqli_stmt_close($update_item_stmt);
            }
            
            // Update discount total in bulk_orders table
            if ($ok) {
                $discount_total_value = $discount_total > 0 ? $discount_total : null;
                error_log("Updating bulk_orders table with discount_total: $discount_total");
                
                // Update discount total and automatically approve the order when discount is applied
                $update_discount_total_sql = "UPDATE bulk_orders SET discount_total = ?, status = 'approved', admin_updated = NOW() WHERE id = ?";
                $update_discount_total_stmt = mysqli_prepare($conn, $update_discount_total_sql);
                
                if (!$update_discount_total_stmt) {
                    $ok = false;
                    $err = "Failed to prepare bulk_orders update: " . mysqli_error($conn);
                    error_log("ERROR: " . $err);
                } else {
                    mysqli_stmt_bind_param($update_discount_total_stmt, "di", $discount_total_value, $target_id);
                    $ok = mysqli_stmt_execute($update_discount_total_stmt);
                    
                    if (!$ok) { 
                        $err = "Failed to update bulk_orders: " . mysqli_error($conn);
                        error_log("ERROR: " . $err);
                    } else {
                        error_log("Successfully updated bulk_orders, affected rows: " . mysqli_stmt_affected_rows($update_discount_total_stmt));
                    }
                    
                    mysqli_stmt_close($update_discount_total_stmt);
                    
                    // Log the activity
                    if ($ok) {
                        logAdminActivity($conn, 'UPDATE', "Updated discount pricing for bulk order #$target_id (Discount Total: ₱" . number_format($discount_total, 2) . ") and auto-approved order", 'bulk_orders', $target_id);
                        error_log("Activity logged successfully");
                        
                        // Send approval email to customer
                        try {
                            sendBulkOrderApprovalEmail($target_id, $conn);
                        } catch (Exception $e) {
                            error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'error' => $ok ? null : ($err ?: 'Update failed'), 'discount_total' => $discount_total, 'new_status' => 'approved']);
        exit();
    }
    
    // If we get here, it's an unknown AJAX action
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unknown action: ' . ($_POST['action'] ?? 'none')]);
    exit();
}

// Get the order ID from URL (skip redirect for AJAX requests)
if (!$is_ajax_request && (!isset($_GET['id']) || empty($_GET['id']))) {
    header("Location: bulk-order-lists.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;



// Check if columns exist and add them if they don't
$check_columns = [
    'unique_order_id' => "ALTER TABLE `bulk_orders` ADD COLUMN `unique_order_id` varchar(20) DEFAULT NULL AFTER `id`",
    'total_items' => "ALTER TABLE `bulk_orders` ADD COLUMN `total_items` int(11) NOT NULL DEFAULT 0 AFTER `total_amount`",
    'admin_updated' => "ALTER TABLE `bulk_orders` ADD COLUMN `admin_updated` timestamp NULL DEFAULT NULL AFTER `proof_of_payment`",
    'admin_notes' => "ALTER TABLE `bulk_orders` ADD COLUMN `admin_notes` text DEFAULT NULL AFTER `admin_updated`"
];

foreach ($check_columns as $column => $alter_query) {
    $check_sql = "SHOW COLUMNS FROM `bulk_orders` LIKE '$column'";
    $check_result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_result) == 0) {
        mysqli_query($conn, $alter_query);
    }
}

// Ensure status enum includes new values
$desired_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','ready_for_pickup','cancelled','rejected','completed'];
$colRes = mysqli_query($conn, "SHOW COLUMNS FROM `bulk_orders` LIKE 'status'");
if ($colRes && mysqli_num_rows($colRes) > 0) {
    $colInfo = mysqli_fetch_assoc($colRes);
    if (isset($colInfo['Type']) && stripos($colInfo['Type'], "enum(") === 0) {
        preg_match_all("/'([^']+)'/", $colInfo['Type'], $matches);
        $current = $matches[1] ?? [];
        $missing = array_diff($desired_statuses, $current);
        if (!empty($missing)) {
            $enumList = "'" . implode("','", $desired_statuses) . "'";
            @mysqli_query($conn, "ALTER TABLE `bulk_orders` MODIFY COLUMN `status` enum($enumList) NOT NULL DEFAULT 'pending'");
        }
    }
}

// Create bulk_order_items table if it doesn't exist
// Items table aligned with integer bulk_order_id FK
$create_items_table_query = "
    CREATE TABLE IF NOT EXISTS `bulk_order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bulk_order_id` int(11) NOT NULL,
        `product_id` int(11) DEFAULT NULL,
        `product_name` varchar(255) NOT NULL,
        `product_price` decimal(10,2) NOT NULL,
        `quantity` int(11) NOT NULL,
        `subtotal` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `bulk_order_id` (`bulk_order_id`),
        CONSTRAINT `bulk_order_items_ibfk_1` FOREIGN KEY (`bulk_order_id`) REFERENCES `bulk_orders` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
mysqli_query($conn, $create_items_table_query);

// Simple test endpoint for debugging AJAX
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'test_ajax') {
    error_log("TEST AJAX ENDPOINT REACHED!");
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'AJAX is working!', 'timestamp' => date('Y-m-d H:i:s')]);
    exit();
}

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['new_status'] ?? '';
    $is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $allowed_statuses = ['pending','approved','payment_received','payment_rejected','ready_for_delivery','ready_for_pickup','cancelled','rejected','completed'];
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE bulk_orders SET status = ?, admin_updated = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $target_id);
        $ok = mysqli_stmt_execute($update_stmt);
        $err = mysqli_error($conn);
        mysqli_stmt_close($update_stmt);
        
        // Log the activity
        if ($ok) {
            logAdminActivity($conn, 'UPDATE', "Changed bulk order #$target_id status to '$new_status'", 'bulk_orders', $target_id);
            
            // Get order details for emails and notifications
            $order_info_sql = "SELECT b.user_id, b.unique_order_id, b.name, b.email, u.username FROM bulk_orders b 
                              LEFT JOIN users u ON b.user_id = u.id 
                              WHERE b.id = ?";
            $order_info_stmt = $conn->prepare($order_info_sql);
            $order_info_stmt->bind_param("i", $target_id);
            $order_info_stmt->execute();
            $order_info_result = $order_info_stmt->get_result();
            $order_info = $order_info_result->fetch_assoc();
            
            if ($order_info) {
                // Send approval email to customer if status is approved
                if ($new_status === 'approved') {
                    try {
                        sendBulkOrderApprovalEmail($target_id, $conn);
                        error_log("✓ Approval email sent for bulk order #$target_id");
                    } catch (Exception $e) {
                        error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
                    }
                    
                    // Create user notification for approval
                    try {
                        $notificationHandler = new NotificationHandler($conn);
                        $notificationHandler->createUserBulkOrderNotification(
                            $order_info['user_id'],
                            $target_id,
                            'bulk_approved',
                            $order_info['unique_order_id']
                        );
                        error_log("✓ User notification created for bulk order #$target_id approval");
                    } catch (Exception $e) {
                        error_log("Failed to create approval notification: " . $e->getMessage());
                    }
                }
                
                // Send payment received email to customer if status is payment_received
                if ($new_status === 'payment_received') {
                    try {
                        sendBulkOrderPaymentReceivedEmail($target_id, $conn);
                        error_log("✓ Payment received email sent for bulk order #$target_id");
                    } catch (Exception $e) {
                        error_log("Failed to send payment received email: " . $e->getMessage());
                    }
                    
                    // Create user notification for payment received
                    try {
                        $notificationHandler = new NotificationHandler($conn);
                        $notificationHandler->createUserBulkOrderNotification(
                            $order_info['user_id'],
                            $target_id,
                            'bulk_payment_received',
                            $order_info['unique_order_id']
                        );
                        error_log("✓ User notification created for bulk order #$target_id payment received");
                    } catch (Exception $e) {
                        error_log("Failed to create payment received notification: " . $e->getMessage());
                    }
                }
                
                // Send payment rejection email to customer if status is payment_rejected
                if ($new_status === 'payment_rejected') {
                    try {
                        sendBulkOrderPaymentRejectedEmail($target_id, $conn);
                        error_log("✓ Payment rejection email sent for bulk order #$target_id");
                    } catch (Exception $e) {
                        error_log("Failed to send payment rejection email: " . $e->getMessage());
                    }
                    
                    // Create user notification for payment rejection
                    try {
                        $notificationHandler = new NotificationHandler($conn);
                        $notificationHandler->createUserBulkOrderNotification(
                            $order_info['user_id'],
                            $target_id,
                            'bulk_payment_rejected',
                            $order_info['unique_order_id']
                        );
                        error_log("✓ User notification created for bulk order #$target_id payment rejection");
                    } catch (Exception $e) {
                        error_log("Failed to create payment rejection notification: " . $e->getMessage());
                    }
                }
                
                // Create admin notification for status change
                try {
                    $notificationHandler = new NotificationHandler($conn);
                    $notificationHandler->createBulkOrderNotification(
                        $target_id,
                        'bulk_status',
                        $order_info['name'],
                        $order_info['username'],
                        ucfirst(str_replace('_', ' ', $new_status))
                    );
                    error_log("✓ Admin notification created for bulk order #$target_id status update");
                } catch (Exception $notif_error) {
                    error_log("Failed to create bulk status notification: " . $notif_error->getMessage());
                }
            }
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed')]);
            exit();
        } else {
            if ($ok) {
                $success_message = "Order status updated successfully to " . ucfirst(str_replace('_', ' ', $new_status)) . "!";
            } else {
                $error_message = "Error updating order status: " . $err;
            }
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit();
        }
        $error_message = "Invalid status selected.";
    }
}

// AJAX: Save editable sections for admin
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_customer_info') {
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $billing = trim($_POST['billing_address'] ?? '');
    
    // Update customer info and automatically approve the order
    $sql = "UPDATE bulk_orders SET name = ?, contact = ?, email = ?, billing_address = ?, status = 'approved', admin_updated = NOW() WHERE id = ?"; 
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $contact, $email, $billing, $target_id);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    
    // Log the activity
    if ($ok) {
        logAdminActivity($conn, 'UPDATE', "Updated customer info for bulk order #$target_id and auto-approved order", 'bulk_orders', $target_id);
        
        // Get order details for notification
        $order_info_sql = "SELECT user_id, unique_order_id FROM bulk_orders WHERE id = ?";
        $order_info_stmt = mysqli_prepare($conn, $order_info_sql);
        mysqli_stmt_bind_param($order_info_stmt, "i", $target_id);
        mysqli_stmt_execute($order_info_stmt);
        $order_info_result = mysqli_stmt_get_result($order_info_stmt);
        $order_info = mysqli_fetch_assoc($order_info_result);
        mysqli_stmt_close($order_info_stmt);
        
        // Send approval email to customer
        try {
            sendBulkOrderApprovalEmail($target_id, $conn);
        } catch (Exception $e) {
            error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
        }
        
        // Create user notification for approval
        if ($order_info) {
            try {
                $notificationHandler = new NotificationHandler($conn);
                $notificationHandler->createUserBulkOrderNotification(
                    $order_info['user_id'],
                    $target_id,
                    'bulk_approved',
                    $order_info['unique_order_id']
                );
                error_log("✓ User notification created for bulk order #$target_id approval");
            } catch (Exception $e) {
                error_log("Failed to create approval notification: " . $e->getMessage());
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed'), 'new_status' => 'approved']);
    exit();
}

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_order_details') {
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $purpose = trim($_POST['purpose'] ?? '');
    $date_needed = $_POST['date_needed'] ?? null;
    $time_needed = $_POST['time_needed'] ?? null;
    $delivery = trim($_POST['delivery_address'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    // Update order details and automatically approve the order
    $sql = "UPDATE bulk_orders SET purpose = ?, date_needed = ?, time_needed = ?, delivery_address = ?, admin_notes = ?, status = 'approved', admin_updated = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $purpose, $date_needed, $time_needed, $delivery, $admin_notes, $target_id);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    
    // Log the activity
    if ($ok) {
        logAdminActivity($conn, 'UPDATE', "Updated order details for bulk order #$target_id and auto-approved order", 'bulk_orders', $target_id);
        
        // Get order details for notification
        $order_info_sql = "SELECT user_id, unique_order_id FROM bulk_orders WHERE id = ?";
        $order_info_stmt = mysqli_prepare($conn, $order_info_sql);
        mysqli_stmt_bind_param($order_info_stmt, "i", $target_id);
        mysqli_stmt_execute($order_info_stmt);
        $order_info_result = mysqli_stmt_get_result($order_info_stmt);
        $order_info = mysqli_fetch_assoc($order_info_result);
        mysqli_stmt_close($order_info_stmt);
        
        // Send approval email to customer
        try {
            sendBulkOrderApprovalEmail($target_id, $conn);
        } catch (Exception $e) {
            error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
        }
        
        // Create user notification for approval
        if ($order_info) {
            try {
                $notificationHandler = new NotificationHandler($conn);
                $notificationHandler->createUserBulkOrderNotification(
                    $order_info['user_id'],
                    $target_id,
                    'bulk_approved',
                    $order_info['unique_order_id']
                );
                error_log("✓ User notification created for bulk order #$target_id approval");
            } catch (Exception $e) {
                error_log("Failed to create approval notification: " . $e->getMessage());
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed'), 'new_status' => 'approved']);
    exit();
}

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_all') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $billing = trim($_POST['billing_address'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $date_needed = $_POST['date_needed'] ?? null;
    $time_needed = $_POST['time_needed'] ?? null;
    $delivery = trim($_POST['delivery_address'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $ok = false; $err='';
    if ($orderId) {
        // Update all fields and automatically approve the order
        $stmt = $conn->prepare("UPDATE bulk_orders SET name=?, contact=?, email=?, billing_address=?, purpose=?, date_needed=?, time_needed=?, delivery_address=?, admin_notes=?, status='approved', admin_updated=NOW(), updated_at=NOW() WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sssssssssi', $name, $contact, $email, $billing, $purpose, $date_needed, $time_needed, $delivery, $admin_notes, $orderId);
            $ok = $stmt->execute();
            if (!$ok) { $err = $stmt->error; }
            $stmt->close();
            
            // Log the activity
            if ($ok) {
                logAdminActivity($conn, 'UPDATE', "Updated all order details for bulk order #$orderId and auto-approved order", 'bulk_orders', $orderId);
                
                // Send approval email to customer
                try {
                    sendBulkOrderApprovalEmail($orderId, $conn);
                } catch (Exception $e) {
                    error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
                }
            }
        } else { $err = $conn->error; }
    } else { $err = 'Invalid order id'; }
    header('Content-Type: application/json');
    echo json_encode(['success'=>$ok,'error'=>$err ?: null, 'new_status' => 'approved']);
    exit();
}

// Handle discount price updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_discount_prices') {
    error_log("=== DISCOUNT PRICE UPDATE REQUEST ===");
    error_log("POST data: " . print_r($_POST, true));
    
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : (isset($order_id) ? $order_id : 0);
    $items_data = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
    
    error_log("Target ID: $target_id");
    error_log("Items data: " . print_r($items_data, true));
    
    $ok = true;
    $err = '';
    $discount_total = 0;
    
    if (empty($items_data)) {
        $ok = false;
        $err = 'No items data received';
        error_log("ERROR: No items data");
    } elseif ($target_id <= 0) {
        $ok = false;
        $err = 'Invalid order ID';
        error_log("ERROR: Invalid order ID");
    } else {
        foreach ($items_data as $item_data) {
            $item_id = (int)$item_data['id'];
            $discount_price = floatval($item_data['discount_price'] ?? 0);
            $quantity = intval($item_data['quantity']);
            $retail_price = floatval($item_data['retail_price'] ?? 0);
            
            error_log("Processing item ID: $item_id, discount_price: $discount_price, retail_price: $retail_price, quantity: $quantity");
            
            // Backend validation: Ensure discount price is lower than retail price (cannot be equal or higher)
            if ($discount_price > 0 && $discount_price >= $retail_price) {
                $ok = false;
                $err = "Item #$item_id: Discount price (₱" . number_format($discount_price, 2) . ") must be lower than retail price (₱" . number_format($retail_price, 2) . ")";
                error_log("ERROR: " . $err);
                break;
            }
            
            // Allow NULL for discount_price if it's 0 or empty
            $discount_price_value = $discount_price > 0 ? $discount_price : null;
            
            if ($discount_price > 0) {
                $discount_total += $discount_price * $quantity;
            }
            
            $update_item_sql = "UPDATE bulk_order_items SET discount_price = ? WHERE id = ? AND bulk_order_id = ?";
            $update_item_stmt = mysqli_prepare($conn, $update_item_sql);
            
            if (!$update_item_stmt) {
                $ok = false;
                $err = "Failed to prepare statement: " . mysqli_error($conn);
                error_log("ERROR: " . $err);
                break;
            }
            
            mysqli_stmt_bind_param($update_item_stmt, "dii", $discount_price_value, $item_id, $target_id);
            
            if (!mysqli_stmt_execute($update_item_stmt)) {
                $ok = false;
                $err = "Failed to execute: " . mysqli_error($conn);
                error_log("ERROR: " . $err);
                mysqli_stmt_close($update_item_stmt);
                break;
            }
            
            $affected_rows = mysqli_stmt_affected_rows($update_item_stmt);
            error_log("Updated item $item_id, affected rows: $affected_rows");
            mysqli_stmt_close($update_item_stmt);
        }
        
        // Update discount total in bulk_orders table
        if ($ok) {
            $discount_total_value = $discount_total > 0 ? $discount_total : null;
            error_log("Updating bulk_orders table with discount_total: $discount_total");
            
            // Update discount total and automatically approve the order when discount is applied
            $update_discount_total_sql = "UPDATE bulk_orders SET discount_total = ?, status = 'approved', admin_updated = NOW() WHERE id = ?";
            $update_discount_total_stmt = mysqli_prepare($conn, $update_discount_total_sql);
            
            if (!$update_discount_total_stmt) {
                $ok = false;
                $err = "Failed to prepare bulk_orders update: " . mysqli_error($conn);
                error_log("ERROR: " . $err);
            } else {
                mysqli_stmt_bind_param($update_discount_total_stmt, "di", $discount_total_value, $target_id);
                $ok = mysqli_stmt_execute($update_discount_total_stmt);
                
                if (!$ok) { 
                    $err = "Failed to update bulk_orders: " . mysqli_error($conn);
                    error_log("ERROR: " . $err);
                } else {
                    error_log("Successfully updated bulk_orders, affected rows: " . mysqli_stmt_affected_rows($update_discount_total_stmt));
                }
                
                mysqli_stmt_close($update_discount_total_stmt);
                
                // Log the activity
                if ($ok) {
                    logAdminActivity($conn, 'UPDATE', "Updated discount pricing for bulk order #$target_id (Discount Total: ₱" . number_format($discount_total, 2) . ") and auto-approved order", 'bulk_orders', $target_id);
                    error_log("Activity logged successfully");
                    
                    // Send approval email to customer
                    try {
                        sendBulkOrderApprovalEmail($target_id, $conn);
                        
                        // Create user notification for approval
                        $order_info_sql = "SELECT user_id, unique_order_id FROM bulk_orders WHERE id = ?";
                        $order_info_stmt = mysqli_prepare($conn, $order_info_sql);
                        mysqli_stmt_bind_param($order_info_stmt, "i", $target_id);
                        mysqli_stmt_execute($order_info_stmt);
                        $order_info_result = mysqli_stmt_get_result($order_info_stmt);
                        $order_info = mysqli_fetch_assoc($order_info_result);
                        mysqli_stmt_close($order_info_stmt);
                        
                        if ($order_info) {
                            require_once __DIR__ . '/../../api/notification.php';
                            $notificationHandler = new NotificationHandler($conn);
                            $notificationHandler->createUserBulkOrderNotification(
                                $order_info['user_id'],
                                $target_id,
                                'bulk_approved',
                                $order_info['unique_order_id']
                            );
                        }
                    } catch (Exception $e) {
                        error_log("Failed to send bulk order approval email to customer: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok, 'error' => $ok ? null : ($err ?: 'Update failed'), 'discount_total' => $discount_total, 'new_status' => 'approved']);
    exit();
}

// Handle item price updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_item_prices') {
    $target_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : $order_id;
    $items_data = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
    
    $ok = true;
    $err = '';
    $total_amount = 0;
    
    if (!empty($items_data)) {
        foreach ($items_data as $item_data) {
            $item_id = (int)$item_data['id'];
            $price = floatval($item_data['price']);
            $quantity = intval($item_data['quantity']);
            $subtotal = $price * $quantity;
            $total_amount += $subtotal;
            
            $update_item_sql = "UPDATE bulk_order_items SET product_price = ?, subtotal = ? WHERE id = ? AND bulk_order_id = ?";
            $update_item_stmt = mysqli_prepare($conn, $update_item_sql);
            mysqli_stmt_bind_param($update_item_stmt, "ddii", $price, $subtotal, $item_id, $target_id);
            if (!mysqli_stmt_execute($update_item_stmt)) {
                $ok = false;
                $err = mysqli_error($conn);
                break;
            }
            mysqli_stmt_close($update_item_stmt);
        }
        
        // Update total amount in bulk_orders table
        if ($ok) {
            $update_total_sql = "UPDATE bulk_orders SET total_amount = ?, admin_updated = NOW() WHERE id = ?";
            $update_total_stmt = mysqli_prepare($conn, $update_total_sql);
            mysqli_stmt_bind_param($update_total_stmt, "di", $total_amount, $target_id);
            $ok = mysqli_stmt_execute($update_total_stmt);
            if (!$ok) { $err = mysqli_error($conn); }
            mysqli_stmt_close($update_total_stmt);
            
            // Log the activity
            if ($ok) {
                logAdminActivity($conn, 'UPDATE', "Updated pricing for bulk order #$target_id (Total: ₱" . number_format($total_amount, 2) . ")", 'bulk_orders', $target_id);
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok, 'error' => $ok ? null : ($err ?: 'Update failed'), 'total' => $total_amount]);
    exit();
}

// Fetch bulk order details
$order_sql = "SELECT bo.*, u.firstname, u.lastname, u.username, u.email as user_email 
              FROM bulk_orders bo
              LEFT JOIN users u ON bo.user_id = u.id 
              WHERE bo.id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("Location: bulk-order-lists.php?error=Order not found");
    exit();
}

// Fetch order items with IDs for editing
$items_sql = "SELECT * FROM bulk_order_items WHERE bulk_order_id = ? ORDER BY id";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $order['id']);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

// Calculate totals
$total_items = 0;
$total_amount = 0;
$items = [];

while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
    $total_items += $item['quantity'];
    $total_amount += $item['subtotal'];
}

$user_name = $order['firstname'] && $order['lastname'] 
    ? $order['firstname'] . ' ' . $order['lastname'] 
    : ($order['username'] ?: 'Guest User');

$order_id_display = $order['unique_order_id'] ? $order['unique_order_id'] : str_pad($order['id'], 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Details - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="bulk-order.css">
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    <?php include __DIR__ . '/../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>

    
    <div class="bulk-order-detail-container">
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Bulk Order Form: #<?php echo htmlspecialchars($order_id_display); ?></h1>
                </div>
                <div class="header-controls">
                    <!-- Status Update -->
                    <div class="status-control">
                        <form method="POST" action="update-bulk-status.php" class="status-form" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                            <label for="new_status">Status:</label>
                            <select name="status" id="new_status" class="status-select" onchange="this.form.submit()">
                                <?php $statuses = [
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'payment_received' => 'Payment Received',
                                    'payment_rejected' => 'Payment Rejected',
                                    'ready_for_delivery' => 'Ready for Delivery',
                                    'ready_for_pickup' => 'Ready for Pickup',
                                    'cancelled' => 'Cancelled',
                                    'rejected' => 'Rejected',
                                    'completed' => 'Completed',
                                ];
                                foreach ($statuses as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo ($order['status'] === $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn btn-secondary btn-xs" id="toggleAllEdit">
                            <i class="fas fa-pen"></i> <span class="btn-text">Edit Information</span>
                        </button>
                        <button class="btn btn-primary btn-xs" id="saveAllBtn" disabled>
                            <i class="fas fa-save"></i> <span class="btn-text">Save</span>
                        </button>
                        <span id="all-saved" class="saved-indicator"><i class="fas fa-check"></i> Saved</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success/Error Notifications -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message) && !empty(trim($error_message))): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Order Information Grid -->
        <div class="order-info-grid">
            <!-- Customer Information -->
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <!-- View mode -->
                <div class="info-grid view-mode">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value" id="view_name">
                            <?php echo htmlspecialchars($order['name']); ?>
                            <?php if (!empty($order['username'])): ?>
                                <br><small>@<?php echo htmlspecialchars($order['username']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value" id="view_contact"><?php echo htmlspecialchars($order['contact']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value" id="view_email"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Billing Address</div>
                        <div class="info-value" id="view_billing"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></div>
                    </div>
                </div>
                <!-- Edit mode -->
                <div class="info-grid edit-mode hidden">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">
                            <input class="readonly-price-input" type="text" value="<?php echo htmlspecialchars($order['name']); ?>" readonly title="Customer name cannot be edited">
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value">
                            <input class="readonly-price-input" type="text" value="<?php echo htmlspecialchars($order['contact']); ?>" readonly title="Contact number cannot be edited">
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value">
                            <input class="readonly-price-input" type="email" value="<?php echo htmlspecialchars($order['email']); ?>" readonly title="Email address cannot be edited">
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Billing Address</div>
                        <div class="info-value"><textarea class="editable-input" id="cust_billing" rows="3"><?php echo htmlspecialchars($order['billing_address']); ?></textarea></div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="info-card">
                <h3><i class="fas fa-clipboard-list"></i> Order Details</h3>
                <!-- View mode -->
                <div class="info-grid two-column view-mode">
                    <!-- First Column -->
                    <div class="column-left">
                        <div class="info-item">
                            <div class="info-label">Order Type</div>
                            <div class="info-value">
                                <span class="order-type-badge <?php echo $order['order_type']; ?>">
                                    <?php echo ucfirst($order['order_type']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($order['order_type'] == 'delivery' && $order['delivery_address']): ?>
                        <div class="info-item">
                            <div class="info-label">Delivery Address</div>
                            <div class="info-value" id="view_delivery"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Date Submitted</div>
                            <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date Needed</div>
                            <div class="info-value" id="view_date_needed"><?php echo date('F j, Y', strtotime($order['date_needed'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time Needed</div>
                            <div class="info-value" id="view_time_needed"><?php echo date('g:i A', strtotime($order['time_needed'])); ?></div>
                        </div>
                    </div>
                    
                    <!-- Second Column -->
                    <div class="column-right">
                        <div class="info-item">
                            <div class="info-label">Purpose of Order</div>
                            <div class="info-value" id="view_purpose"><?php echo nl2br(htmlspecialchars($order['purpose'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Items</div>
                            <div class="info-value"><?php echo number_format($total_items); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Total Amount</div>
                            <div class="info-value total-amount" id="orderDetailsTotalAmount">₱<?php echo number_format($total_amount, 2); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Status</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Edit mode -->
                <div class="info-grid two-column edit-mode hidden">
                    <div class="column-left">
                        <div class="info-item">
                            <div class="info-label">Order Type</div>
                            <div class="info-value">
                                <span class="order-type-badge <?php echo $order['order_type']; ?>"><?php echo ucfirst($order['order_type']); ?></span>
                            </div>
                        </div>
                        <?php if ($order['order_type'] == 'delivery'): ?>
                        <div class="info-item">
                            <div class="info-label">Delivery Address</div>
                            <div class="info-value"><textarea class="editable-input" id="order_delivery" rows="3"><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea></div>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Date Needed</div>
                            <div class="info-value"><input class="editable-input" id="order_date_needed" type="date" value="<?php echo htmlspecialchars($order['date_needed']); ?>"></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Time Needed</div>
                            <div class="info-value"><input class="editable-input" id="order_time_needed" type="time" value="<?php echo htmlspecialchars(substr($order['time_needed'],0,5)); ?>"></div>
                        </div>
                    </div>
                    <div class="column-right">
                        <div class="info-item">
                            <div class="info-label">Purpose of Order</div>
                            <div class="info-value"><textarea class="editable-input" id="order_purpose" rows="3"><?php echo htmlspecialchars($order['purpose']); ?></textarea></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Admin Notes</div>
                            <div class="info-value"><textarea class="editable-input" id="order_admin_notes" rows="3" placeholder="Notes visible to the customer (optional)"><?php echo htmlspecialchars($order['admin_notes'] ?? ''); ?></textarea></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Notes -->
        <?php if (!empty($order['note'])): ?>
        <div class="notes-section">
            <div class="notes-header">
                <i class="fas fa-sticky-note"></i> Customer Notes
            </div>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($order['note'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin Notes -->
        <?php if (!empty($order['admin_notes'])): ?>
        <div class="notes-section admin-notes-section">
            <div class="notes-header">
                <i class="fas fa-user-shield"></i> Admin Notes
            </div>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($order['admin_notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <div class="items-table-container">
            <form method="POST" action="">
                <input type="hidden" name="order_id" value="<?php echo (int)$order_id; ?>">
                <div class="card-header-flex">
                    <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                    <div class="pricing-controls">
                        <button type="submit" name="save_discount_prices" class="btn btn-primary btn-xs">
                            <i class="fas fa-percentage"></i> Save Discount Prices
                        </button>
                    </div>
                </div>
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th style="width: 120px;">Retail Price (₱)</th>
                        <th style="width: 150px;">Discount Price (₱)</th>
                        <th style="width: 50px;">Qty</th>
                        <th style="width: 120px;">Regular Subtotal (₱)</th>
                        <th style="width: 120px;">Discounted Subtotal (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $item): ?>
                        <tr data-item-id="<?php echo $item['id']; ?>">
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td>
                                <input type="number" 
                                       class="readonly-price-input" 
                                       value="<?php echo number_format($item['product_price'], 2, '.', ''); ?>" 
                                       readonly
                                       title="Retail price from customer form (read-only)">
                            </td>
                            <td>
                                <input type="number" 
                                       name="discount_price_<?php echo $item['id']; ?>"
                                       class="editable-input discount-price-input" 
                                       data-item-id="<?php echo $item['id']; ?>"
                                       data-retail-price="<?php echo number_format($item['product_price'], 2, '.', ''); ?>"
                                       value="<?php echo $item['discount_price'] ? number_format($item['discount_price'], 2, '.', '') : ''; ?>" 
                                       min="0" 
                                       max="<?php echo number_format($item['product_price'], 2, '.', ''); ?>"
                                       step="0.01"
                                       placeholder="Enter discount price"
                                       onchange="validateAndCalculateDiscountSubtotal(<?php echo $item['id']; ?>)"
                                       oninput="validateDiscountPrice(this)">
                                <!-- Hidden fields for form processing -->
                                <input type="hidden" name="retail_price_<?php echo $item['id']; ?>" value="<?php echo number_format($item['product_price'], 2, '.', ''); ?>">
                                <input type="hidden" name="quantity_<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>">
                            </td>
                            <td class="quantity-cell"><?php echo number_format($item['quantity']); ?></td>
                            <td class="regular-subtotal-cell" data-item-id="<?php echo $item['id']; ?>">
                                ₱<?php echo number_format($item['subtotal'], 2); ?>
                            </td>
                            <td class="discount-subtotal-cell" data-item-id="<?php echo $item['id']; ?>">
                                <?php 
                                if ($item['discount_price']) {
                                    echo '₱' . number_format($item['discount_price'] * $item['quantity'], 2);
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666;">No items found for this order</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>Regular Total:</strong></td>
                        <td id="grandTotalCell"><strong>₱<?php echo number_format($total_amount, 2); ?></strong></td>
                        <td><span class="text-muted">-</span></td>
                    </tr>
                    <tr class="discount-total-row">
                        <td colspan="4"><strong>Final Total (with discounts):</strong></td>
                        <td><span class="text-muted">-</span></td>
                        <td id="discountTotalCell">
                            <strong>
                                <?php 
                                $final_total = 0;
                                $has_any_discount = false;
                                foreach ($items as $item) {
                                    if ($item['discount_price']) {
                                        // Use discount price for items with discount
                                        $final_total += $item['discount_price'] * $item['quantity'];
                                        $has_any_discount = true;
                                    } else {
                                        // Use regular price for items without discount
                                        $final_total += $item['product_price'] * $item['quantity'];
                                    }
                                }
                                if ($has_any_discount && $final_total > 0) {
                                    echo '₱' . number_format($final_total, 2);
                                } else {
                                    echo '<span class="text-muted">No discounts applied</span>';
                                }
                                ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
            </form>
        </div>

        <!-- Payment Proofs -->
        <?php if ($order['status'] != 'rejected' && $order['status'] != 'pending'): ?>
        <div class="payment-status">
            <h3><i class="fas fa-credit-card"></i> Payment Proofs</h3>
            <?php 
            $proofs = [];
            if (!empty($order['proof_of_payment'])) {
                $decoded = json_decode($order['proof_of_payment'], true);
                if (is_array($decoded)) {
                    $proofs = $decoded;
                } else {
                    $proofs = [[
                        'filename' => $order['proof_of_payment'],
                        'type' => 'full',
                        'uploaded_at' => 'Unknown',
                        'original_name' => $order['proof_of_payment']
                    ]];
                }
            }
            ?>
            <?php if (!empty($proofs)): ?>
                <div class="proofs-list">
                    <?php foreach ($proofs as $pf): 
                        // Prioritize cloud_url over legacy filename
                        if (!empty($pf['cloud_url'])) {
                            $file = $pf['cloud_url'];
                        } else {
                            // Fallback to local file for backward compatibility
                            $file = "../../../assets/bulk_payments/" . $pf['filename'];
                        }
                        $ext = strtolower(pathinfo($pf['filename'], PATHINFO_EXTENSION));
                        $payment_type = isset($pf['type']) ? ucfirst($pf['type']) : 'Full';
                    ?>
                    <div class="proof-card">
                        <div class="proof-header">
                            <span class="payment-type-badge">
                                <i class="fas fa-receipt"></i> <?php echo htmlspecialchars($payment_type); ?> Payment
                            </span>
                            <span class="proof-date"><?php echo htmlspecialchars($pf['uploaded_at'] ?? 'Unknown'); ?></span>
                        </div>
                        <div class="proof-preview">
                            <?php if (in_array($ext, ['jpg','jpeg','png'])): ?>
                                <img src="<?php echo htmlspecialchars($file); ?>" alt="Proof of payment" class="proof-thumb" onclick="openImageModal(this.src)">
                            <?php else: ?>
                                <a class="btn btn-secondary" href="<?php echo htmlspecialchars($file); ?>" target="_blank"><i class="fas fa-file-pdf"></i> View PDF</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="payment-info">
                    <div class="payment-icon payment-awaiting"><i class="fas fa-clock"></i></div>
                    <div><strong>No payment proof submitted</strong></div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <style>
        /* Alert Styles */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #f0fdf4;
            border-color: #16a34a;
            color: #166534;
        }
        .alert-error {
            background-color: #fef2f2;
            border-color: #dc2626;
            color: #991b1b;
        }
        
        .editable-input { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; width: 100%; background: #fffdf7; }
        .readonly-price-input { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; width: 100%; background: #f9fafb; color: #6b7280; cursor: not-allowed; }
        .discount-price-input { border: 1px solid #059669; border-radius: 6px; padding: 8px; width: 100%; background: #ecfdf5; transition: all 0.2s; }
        .discount-price-input:focus { border-color: #047857; box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.1); }
        .discount-price-input:invalid { border-color: #dc2626; background-color: #fee2e2; }
        .edit-actions { margin-top: 10px; }
        .card-header-flex { display:flex; align-items:center; justify-content:space-between; gap: 10px; }
        .btn-xs { padding: 4px 8px; font-size: 12px; }
        .header-actions { display:flex; gap:8px; align-items:center; }
        .hidden { display: none !important; }
        .proofs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .proofs-list { display: flex; flex-direction: column; gap: 12px; }
        .proof-card { 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 12px; 
            background: #fafbfc;
            transition: all 0.2s ease;
        }
        .proof-card:hover { 
            border-color: #d1d5db; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .proof-header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            margin-bottom: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .payment-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .proof-date { 
            color: #6b7280; 
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .proof-preview { 
            border-radius: 6px; 
            overflow: hidden;
        }
        .proof-thumb { width: 100%; height: 120px; object-fit: cover; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .image-modal { display:none; position: fixed; z-index: 9999; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.75); align-items:center; justify-content:center; }
        .image-modal img { max-width: 90%; max-height: 90%; border-radius: 8px; }
        .image-modal .close { position:absolute; top:20px; right:24px; font-size:28px; color:#fff; cursor:pointer; }
        .discount-total-row { background-color: #f0fdf4; font-weight: bold; }
        .pricing-controls { display: flex; gap: 10px; align-items: center; }
        
        /* Responsive Header Styles */
        .header-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .header-title h1 {
            margin: 0;
            font-size: 1.5rem;
            white-space: nowrap;
        }
        
        .header-controls {
            display: flex;
            flex-direction: row;
            gap: 12px;
            min-width: 300px;
        }
        
        .status-control {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .status-control label {
            font-weight: 500;
            white-space: nowrap;
        }
        
        .status-select {
            padding: 4px 8px;
            font-size: 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            min-width: 150px;
        }
        
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .saved-indicator {
            display: none;
            color: #16a34a;
            font-size: 12px;
            white-space: nowrap;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .header-title h1 {
                font-size: 1.25rem;
                white-space: normal;
                text-align: center;
            }
            
            .header-controls {
                flex-direction: row;
                min-width: auto;
                width: 100%;
            }
            
            .status-control {
                justify-content: center;
                border-radius: 6px;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .btn-text {
                display: none;
            }
            
            .btn {
                padding: 8px 12px;
            }
        }
        
        /* Small Mobile */
        @media (max-width: 480px) {

            .card-header-flex{
            }
            

            .header-controls {
                flex-direction: column;
                min-width: auto;
                width: 100%;
            }
            .status-control {
                flex-direction: row;
                gap: 4px;
                text-align: center;
            }
            
            .status-select {
                min-width: auto;
                width: 100%;
                max-width: 200px;
            }
            
            .action-buttons {
                flex-direction: row;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
                max-width: 100px;
            }
        }
    </style>
    <div id="imgModal" class="image-modal" onclick="closeImageModal(event)">
        <span class="close" onclick="closeImageModal(event)">&times;</span>
        <img id="imgModalContent" src="" alt="Preview" />
    </div>
    <script>

        // Single toggle for both sections
        (function(){
            const toggle = document.getElementById('toggleAllEdit');
            const saveBtn = document.getElementById('saveAllBtn');
            const savedIndicator = document.getElementById('all-saved');
            const viewBlocks = document.querySelectorAll('.view-mode');
            const editBlocks = document.querySelectorAll('.edit-mode');
            let editing = false;
            if (toggle) {
                toggle.addEventListener('click', () => {
                    editing = !editing;
                    viewBlocks.forEach(b => b.classList.toggle('hidden', editing));
                    editBlocks.forEach(b => b.classList.toggle('hidden', !editing));
                    saveBtn.disabled = !editing;
                    toggle.innerHTML = editing ? '<i class="fas fa-ban"></i> Cancel' : '<i class="fas fa-pen"></i> Edit';
                });
            }
            if (saveBtn) {
                saveBtn.addEventListener('click', async () => {
                    const form = new FormData();
                    form.append('action', 'save_all');
                    form.append('is_ajax', '1');
                    form.append('order_id', '<?php echo (int)$order['id']; ?>');
                    // Customer - use original values from PHP since these are readonly
                    form.append('name', '<?php echo addslashes($order['name']); ?>');
                    form.append('contact', '<?php echo addslashes($order['contact']); ?>');
                    form.append('email', '<?php echo addslashes($order['email']); ?>');
                    form.append('billing_address', document.getElementById('cust_billing').value);
                    // Order
                    const deliveryEl = document.getElementById('order_delivery');
                    form.append('delivery_address', deliveryEl ? deliveryEl.value : '');
                    form.append('date_needed', document.getElementById('order_date_needed').value);
                    form.append('time_needed', document.getElementById('order_time_needed').value);
                    form.append('purpose', document.getElementById('order_purpose').value);
                    form.append('admin_notes', document.getElementById('order_admin_notes').value);
                    try {
                        const res = await fetch('', { method: 'POST', body: form });
                        const data = await res.json();
                        if (data.success) {
                            // Update view text
                            const nameV = document.getElementById('view_name');
                            const contactV = document.getElementById('view_contact');
                            const emailV = document.getElementById('view_email');
                            const billingV = document.getElementById('view_billing');
                            const deliveryV = document.getElementById('view_delivery');
                            const dateV = document.getElementById('view_date_needed');
                            const timeV = document.getElementById('view_time_needed');
                            const purposeV = document.getElementById('view_purpose');
                            if (nameV) {
                                // Name is readonly, no need to update
                            }
                            if (contactV) {
                                // Contact is readonly, no need to update
                            }
                            if (emailV) {
                                // Email is readonly, no need to update
                            }
                            if (billingV) billingV.innerHTML = (document.getElementById('cust_billing').value || '').replace(/\n/g,'<br>');
                            if (deliveryV && deliveryEl) deliveryV.innerHTML = (deliveryEl.value || '').replace(/\n/g,'<br>');
                            // Format date/time for view
                            const dateStr = document.getElementById('order_date_needed').value;
                            const timeStr = document.getElementById('order_time_needed').value;
                            function formatDate(str){ try { const d = new Date(str+'T00:00:00'); return d.toLocaleDateString(undefined, { month:'long', day:'numeric', year:'numeric' }); } catch(e){return str;} }
                            function formatTime(str){ try { const [h,m] = str.split(':'); const d = new Date(); d.setHours(h, m||'0'); return d.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'}); } catch(e){return str;} }
                            if (dateV && dateStr) dateV.textContent = formatDate(dateStr);
                            if (timeV && timeStr) timeV.textContent = formatTime(timeStr);
                            if (purposeV) purposeV.innerHTML = (document.getElementById('order_purpose').value || '').replace(/\n/g,'<br>');
                            // Admin notes block
                            const adminNotesVal = document.getElementById('order_admin_notes').value;
                            const adminSection = document.querySelector('.admin-notes-section');
                            if (adminSection) {
                                const content = adminSection.querySelector('.notes-content');
                                if (adminNotesVal && adminNotesVal.trim()) {
                                    if (content) content.innerHTML = adminNotesVal.replace(/\n/g,'<br>');
                                    adminSection.style.display = '';
                                } else {
                                    adminSection.style.display = 'none';
                                }
                            }
                            
                            // Auto-update status if returned
                            if (data.new_status) {
                                updateStatusUI(data.new_status);
                            }
                            
                            // Toggle back to view mode
                            editing = false;
                            viewBlocks.forEach(b => b.classList.remove('hidden'));
                            editBlocks.forEach(b => b.classList.add('hidden'));
                            saveBtn.disabled = true;
                            if (toggle) toggle.innerHTML = '<i class="fas fa-pen"></i> Edit';
                            savedIndicator.style.display = 'inline-flex';
                            setTimeout(()=> savedIndicator.style.display = 'none', 1500);
                        } else {
                            alert('Save failed: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) { alert('Request failed.'); }
                });
            }
        })();

        // Price calculation functions for bulk order items
        function calculateSubtotal(itemId) {
            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            if (!row) return;
            
            const priceInput = row.querySelector('.price-input');
            const quantityCell = row.querySelector('.quantity-cell');
            const subtotalCell = row.querySelector('.subtotal-cell');
            
            const price = parseFloat(priceInput.value) || 0;
            const quantity = parseInt(quantityCell.textContent.replace(/,/g, '')) || 0;
            const subtotal = price * quantity;
            
            subtotalCell.textContent = '₱' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            calculateGrandTotal();
        }

        // Validate discount price on input
        function validateDiscountPrice(input) {
            const retailPrice = parseFloat(input.getAttribute('data-retail-price')) || 0;
            const discountPrice = parseFloat(input.value) || 0;
            
            if (discountPrice > 0 && discountPrice >= retailPrice) {
                input.style.borderColor = '#dc2626';
                input.style.backgroundColor = '#fee2e2';
                input.setCustomValidity('Discount price must be lower than retail price (₱' + retailPrice.toFixed(2) + ')');
            } else {
                input.style.borderColor = '#059669';
                input.style.backgroundColor = '#ecfdf5';
                input.setCustomValidity('');
            }
        }
        
        function validateAndCalculateDiscountSubtotal(itemId) {
            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            if (!row) return;
            
            const discountPriceInput = row.querySelector('.discount-price-input');
            const quantityCell = row.querySelector('.quantity-cell');
            const discountSubtotalCell = row.querySelector('.discount-subtotal-cell');
            
            const retailPrice = parseFloat(discountPriceInput.getAttribute('data-retail-price')) || 0;
            const discountPrice = parseFloat(discountPriceInput.value) || 0;
            const quantity = parseInt(quantityCell.textContent.replace(/,/g, '')) || 0;
            
            // Validate discount price - must be lower than retail price (cannot be equal or higher)
            if (discountPrice > 0 && discountPrice >= retailPrice) {
                alert('Discount price (₱' + discountPrice.toFixed(2) + ') must be lower than retail price (₱' + retailPrice.toFixed(2) + ')');
                discountPriceInput.value = '';
                discountPriceInput.style.borderColor = '#dc2626';
                discountPriceInput.style.backgroundColor = '#fee2e2';
                discountSubtotalCell.innerHTML = '<span class="text-muted">-</span>';
                calculateDiscountTotal();
                return;
            }
            
            // Reset validation styling
            discountPriceInput.style.borderColor = '#059669';
            discountPriceInput.style.backgroundColor = '#ecfdf5';
            
            if (discountPrice > 0) {
                const discountSubtotal = discountPrice * quantity;
                discountSubtotalCell.innerHTML = '₱' + discountSubtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            } else {
                discountSubtotalCell.innerHTML = '<span class="text-muted">-</span>';
            }
            
            calculateDiscountTotal();
        }
        
        function calculateDiscountSubtotal(itemId) {
            validateAndCalculateDiscountSubtotal(itemId);
        }
        
        function calculateGrandTotal() {
            const subtotalCells = document.querySelectorAll('.regular-subtotal-cell');
            let total = 0;
            
            subtotalCells.forEach(cell => {
                const value = parseFloat(cell.textContent.replace(/₱|,/g, '')) || 0;
                total += value;
            });
            
            const grandTotalCell = document.getElementById('grandTotalCell');
            grandTotalCell.innerHTML = '<strong>₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong>';
        }

        function calculateDiscountTotal() {
            const discountSubtotalCells = document.querySelectorAll('.discount-subtotal-cell');
            const regularSubtotalCells = document.querySelectorAll('.regular-subtotal-cell');
            const rows = document.querySelectorAll('#itemsTable tbody tr[data-item-id]');
            
            let finalTotal = 0;
            let hasAnyDiscount = false;
            
            rows.forEach((row, index) => {
                const discountSubtotalCell = row.querySelector('.discount-subtotal-cell');
                const regularSubtotalCell = row.querySelector('.regular-subtotal-cell');
                
                const discountText = discountSubtotalCell.textContent || discountSubtotalCell.innerText;
                
                // Check if this item has a discount applied
                if (discountText && discountText.includes('₱')) {
                    // Use discounted subtotal
                    const discountValue = parseFloat(discountText.replace(/₱|,/g, '')) || 0;
                    finalTotal += discountValue;
                    hasAnyDiscount = true;
                } else {
                    // Use regular subtotal for items without discount
                    const regularText = regularSubtotalCell.textContent || regularSubtotalCell.innerText;
                    const regularValue = parseFloat(regularText.replace(/₱|,/g, '')) || 0;
                    finalTotal += regularValue;
                }
            });
            
            const discountTotalCell = document.getElementById('discountTotalCell');
            if (hasAnyDiscount && finalTotal > 0) {
                discountTotalCell.innerHTML = '<strong>₱' + finalTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong>';
            } else {
                discountTotalCell.innerHTML = '<strong><span class="text-muted">No discounts applied</span></strong>';
            }
        }
        
        // Save prices button handler
        (function() {
            const savePricesBtn = document.getElementById('savePricesBtn');
            if (savePricesBtn) {
                savePricesBtn.addEventListener('click', async () => {
                    const itemsData = [];
                    const rows = document.querySelectorAll('#itemsTable tbody tr[data-item-id]');
                    
                    rows.forEach(row => {
                        const itemId = row.getAttribute('data-item-id');
                        const priceInput = row.querySelector('.price-input');
                        const quantityCell = row.querySelector('.quantity-cell');
                        
                        if (itemId && priceInput && quantityCell) {
                            itemsData.push({
                                id: parseInt(itemId),
                                price: parseFloat(priceInput.value) || 0,
                                quantity: parseInt(quantityCell.textContent.replace(/,/g, '')) || 0
                            });
                        }
                    });
                    
                    if (itemsData.length === 0) {
                        alert('No items to save.');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'update_item_prices');
                    formData.append('order_id', '<?php echo (int)$order_id; ?>');
                    formData.append('items', JSON.stringify(itemsData));
                    
                    try {
                        savePricesBtn.disabled = true;
                        savePricesBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                        
                        const res = await fetch('', { method: 'POST', body: formData });
                        const data = await res.json();
                        
                        if (data.success) {
                            savePricesBtn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                            setTimeout(() => {
                                savePricesBtn.innerHTML = '<i class="fas fa-save"></i> Save Prices';
                                savePricesBtn.disabled = false;
                            }, 1500);
                            
                            // Update the grand total in items table footer
                            const grandTotalCell = document.getElementById('grandTotalCell');
                            grandTotalCell.innerHTML = '<strong>₱' + parseFloat(data.total).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong>';
                            
                            // Update the total amount in Order Details section
                            const orderDetailsTotalAmount = document.getElementById('orderDetailsTotalAmount');
                            if (orderDetailsTotalAmount) {
                                orderDetailsTotalAmount.textContent = '₱' + parseFloat(data.total).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                            }
                        } else {
                            alert('Failed to save prices: ' + (data.error || 'Unknown error'));
                            savePricesBtn.innerHTML = '<i class="fas fa-save"></i> Save Prices';
                            savePricesBtn.disabled = false;
                        }
                    } catch (e) {
                        alert('Request failed: ' + e.message);
                        savePricesBtn.innerHTML = '<i class="fas fa-save"></i> Save Prices';
                        savePricesBtn.disabled = false;
                    }
                });
            }
        })();
        
        // Save discount prices button handler
        (function() {
            const saveDiscountPricesBtn = document.getElementById('saveDiscountPricesBtn');
            if (saveDiscountPricesBtn) {
                saveDiscountPricesBtn.addEventListener('click', async () => {
                    console.log('Save discount prices button clicked');
                    
                    const itemsData = [];
                    const rows = document.querySelectorAll('#itemsTable tbody tr[data-item-id]');
                    
                    console.log('Found rows:', rows.length);
                    
                    rows.forEach((row, index) => {
                        const itemId = row.getAttribute('data-item-id');
                        const discountPriceInput = row.querySelector('.discount-price-input');
                        const quantityCell = row.querySelector('.quantity-cell');
                        
                        console.log(`Row ${index}:`, {
                            itemId: itemId,
                            hasDiscountInput: !!discountPriceInput,
                            hasQuantityCell: !!quantityCell,
                            discountValue: discountPriceInput ? discountPriceInput.value : 'null',
                            quantityText: quantityCell ? quantityCell.textContent : 'null'
                        });
                        
                        if (itemId && discountPriceInput && quantityCell) {
                            const retailPrice = parseFloat(discountPriceInput.getAttribute('data-retail-price')) || 0;
                            const discountPrice = parseFloat(discountPriceInput.value) || 0;
                            const quantity = parseInt(quantityCell.textContent.replace(/,/g, '')) || 0;
                            
                            // Validate discount price before adding to data - must be lower than retail price
                            if (discountPrice > 0 && discountPrice >= retailPrice) {
                                alert(`Item #${itemId}: Discount price (₱${discountPrice.toFixed(2)}) must be lower than retail price (₱${retailPrice.toFixed(2)})`);
                                discountPriceInput.focus();
                                throw new Error('Invalid discount price');
                            }
                            
                            itemsData.push({
                                id: parseInt(itemId),
                                discount_price: discountPrice,
                                quantity: quantity,
                                retail_price: retailPrice
                            });
                        }
                    });
                    
                    console.log('Items data collected:', itemsData);
                    
                    if (itemsData.length === 0) {
                        console.error('No items found to save');
                        alert('No items to save. Please check if the table has loaded properly and try refreshing the page.');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'update_discount_prices');
                    formData.append('order_id', '<?php echo (int)$order_id; ?>');
                    formData.append('items', JSON.stringify(itemsData));
                    
                    console.log('Sending form data:', {
                        action: 'update_discount_prices',
                        order_id: '<?php echo (int)$order_id; ?>',
                        items: JSON.stringify(itemsData)
                    });
                    
                    try {
                        saveDiscountPricesBtn.disabled = true;
                        saveDiscountPricesBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                        
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        console.log('Response status:', response.status);
                        const responseText = await response.text();
                        console.log('Raw response:', responseText);
                        
                        let data;
                        try {
                            data = JSON.parse(responseText);
                            console.log('Parsed response data:', data);
                        } catch (parseError) {
                            console.error('JSON parse error:', parseError);
                            console.error('Response was not valid JSON:', responseText);
                            alert('Server returned invalid response. Check console for details.');
                            saveDiscountPricesBtn.innerHTML = '<i class="fas fa-percentage"></i> Save Discount Prices';
                            saveDiscountPricesBtn.disabled = false;
                            return;
                        }
                        
                        if (data.success) {
                            saveDiscountPricesBtn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                            setTimeout(() => {
                                saveDiscountPricesBtn.innerHTML = '<i class="fas fa-percentage"></i> Save Discount Prices';
                                saveDiscountPricesBtn.disabled = false;
                            }, 1500);
                            
                            // Auto-update status if returned
                            if (data.new_status) {
                                updateStatusUI(data.new_status);
                            }
                            
                            // Update the discount totals in real-time
                            calculateDiscountTotal();
                        } else {
                            console.error('Server error:', data.error);
                            alert('Failed to save discount prices: ' + (data.error || 'Unknown error'));
                            saveDiscountPricesBtn.innerHTML = '<i class="fas fa-percentage"></i> Save Discount Prices';
                            saveDiscountPricesBtn.disabled = false;
                        }
                    } catch (e) {
                        console.error('Request failed:', e);
                        alert('Request failed: ' + e.message);
                        saveDiscountPricesBtn.innerHTML = '<i class="fas fa-percentage"></i> Save Discount Prices';
                        saveDiscountPricesBtn.disabled = false;
                    }
                });
            } else {
                console.error('Save discount prices button not found');
            }
        })();
        
        // Test AJAX button handler
        (function() {
            const testAjaxBtn = document.getElementById('testAjaxBtn');
            if (testAjaxBtn) {
                testAjaxBtn.addEventListener('click', async () => {
                    console.log('Test AJAX button clicked');
                    
                    const formData = new FormData();
                    formData.append('action', 'test_ajax');
                    
                    try {
                        testAjaxBtn.disabled = true;
                        testAjaxBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
                        
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        console.log('Test response status:', response.status);
                        const responseText = await response.text();
                        console.log('Test raw response:', responseText);
                        
                        const data = JSON.parse(responseText);
                        console.log('Test parsed data:', data);
                        
                        if (data.success) {
                            alert('AJAX Test Successful! Message: ' + data.message);
                            testAjaxBtn.innerHTML = '<i class="fas fa-check"></i> Success!';
                        } else {
                            alert('AJAX Test Failed: ' + (data.error || 'Unknown error'));
                            testAjaxBtn.innerHTML = '<i class="fas fa-times"></i> Failed';
                        }
                        
                        setTimeout(() => {
                            testAjaxBtn.innerHTML = '<i class="fas fa-bug"></i> Test AJAX';
                            testAjaxBtn.disabled = false;
                        }, 2000);
                        
                    } catch (e) {
                        console.error('Test AJAX failed:', e);
                        alert('Test AJAX failed: ' + e.message);
                        testAjaxBtn.innerHTML = '<i class="fas fa-bug"></i> Test AJAX';
                        testAjaxBtn.disabled = false;
                    }
                });
            }
        })();
        
        // Make functions available globally
        window.calculateSubtotal = calculateSubtotal;
        window.calculateDiscountSubtotal = calculateDiscountSubtotal;

        // Function to update status UI elements
        function updateStatusUI(newStatus) {
            // Update the status dropdown
            const statusSelect = document.getElementById('new_status');
            if (statusSelect) {
                statusSelect.value = newStatus;
            }
            
            // Update the status badge in Order Details section
            const statusBadge = document.querySelector('.status-badge');
            if (statusBadge) {
                const statusLabels = {
                    'pending': 'Pending',
                    'approved': 'Approved',
                    'payment_received': 'Payment Received',
                    'payment_rejected': 'Payment Rejected',
                    'ready_for_delivery': 'Ready For Delivery',
                    'cancelled': 'Cancelled',
                    'rejected': 'Rejected',
                    'completed': 'Completed'
                };
                
                statusBadge.textContent = statusLabels[newStatus] || newStatus;
                statusBadge.className = 'status-badge status-' + newStatus.toLowerCase().replace('_', '-');
            }
            
            // Show a notification that status was auto-updated
            const statusNotification = document.createElement('div');
            statusNotification.innerHTML = '<i class="fas fa-check-circle"></i> Order automatically approved!';
            statusNotification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 500;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease-out;
            `;
            
            // Add animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(statusNotification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                statusNotification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (statusNotification.parentNode) {
                        statusNotification.parentNode.removeChild(statusNotification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Make updateStatusUI available globally
        window.updateStatusUI = updateStatusUI;

        // Image modal
        function openImageModal(src) {
            const modal = document.getElementById('imgModal');
            const img = document.getElementById('imgModalContent');
            img.src = src;
            modal.style.display = 'flex';
        }
        function closeImageModal(e) {
            const modal = document.getElementById('imgModal');
            if (!e || e.target === modal || (e.target && e.target.classList && e.target.classList.contains('close'))) {
                modal.style.display = 'none';
                document.getElementById('imgModalContent').src = '';
            }
        }
        window.openImageModal = openImageModal;
        window.closeImageModal = closeImageModal;
    </script>
</body>
</html>
