<?php
session_start();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['coupon'])) {
    $_SESSION['applied_coupon'] = $input['coupon'];
    $_SESSION['discount_amount'] = $input['discount_amount'] ?? 0;
    
    error_log("Coupon saved to session: " . json_encode($input['coupon']));
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No coupon data provided']);
}
?>
