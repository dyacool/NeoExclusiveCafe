<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

require_once '../../../backend/pages/admin-includes/database.php';

$user_id = $_SESSION['user_id'];

// Fetch user email from users table
$sql_email = "SELECT email FROM users WHERE id = ?";
$stmt_email = mysqli_prepare($conn, $sql_email);
mysqli_stmt_bind_param($stmt_email, "i", $user_id);
mysqli_stmt_execute($stmt_email);
$result_email = mysqli_stmt_get_result($stmt_email);
$user = mysqli_fetch_assoc($result_email);

if (!$user) {
    // User not found, redirect or handle error
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

$user_email = $user['email'];

// Fetch user orders by customer_email
$sql = "SELECT order_id, status, order_date FROM orders WHERE customer_email = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Orders - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="../../css/users/my-orders.css" />
</head>
<body>
<?php include '../../user-includes/navbar/customer-navigation.php'; ?>

<div class="orders-container">
    <h1>My Orders</h1>
    <table class="orders-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Status</th>
                <th>Order Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                    <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></td>
                    <td>
                        <a href="../cart/order-details.php?order_id=<?php echo $order['order_id']; ?>" class="btn-view">View Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No orders found for your account.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
