<?php
// Disable error display to prevent HTML in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

// Set JSON header first
header('Content-Type: application/json');

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/settings-helper.php";

try {
    // Get the days from the request
    $input = json_decode(file_get_contents('php://input'), true);
    $days = isset($input['days']) && is_array($input['days']) ? $input['days'] : [];
    
    error_log("Applying global days: " . json_encode($days));
    
    // Save to system settings
    setSetting('global_available_days', $days, 'json', 'Global available days for pre-order products');
    
    // Get all products with status 1, 2, or 3 (Pick Up, Delivery, Delivery or Pick Up)
    $products_query = "SELECT id FROM products WHERE status_id IN (1, 2, 3) AND deleted_at IS NULL";
    $products_result = $conn->query($products_query);
    
    $updated_count = 0;
    
    if ($products_result && $products_result->num_rows > 0) {
        // Collect all product IDs
        $product_ids = [];
        while ($product = $products_result->fetch_assoc()) {
            $product_ids[] = $product['id'];
        }
        
        if (!empty($product_ids)) {
            // Delete all existing days for these products in one query
            $ids_string = implode(',', array_map('intval', $product_ids));
            $conn->query("DELETE FROM product_day WHERE product_id IN ($ids_string)");
            
            // Insert new days in batch
            if (!empty($days)) {
                $values = [];
                foreach ($product_ids as $product_id) {
                    foreach ($days as $day) {
                        $day_escaped = $conn->real_escape_string($day);
                        $values[] = "($product_id, '$day_escaped')";
                    }
                }
                
                if (!empty($values)) {
                    $values_string = implode(',', $values);
                    $conn->query("INSERT INTO product_day (product_id, day_of_week) VALUES $values_string");
                }
            }
            
            $updated_count = count($product_ids);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Global days updated and applied to $updated_count products",
        'updated_count' => $updated_count
    ]);
    
} catch (Exception $e) {
    error_log("Error in apply-global-days.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
