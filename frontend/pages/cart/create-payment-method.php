<?php
/**
 * Create Payment Method for Card Payments
 * Handles PayMongo Payment Method creation
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../../../backend/pages/admin-includes/database.php';
require_once '../../../includes/session-manager.php';
require_once 'paymongo-config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("[CREATE-PM] Received request: " . print_r($input, true));
    
    // Validate input
    if (empty($input['card_number']) || empty($input['exp_month']) || empty($input['exp_year']) || empty($input['cvc'])) {
        throw new Exception('Missing required card details');
    }
    
    // Create PayMongo API instance
    $paymongo = new PayMongoAPI();
    
    // Prepare card details for PayMongo
    $cardDetails = [
        'card_number' => str_replace(' ', '', $input['card_number']), // Remove spaces
        'exp_month' => intval($input['exp_month']),
        'exp_year' => intval($input['exp_year']),
        'cvc' => $input['cvc']
    ];
    
    error_log("[CREATE-PM] Creating payment method with card ending in: " . substr($cardDetails['card_number'], -4));
    error_log("[CREATE-PM] Card details: month=" . $cardDetails['exp_month'] . ", year=" . $cardDetails['exp_year']);
    
    // Create payment method
    $result = $paymongo->createPaymentMethod('card', $cardDetails);
    
    error_log("[CREATE-PM] PayMongo full response: " . json_encode($result));
    
    // Check for errors in response
    if (isset($result['error'])) {
        $errorMsg = is_array($result['error']) ? json_encode($result['error']) : $result['error'];
        
        // Include the full response for debugging
        if (isset($result['response'])) {
            error_log("[CREATE-PM] PayMongo response details: " . json_encode($result['response']));
            
            // Check for specific error messages
            if (isset($result['response']['errors'])) {
                $errors = $result['response']['errors'];
                if (is_array($errors) && count($errors) > 0) {
                    $firstError = $errors[0];
                    $detailMsg = $firstError['detail'] ?? $firstError['code'] ?? 'Unknown error';
                    throw new Exception('PayMongo error: ' . $detailMsg);
                }
            }
        }
        
        throw new Exception('PayMongo error: ' . $errorMsg);
    }
    
    if (isset($result['errors'])) {
        $errorMsg = is_array($result['errors']) ? json_encode($result['errors']) : $result['errors'];
        throw new Exception('PayMongo validation errors: ' . $errorMsg);
    }
    
    if (!isset($result['data']['id'])) {
        throw new Exception('Invalid response from payment gateway: ' . json_encode($result));
    }
    
    error_log("[CREATE-PM] ✓ Payment method created successfully: " . $result['data']['id']);
    
    echo json_encode([
        'success' => true,
        'payment_method_id' => $result['data']['id']
    ]);
    
} catch (Exception $e) {
    error_log("[CREATE-PM] Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
