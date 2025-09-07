<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /NeoExclusiveCafe/pages/auth/login-signup.php");
    exit();
}

require_once '../admin-includes/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Update the order status
    $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
if (mysqli_stmt_execute($stmt)) {
    // Create notification for user about order status change
    require_once '../../../frontend/pages/notifications/class-notif.php';
    $notification = new Notification($conn);

    // Adjusted column name from user_id to customer_id based on error
    $userIdStmt = mysqli_prepare($conn, "SELECT customer_id FROM orders WHERE order_id = ?");
    mysqli_stmt_bind_param($userIdStmt, "i", $order_id);
    mysqli_stmt_execute($userIdStmt);
    mysqli_stmt_bind_result($userIdStmt, $user_id);
    mysqli_stmt_fetch($userIdStmt);
    mysqli_stmt_close($userIdStmt);

    $statusMessages = [
        "Confirmed" => "Your order #$order_id has been confirmed.",
        "Ready for Delivery" => "Your order #$order_id is ready for delivery.",
        "Ready for Pick-up" => "Your order #$order_id is ready for pick-up.",
        "Delivered" => "Your order #$order_id has been delivered.",
        "Picked-up" => "Your order #$order_id has been picked up."
    ];

    if (array_key_exists($status, $statusMessages)) {
        // Check if user exists before creating notification
        $userCheckStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ?");
        mysqli_stmt_bind_param($userCheckStmt, "i", $user_id);
        mysqli_stmt_execute($userCheckStmt);
        mysqli_stmt_store_result($userCheckStmt);
        if (mysqli_stmt_num_rows($userCheckStmt) > 0) {
            $notification->create($user_id, "order_status", $statusMessages[$status]);
        }
        mysqli_stmt_close($userCheckStmt);
    }

    // Use the createOrderNotification function for detailed notifications
    $notification->createOrderNotification($order_id, $status);

    // Success - redirect back to the order details
    header("Location: view-orders.php?order_id=$order_id&status_updated=1");
    exit();
} else {
    // Error - redirect with error message
    header("Location: view-orders.php?order_id=$order_id&error=1");
    exit();
}
} else {
    // Invalid request - redirect to order list
    header("Location: order-list.php");
    exit();
}
?>
