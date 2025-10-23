<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/settings-helper.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['days']) || !is_array($data['days'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    $selectedDays = $data['days'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get all products with status_id 1 (Pick Up), 2 (Delivery), or 3 (Delivery or Pick Up)
        $sql = "SELECT id FROM products WHERE status_id IN (1, 2, 3) AND deleted_at IS NULL";
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            throw new Exception("Failed to fetch products: " . mysqli_error($conn));
        }
        
        $productIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $productIds[] = $row['id'];
        }
        
        if (empty($productIds)) {
            echo json_encode(['success' => true, 'message' => 'No eligible products found', 'updated_count' => 0]);
            exit();
        }
        
        // Delete existing available days for these products
        $productIdsStr = implode(',', $productIds);
        $deleteSql = "DELETE FROM product_day WHERE product_id IN ($productIdsStr)";
        
        if (!mysqli_query($conn, $deleteSql)) {
            throw new Exception("Failed to delete existing days: " . mysqli_error($conn));
        }
        
        // Insert new available days
        if (!empty($selectedDays)) {
            $insertValues = [];
            foreach ($productIds as $productId) {
                foreach ($selectedDays as $day) {
                    $day = mysqli_real_escape_string($conn, $day);
                    $insertValues[] = "($productId, '$day')";
                }
            }
            
            if (!empty($insertValues)) {
                $insertSql = "INSERT INTO product_day (product_id, day_of_week) VALUES " . implode(', ', $insertValues);
                
                if (!mysqli_query($conn, $insertSql)) {
                    throw new Exception("Failed to insert new days: " . mysqli_error($conn));
                }
            }
        }
        
        // Save the selected days to system settings for persistence
        setSetting('global_available_days', $selectedDays, 'json', 'Global available days for Pick Up, Delivery, and Delivery or Pick Up products');
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Available days updated successfully for ' . count($productIds) . ' products',
            'updated_count' => count($productIds),
            'selected_days' => $selectedDays
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    mysqli_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
