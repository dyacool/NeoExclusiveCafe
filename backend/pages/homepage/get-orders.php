<?php
// Start output buffering to catch any unexpected output
ob_start();

// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/php_errors.log');

// Include necessary files
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../../login/admin/admin-auth.php";

// Set content type to JSON
header('Content-Type: application/json');

// Function to handle errors
function handleError($message, $debug = []) {
    if (ob_get_length()) ob_clean();
    error_log("Calendar Error: " . $message . " Debug: " . print_r($debug, true));
    http_response_code(500);
    echo json_encode([
        'error' => $message,
        'debug' => $debug
    ]);
    exit;
}

try {
    // Check if database connection is established
    if (!isset($conn) || !$conn) {
        handleError("Database connection failed");
    }

    // Get start and end dates from FullCalendar request
    $start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
    $end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+1 month'));

    // Convert dates to MySQL format
    try {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
    } catch (Exception $e) {
        handleError("Invalid date format", [
            'start' => $start,
            'end' => $end,
            'error' => $e->getMessage()
        ]);
    }

    error_log("Processing dates: start=$start, end=$end");

    // Get all orders within the date range
    $query = "SELECT 
                o.order_id,
                o.order_date,
                o.delivery_method AS order_type,
                o.customer_name,
                o.status,
                o.pickup_date,
                o.delivery_date,
                o.pickup_time,
                o.delivery_time,
                o.total_amount,
                c.contact as customer_contact,
                c.address as customer_address
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.customer_id
              WHERE (
                  (o.pickup_date BETWEEN ? AND ?) OR
                  (o.delivery_date BETWEEN ? AND ?)
              )";

    // Add status filter based on showCompletedOrders parameter
    $showCompleted = isset($_GET['showCompleted']) && $_GET['showCompleted'] === 'true';
    if ($showCompleted) {
        $query .= " AND LOWER(o.status) IN (LOWER('pending'), LOWER('picked-up'), LOWER('delivered'))";
    } else {
        $query .= " AND LOWER(o.status) LIKE LOWER('pending')";
    }

    $query .= " ORDER BY COALESCE(o.pickup_date, o.delivery_date) ASC, o.pickup_time ASC";

    error_log("Executing query: " . $query);
    error_log("With parameters: start=$start, end=$end, showCompleted=" . ($showCompleted ? 'true' : 'false'));

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        handleError("Failed to prepare statement: " . $conn->error, [
            'query' => $query
        ]);
    }

    $stmt->bind_param("ssss", $start, $end, $start, $end);
    
    if (!$stmt->execute()) {
        handleError("Failed to execute query: " . $stmt->error, [
            'query' => $query,
            'start' => $start,
            'end' => $end
        ]);
    }

    $result = $stmt->get_result();
    if (!$result) {
        handleError("Failed to get result: " . $conn->error);
    }
    
    $events = [];
    while ($order = $result->fetch_assoc()) {
        // Determine which date to use (delivery_date or pickup_date)
        $eventDate = !empty($order['delivery_date']) ? $order['delivery_date'] : $order['pickup_date'];
        
        // If we have a time, combine it with the date
        $eventDateTime = $eventDate;
        $displayTime = '';
        
        if (!empty($order['pickup_time']) && $order['pickup_time'] !== '00:00:00') {
            $eventDateTime = $eventDate . 'T' . $order['pickup_time'];
            $displayTime = date('h:i A', strtotime($order['pickup_time']));
        }

        // Set color based on order type and status
        $backgroundColor = '#4CAF50'; // Default green for completed
        $borderColor = '#388E3C';     // Darker green

        if ($order['status'] === 'Pending') {
            if ($order['order_type'] === 'Pick-up') {
                $backgroundColor = '#2196F3'; // Blue for pickup
                $borderColor = '#1976D2';     // Darker blue
            } else {
                $backgroundColor = '#FF9800'; // Orange for delivery
                $borderColor = '#F57C00';     // Darker orange
            }
        }

        $events[] = [
            'id' => $order['order_id'],
            'title' => "Order #" . str_pad($order['order_id'], 2, '0', STR_PAD_LEFT) . " - " . $order['customer_name'],
            'start' => $eventDateTime,
            'backgroundColor' => $backgroundColor,
            'borderColor' => $borderColor,
            'className' => strtolower($order['order_type']) . ' ' . strtolower($order['status']),
            'extendedProps' => [
                'type' => $order['order_type'],
                'customer' => $order['customer_name'],
                'contact' => $order['customer_contact'],
                'address' => $order['customer_address'],
                'time' => $displayTime,
                'status' => $order['status'],
                'total_amount' => $order['total_amount']
            ]
        ];
    }

    // Clear any previous output
    if (ob_get_length()) ob_clean();

    // Return empty array if no events
    if (empty($events)) {
        echo json_encode([]);
        exit;
    }

    // Encode with options to catch encoding errors
    $json = json_encode($events, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);
    
    if ($json === false) {
        handleError("JSON encoding failed: " . json_last_error_msg(), [
            'events' => $events,
            'error' => json_last_error_msg()
        ]);
    }

    echo $json;

} catch (Exception $e) {
    handleError("Server error: " . $e->getMessage());
}
?>