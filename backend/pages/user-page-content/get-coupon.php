<?php
session_start();
header('Content-Type: application/json');


if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


require_once __DIR__ . '/database-config.php';

$conn = getDBConnection();
createPromotionsTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM promotions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $coupon = $result->fetch_assoc();
        
        $mappedCoupon = [
            'id' => $coupon['id'],
            'title' => $coupon['title'],
            'code' => $coupon['code'],
            'discount_type' => $coupon['type'],
            'discount_value' => $coupon['value'],
            'min_spend' => $coupon['min_purchase'],
            'applicable_to' => $coupon['applicable_to'],
            'usage_limit' => $coupon['usage_limit'],
            'usage_limit_per_user' => $coupon['usage_limit_per_user'],
            'start_date' => $coupon['activation_date'],
            'end_date' => $coupon['expiration_date'],
            'status' => $coupon['status'],
            'used_count' => $coupon['used_count'],
            'include_free_shipping' => $coupon['include_free_shipping'],
            'prevent_discounted' => $coupon['prevent_discounted'],
            'application_method' => $coupon['application_method']
        ];
        
        echo json_encode(['success' => true, 'coupon' => $mappedCoupon]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Coupon not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
