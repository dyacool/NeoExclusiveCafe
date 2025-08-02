<?php
session_start();
require_once '../../php/includes/database.php'; // Adjust path as needed

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/auth/login-signup.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch order notifications
$order_notifs_query = "
    SELECT n.id, n.message, n.created_at, o.id AS order_id, o.product_image, o.status
    FROM notifications n
    JOIN orders o ON n.order_id = o.id
    WHERE n.user_id = ? AND n.type = 'order'
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($order_notifs_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$order_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch promotional notifications
$promo_notifs_query = "
    SELECT n.id, n.message, n.created_at, p.id AS product_id, p.image AS product_image, p.description
    FROM notifications n
    JOIN products p ON n.product_id = p.id
    WHERE n.user_id = ? AND n.type = 'promotion'
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($promo_notifs_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$promo_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Messages - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="../../css/users/messages.css" />
</head>
<body>
    <h1>Messages</h1>

    <section id="order-messages">
        <h2>Order Updates</h2>
        <?php if (count($order_notifications) === 0): ?>
            <p>No order updates.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($order_notifications as $notif): ?>
                    <li>
                        <a href="order-details.php?order_id=<?= htmlspecialchars($notif['order_id']) ?>">
                            <img src="<?= htmlspecialchars($notif['product_image'] ?: '../../assets/images/default-product.png') ?>" alt="Product Image" width="50" />
                            <div>
                                <strong>Order #<?= htmlspecialchars($notif['order_id']) ?></strong><br />
                                <?= htmlspecialchars($notif['message']) ?><br />
                                <small><?= htmlspecialchars(date('M d, Y H:i', strtotime($notif['created_at']))) ?></small>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section id="promo-messages">
        <h2>Promotions</h2>
        <?php if (count($promo_notifications) === 0): ?>
            <p>No promotions.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($promo_notifications as $promo): ?>
                    <li>
                        <a href="product.php?product_id=<?= htmlspecialchars($promo['product_id']) ?>">
                            <img src="<?= htmlspecialchars($promo['product_image'] ?: '../../assets/images/default-product.png') ?>" alt="Product Image" width="50" />
                            <div>
                                <?= htmlspecialchars($promo['message']) ?><br />
                                <small><?= htmlspecialchars(date('M d, Y H:i', strtotime($promo['created_at']))) ?></small>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</body>
</html>
