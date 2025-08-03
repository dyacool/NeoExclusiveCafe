<?php
// Start output buffering to catch any unexpected output
ob_start();

// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Let database.php know we're in an API call
$suppress_db_debug = true;

// Include necessary files
require_once "../../php/includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Function to handle errors and return valid JSON
function handleError($message, $debug = []) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set content type
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => $message,
        'debug' => $debug
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, automatically accept any pending orders
    $accept_query = "UPDATE orders 
                    SET status = 'Pending', 
                        accepted_at = NOW() 
                    WHERE LOWER(status) LIKE LOWER('pending')";
    
    $accept_stmt = $conn->prepare($accept_query);
    if (!$accept_stmt) {
        $conn->rollback();
        handleError("Failed to prepare accept statement: " . $conn->error);
    }
    
    if (!$accept_stmt->execute()) {
        $conn->rollback();
        handleError("Failed to execute accept query: " . $accept_stmt->error);
    }

    // Now get all pending orders
    $query = "SELECT 
                order_id,
                order_date,
                delivery_method as order_type,
                customer_name,
                COALESCE(pickup_date, order_date) as pickup_date,
                COALESCE(delivery_date, NULL) as delivery_date,
                COALESCE(delivery_time, '00:00:00') as pickup_time,
                status
              FROM orders 
              WHERE LOWER(status) LIKE LOWER('pending')
              ORDER BY 
                pickup_date ASC,
                delivery_time ASC,
                order_id ASC
              LIMIT 50";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $conn->rollback();
        handleError("Failed to prepare statement: " . $conn->error, ['query' => $query]);
    }
    
    if (!$stmt->execute()) {
        $conn->rollback();
        handleError("Failed to execute query: " . $stmt->error, ['query' => $query]);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        $conn->rollback();
        handleError("Query execution failed: " . $conn->error, ['query' => $query]);
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Format the dates for display
        if (isset($row['order_date']) && $row['order_date']) {
            $orderDate = new DateTime($row['order_date']);
            $row['order_date'] = $orderDate->format('Y-m-d');
        }
        
        // Format pickup date if it exists
        if (isset($row['pickup_date']) && $row['pickup_date']) {
            $pickupDate = new DateTime($row['pickup_date']);
            $row['pickup_date'] = $pickupDate->format('Y-m-d');
        } else {
            $row['pickup_date'] = 'N/A';
        }
        
        // Format delivery date if it exists
        if (isset($row['delivery_date']) && $row['delivery_date']) {
            $deliveryDate = new DateTime($row['delivery_date']);
            $row['delivery_date'] = $deliveryDate->format('Y-m-d');
        } else {
            $row['delivery_date'] = 'N/A';
        }
        
        // Format time if it exists
        if (isset($row['pickup_time']) && $row['pickup_time'] && $row['pickup_time'] != '00:00:00') {
            try {
                $pickupTime = new DateTime($row['pickup_time']);
                $row['pickup_time'] = $pickupTime->format('h:i A');
            } catch (Exception $e) {
                $row['pickup_time'] = $row['pickup_time'];
            }
        } else {
            $row['pickup_time'] = 'N/A';
        }
        
        $orders[] = $row;
    }

    // Commit transaction
    $conn->commit();

    // Add diagnostic info in development
    $response = [
        'orders' => $orders,
        'debug' => [
            'order_count' => count($orders)
        ]
    ];

    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set content type header
    header('Content-Type: application/json');
    
    // Output JSON response
    echo json_encode($response);
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    handleError("Database error: " . $e->getMessage(), [
        'query' => $query ?? 'No query executed',
        'trace' => $e->getTraceAsString()
    ]);
}
?> 