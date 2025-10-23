<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
$log_file = __DIR__ . "/../../../logs/sdo_quantities.log";

function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $log_file);
}

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    logMessage("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

$raw_input = file_get_contents('php://input');
logMessage("Raw input: " . $raw_input);

$data = json_decode($raw_input, true);

if (!isset($data['product_id']) || !isset($data['quantities'])) {
    logMessage("Missing product_id or quantities");
    echo json_encode(['success' => false, 'message' => 'Product ID and quantities are required']);
    exit();
}

$product_id = intval($data['product_id']);
$quantities = $data['quantities']; // Array of date => quantity

logMessage("Processing product_id: $product_id");
logMessage("Quantities type: " . gettype($quantities));
logMessage("Quantities is_array: " . (is_array($quantities) ? 'yes' : 'no'));
logMessage("Quantities empty: " . (empty($quantities) ? 'yes' : 'no'));
logMessage("Quantities count: " . (is_array($quantities) ? count($quantities) : 'N/A'));
logMessage("Quantities content: " . json_encode($quantities));

mysqli_begin_transaction($conn);

try {
    // Delete existing quantities for this product
    $delete_sql = "DELETE FROM quantity_per_day_sdo WHERE product_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    
    if (!$delete_stmt) {
        throw new Exception("Failed to prepare delete statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($delete_stmt, "i", $product_id);
    mysqli_stmt_execute($delete_stmt);
    $deleted_count = mysqli_stmt_affected_rows($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    logMessage("Deleted $deleted_count existing quantities for product $product_id");
    
    // Insert new quantities (including 0 quantities to allow explicit "unavailable" marking)
    $inserted_count = 0;
    if (!empty($quantities)) {
        $insert_sql = "INSERT INTO quantity_per_day_sdo (product_id, date, quantity) VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        
        if (!$insert_stmt) {
            throw new Exception("Failed to prepare insert statement: " . mysqli_error($conn));
        }
        
        foreach ($quantities as $date => $quantity) {
            $qty = intval($quantity);
            // Save all quantities, including 0
            logMessage("Inserting: product_id=$product_id, date=$date, quantity=$qty");
            mysqli_stmt_bind_param($insert_stmt, "isi", $product_id, $date, $qty);
            
            if (!mysqli_stmt_execute($insert_stmt)) {
                throw new Exception("Failed to insert quantity for date $date: " . mysqli_stmt_error($insert_stmt));
            }
            $inserted_count++;
        }
        
        mysqli_stmt_close($insert_stmt);
    }
    
    mysqli_commit($conn);
    
    logMessage("Successfully saved $inserted_count quantities for product $product_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Quantities updated successfully',
        'inserted' => $inserted_count,
        'deleted' => $deleted_count
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    $error_msg = 'Failed to update quantities: ' . $e->getMessage();
    logMessage("ERROR: $error_msg");
    echo json_encode([
        'success' => false,
        'message' => $error_msg
    ]);
}

mysqli_close($conn);
?>
