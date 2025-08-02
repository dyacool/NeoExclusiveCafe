<?php
// Prevent errors from being displayed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/database.php";

// Set content type to JSON
header('Content-Type: application/json');
// Add CORS headers to allow access from any page
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Function to handle errors
function handleError($message) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

try {
    // Check if required tables exist and create them if not
    require_once $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/admin/check-and-setup-tables.php";
    
    // Get default limit
    $default_query = "SELECT default_limit FROM order_limits WHERE id = 1";
    $default_result = $conn->query($default_query);
    
    if (!$default_result) {
        handleError("Failed to get default limit: " . $conn->error);
    }
    
    $default_limit = $default_result->fetch_assoc()['default_limit'] ?? 10;
    $limits = [];
    
    if (isset($_GET['start']) && isset($_GET['end'])) {
        $start_date = $conn->real_escape_string($_GET['start']);
        $end_date = $conn->real_escape_string($_GET['end']);
        
        // First get date status (not accepting orders)
        $status_query = "SELECT date, status FROM orderdate_status 
                        WHERE date BETWEEN '$start_date' AND '$end_date'
                        AND status = 'not_accepting'";
        $status_result = $conn->query($status_query);
        
        if ($status_result) {
            while ($row = $status_result->fetch_assoc()) {
                $date = $row['date'];
                // Set limit to 0 for dates not accepting orders
                $limits[$date] = 0;
            }
        }
        
        // Then get specific date limits
        $limits_query = "SELECT date, limit_value FROM date_limits 
                         WHERE date BETWEEN '$start_date' AND '$end_date'";
        $limits_result = $conn->query($limits_query);
        
        if ($limits_result) {
            while ($row = $limits_result->fetch_assoc()) {
                $date = $row['date'];
                // Only set if not already set as not accepting orders
                if (!isset($limits[$date])) {
                    $limits[$date] = (int)$row['limit_value'];
                }
            }
        }
        
        // Generate all dates in range
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day'); // Include end date
        
        while ($current < $end) {
            $currentDate = $current->format('Y-m-d');
            // Set default limit for dates not explicitly set
            if (!isset($limits[$currentDate])) {
                $limits[$currentDate] = $default_limit;
            }
            $current->modify('+1 day');
        }
    }
    
    // Debug output - remove in production
    error_log("Date limits for checkout: " . json_encode($limits));
    
    // Clear any previous output and return data
    if (ob_get_length()) ob_clean();
    
    echo json_encode($limits);
    
} catch (Exception $e) {
    handleError($e->getMessage());
}
?> 