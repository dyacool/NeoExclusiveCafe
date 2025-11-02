<?php
/**
 * Rider Orders Interface
 * Mobile-responsive interface for delivery riders to view and manage deliveries
 */

session_start();

// TODO: Implement proper rider authentication
// For now, using a simple check - replace with actual rider authentication
if (!isset($_SESSION["is_rider"]) && !isset($_SESSION["is_admin"])) {
    // Redirect to rider login page
    header("Location: /rider/rider-login.php");
    exit();
}

require_once __DIR__ . '/../backend/pages/admin-includes/database.php';

// Get today's date
$today = date('Y-m-d');

// Query for today's delivery orders
$sql = "SELECT o.order_id, o.customer_name, o.customer_address, 
               o.total_amount, o.delivery_time, o.status,
               pod.proof_image_path, pod.submitted_at,
               GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN pod_orders pod ON o.order_id = pod.order_id
        WHERE o.delivery_method = 'Delivery'
        AND o.delivery_date = ?
        AND o.status IN ('Ready for Delivery', 'Out for Delivery')
        GROUP BY o.order_id
        ORDER BY o.delivery_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Deliveries - Rider</title>
    <link rel="stylesheet" href="rider-orders.css">
</head>
<body>
    <div class="rider-container">
        <header class="rider-header">
            <h1>📦 Today's Deliveries</h1>
            <p class="date-display"><?php echo date('l, F j, Y'); ?></p>
        </header>

        <div class="orders-summary">
            <div class="summary-card">
                <span class="summary-number"><?php echo mysqli_num_rows($result); ?></span>
                <span class="summary-label">Total Orders</span>
            </div>
        </div>

        <div class="orders-table-wrapper">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <table class="rider-orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Products</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="orders-tbody">
                        <?php while ($order = mysqli_fetch_assoc($result)): ?>
                            <tr class="order-row <?php echo !empty($order['proof_image_path']) ? 'completed' : ''; ?>" 
                                data-order-id="<?php echo $order['order_id']; ?>"
                                onclick="<?php echo empty($order['proof_image_path']) ? 'openProofModal(' . $order['order_id'] . ')' : ''; ?>">
                                <td data-label="Order #">
                                    <strong>#<?php echo $order['order_id']; ?></strong>
                                </td>
                                <td data-label="Customer">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </td>
                                <td data-label="Address">
                                    <?php echo htmlspecialchars($order['customer_address']); ?>
                                </td>
                                <td data-label="Products">
                                    <div class="products-list">
                                        <?php echo htmlspecialchars($order['products']); ?>
                                    </div>
                                </td>
                                <td data-label="Total">
                                    <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </td>
                                <td data-label="Status">
                                    <?php if (!empty($order['proof_image_path'])): ?>
                                        <span class="status-badge delivered">✓ Delivered</span>
                                    <?php else: ?>
                                        <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-orders">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7h-9"></path>
                        <path d="M14 17H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path>
                        <path d="M16 21h4"></path>
                        <path d="M18 17v8"></path>
                    </svg>
                    <h2>No Deliveries Today</h2>
                    <p>You're all caught up! Check back later for new orders.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Proof of Delivery Modal -->
    <div id="proof-modal" class="proof-modal">
        <div class="proof-modal-content">
            <div class="modal-header">
                <h2>📸 Proof of Delivery</h2>
                <p class="order-info">Order #<span id="modal-order-id"></span></p>
            </div>
            
            <div class="camera-container">
                <video id="camera-preview" autoplay playsinline></video>
                <canvas id="photo-canvas" style="display:none;"></canvas>
                <img id="captured-photo" style="display:none;" alt="Captured proof" />
            </div>
            
            <div class="camera-error" id="camera-error" style="display:none;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>Camera access denied</p>
                <small>Please enable camera permissions in your browser settings</small>
            </div>
            
            <div class="modal-actions">
                <button id="close-modal-btn" class="btn btn-secondary">Close</button>
                <button id="capture-btn" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    Capture Photo
                </button>
                <button id="retake-btn" class="btn btn-warning" style="display:none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Retake
                </button>
                <button id="confirm-btn" class="btn btn-success" style="display:none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Confirm Delivery
                </button>
            </div>
            
            <div class="upload-progress" id="upload-progress" style="display:none;">
                <div class="progress-bar-container">
                    <div class="progress-bar"></div>
                </div>
                <p>Uploading proof...</p>
            </div>
        </div>
    </div>

    <script src="rider-orders.js"></script>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
