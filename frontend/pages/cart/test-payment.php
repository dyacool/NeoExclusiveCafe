<?php
/**
 * Simple test endpoint to debug payment processing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Get raw input
    $raw_input = file_get_contents('php://input');
    error_log("Test endpoint - Raw input: " . $raw_input);
    
    // Parse JSON
    $input = json_decode($raw_input, true);
    $json_error = json_last_error();
    
    if ($json_error !== JSON_ERROR_NONE) {
        // Return detailed JSON error info
        echo json_encode([
            'success' => false,
            'error' => 'JSON Parse Error: ' . json_last_error_msg(),
            'json_error_code' => $json_error,
            'raw_input_length' => strlen($raw_input),
            'raw_input_preview' => substr($raw_input, 0, 200)
        ]);
        return;
    }
    
    // Return success with received data
    echo json_encode([
        'success' => true,
        'message' => 'Test endpoint working',
        'received_data' => $input,
        'raw_input_length' => strlen($raw_input)
    ]);
    
} catch (Exception $e) {
    error_log("Test endpoint error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
