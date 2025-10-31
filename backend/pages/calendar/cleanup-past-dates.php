<?php
/**
 * Cleanup Past Dates from date_limits table
 * Removes dates older than yesterday to improve performance
 */

require_once __DIR__ . "/../admin-includes/database.php";

header('Content-Type: application/json');

try {
    // Delete dates older than today
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $delete_query = "DELETE FROM date_limits WHERE date < ?";
    $stmt = $conn->prepare($delete_query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $yesterday);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        $stmt->close();
        
        error_log("Cleaned up $deleted_count past dates from date_limits table (before $yesterday)");
        
        echo json_encode([
            'success' => true,
            'deleted_count' => $deleted_count,
            'message' => "Cleaned up $deleted_count past date(s)"
        ]);
    } else {
        throw new Exception("Failed to delete past dates: " . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
