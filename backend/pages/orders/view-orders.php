<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: order-list.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Get order details
$order_sql = "SELECT * FROM orders WHERE order_id = ?";
$stmt = mysqli_prepare($conn, $order_sql);
if (!$stmt) {
    die("SQL Error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: order-list.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items with product images
$items_sql = "SELECT oi.*, pi.image_url 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_name = p.name 
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
              WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $items_sql);
if (!$stmt) {
    die("SQL Error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="view-orders.css">
    <style>
        .order-date-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .order-date-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .order-actions {
                width: 100%;
                justify-content: flex-start;
                margin-top: 10px;
            }
        }
        
        /* Print styles for 48mm thermal receipt printer */
        @media print {
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            @page {
                size: 48mm auto;
                margin: 0;
            }
            
            body {
                width: 48mm;
                max-width: 48mm;
                margin: 0 auto;
                padding: 2mm;
                font-family: 'Courier New', monospace;
                font-size: 7px;
                line-height: 1.2;
                color: #000;
                background: #fff;
            }
            
            /* Hide non-receipt elements */
            .breadcrumb,
            .order-actions,
            .print-button,
            .status-form,
            .warning-message,
            .product-image,
            h3,
            .mobile-header,
            .sidebar,
            .header,
            .logout-modal {
                display: none !important;
            }
            
            /* Receipt header */
            .main-container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
                overflow: hidden;
            }
            
            .order-details {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
                overflow: hidden;
            }
            
            /* Add NeoCafe header */
            .order-info::before {
                content: "NEO CAFE";
                display: block;
                text-align: center;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.5px;
                margin: 0 0 3px 0;
                padding-bottom: 3px;
                border-bottom: 2px solid #000;
            }
            
            .order-info h2 {
                text-align: center;
                font-size: 9px;
                font-weight: bold;
                margin: 0 0 2px 0;
                padding: 3px 0 2px 0;
                border-bottom: 1px solid #000;
            }
            
            .order-date {
                text-align: center;
                font-size: 6px;
                margin: 2px 0 4px 0;
                padding-bottom: 2px;
                border-bottom: 1px dashed #000;
            }
            
            /* Customer details */
            .order-grid {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow: hidden;
            }
            
            .customer-details,
            .order-summary {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
                box-shadow: none;
                border: none;
                overflow: hidden;
            }
            
            .detail-group {
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 1px dashed #000;
                max-width: 100%;
                overflow: hidden;
            }
            
            .detail-group p {
                margin: 2px 0;
                font-size: 7px;
                line-height: 1.2;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            .detail-group strong {
                display: inline-block;
                width: 35px;
                font-weight: bold;
                font-size: 7px;
            }
            
            /* Order items table */
            .table-responsive {
                width: 100%;
                max-width: 100%;
                overflow: hidden;
            }
            
            .items-table {
                width: 100%;
                max-width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
                table-layout: fixed;
            }
            
            .items-table thead th {
                display: none;
            }
            
            .items-table tbody tr {
                border-bottom: 1px dotted #000;
            }
            
            .items-table tbody td {
                padding: 2px 0;
                border: none;
                font-size: 7px;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            /* Simplified item layout for receipt */
            .items-table tbody tr {
                display: block;
                margin-bottom: 4px;
                max-width: 100%;
            }
            
            .items-table tbody td:nth-child(1) {
                display: none; /* Hide image column */
            }
            
            .items-table tbody td:nth-child(2) {
                display: block;
                font-weight: bold;
                font-size: 7px;
                margin-bottom: 1px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                max-width: 100%;
            }
            
            .items-table tbody td:nth-child(3),
            .items-table tbody td:nth-child(4),
            .items-table tbody td:nth-child(5) {
                display: inline;
                font-size: 6px;
            }
            
            .items-table tbody td:nth-child(3)::before {
                content: "₱";
            }
            
            .items-table tbody td:nth-child(4)::before {
                content: " x ";
            }
            
            .items-table tbody td:nth-child(5)::before {
                content: " = ₱";
            }
            
            /* Totals */
            .items-table tfoot {
                border-top: 2px solid #000;
                margin-top: 10px;
                width: 100%;
            }
            
            .items-table tfoot tr {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 4px 0;
                width: 100%;
                max-width: 100%;
            }
            
            .items-table tfoot td {
                display: block !important;
                border: none;
                font-size: 7px;
                font-weight: bold;
                white-space: nowrap;
            }
            
            .total-label {
                font-weight: bold;
                text-align: left;
            }
            
            .total-value {
                text-align: right;
                font-weight: bold;
            }
            
            .items-table tfoot tr:last-child {
                font-size: 8px;
                font-weight: bold;
                border-top: 1px solid #000;
                padding-top: 3px;
                margin-top: 2px;
            }
            
            .items-table tfoot tr:last-child td {
                font-size: 8px;
            }
            
            /* Footer */
            .order-summary::after {
                content: "Thank you!";
                display: block;
                text-align: center;
                margin-top: 6px;
                padding-top: 4px;
                border-top: 1px dashed #000;
                font-size: 7px;
                font-weight: bold;
            }
        }
    </style>
    <title>Order #<?php echo $order_id; ?> | Neo Exclusive Cafe</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="breadcrumb">
        <a href="/backend/pages/orders/order-list.php">Orders</a>
        <span class="separator">></span>
        <span class="current">Order #<?php echo $order_id; ?> - Details</span>
    </div>
    
    <div class="main-container">
        <div class="order-details">
            <div class="order-info">
                <h2>Order #<?php echo $order_id; ?></h2>
                <div class="order-date-actions">
                    <p class="order-date">Placed on <?php echo date('F d, Y \a\t h:i A', strtotime($order['order_date'])); ?></p>
                    <div class="order-actions">
                        <form method="POST" action="update-status.php" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <select name="status" onchange="this.form.submit()" class="status-badge-select status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                <?php
                                    if($order['delivery_method'] == "Pick-up"){
                                        $statuses = ["Pending", "Preparing", "Ready for Pick-up", "Picked-up"];
                                    }elseif($order['delivery_method'] == "Delivery"){
                                        $statuses = ["Pending", "Preparing", "Ready for Delivery", "Out for Delivery", "Delivered"];
                                    }
                                    foreach ($statuses as $status) {
                                        $selected = ($order['status'] == $status) ? 'selected' : '';
                                        echo "<option value=\"$status\" $selected>$status</option>";
                                    }
                                ?>
                            </select>
                        </form>
                        
                        <button onclick="window.print()" class="print-button">Print</button>
                    </div>
                </div>
            </div>

            
            
            <div class="order-grid">
                <div class="customer-details">
                    <h3>Customer Details</h3>
                    <div class="detail-group">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_contact']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
                        <?php if (!empty($order['notes'])): ?>
                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h3>Delivery Information</h3>
                    <div class="detail-group">
                        <p><strong>Method:</strong> <?php echo htmlspecialchars($order['delivery_method']); ?></p>
                        <?php if (!empty($order['delivery_date'])): ?>
                            <p><strong>Delivery Date:</strong> <?php echo date('F d, Y', strtotime($order['delivery_date'])); ?></p>
                            <?php if (!empty($order['delivery_time'])): ?>
                                <p><strong>Delivery Time:</strong> <?php echo date('h:i A', strtotime($order['delivery_time'])); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['pickup_date'])): ?>
                            <p><strong>Pickup Date:</strong> <?php echo date('F d, Y', strtotime($order['pickup_date'])); ?></p>
                        <?php endif; ?>
                        
                        <?php
                        // Calculate and display warning if applicable
                        $date = !empty($order['delivery_date']) ? $order['delivery_date'] : $order['pickup_date'];
                        $time = !empty($order['delivery_time']) ? $order['delivery_time'] : '00:00:00';
                        
                        if (!empty($date)) {
                            $current_datetime = new DateTime();
                            $delivery_datetime = new DateTime($date . ' ' . $time);
                            $today = new DateTime(date('Y-m-d'));
                            $tomorrow = new DateTime(date('Y-m-d', strtotime('+1 day')));
                            $delivery_date_only = new DateTime($date);
                            
                            $status = $order['status'];
                            
                            // Check if delivery/pickup date has passed and order is still pending/preparing/ready
                            if ($delivery_datetime < $current_datetime && 
                                in_array($status, ['Pending', 'Preparing', 'Ready for Delivery', 'Ready for Pick-up'])) {
                                echo '<div class="warning-message critical">';
                                echo '<div class="warning-header"><strong> ! CRITICAL WARNING</strong></div>';
                                echo '<div class="warning-content">This order is <span class="warning-badge critical"> OVERDUE</span></div>';
                                echo '<div class="warning-description">The delivery/pickup date has passed and the order is still pending!</div>';
                                echo '</div>';
                            }
                            // Check if delivery/pickup is tomorrow and status is still pending
                            elseif ($delivery_date_only->format('Y-m-d') == $tomorrow->format('Y-m-d') && $status == 'Pending') {
                                echo '<div class="warning-message urgent">';
                                echo '<div class="warning-header"><strong> ! ATTENTION REQUIRED</strong></div>';
                                echo '<div class="warning-content">This order is <span class="warning-badge urgent">DUE TOMORROW</span></div>';
                                echo '<div class="warning-description">Please start preparation soon to meet the delivery/pickup schedule.</div>';
                                echo '</div>';
                            }
                            // Check if delivery/pickup is today and status is still pending
                            elseif ($delivery_date_only->format('Y-m-d') == $today->format('Y-m-d') && $status == 'Pending') {
                                echo '<div class="warning-message today">';
                                echo '<div class="warning-header"><strong>! REMINDER</strong></div>';
                                echo '<div class="warning-content">This order is <span class="warning-badge today">DUE TODAY</span></div>';
                                echo '<div class="warning-description">Please start preparation immediately to meet today\'s schedule.</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                        
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    </div>
                </div>
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while ($item = mysqli_fetch_assoc($items_result)): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                    
                                    // Construct image path same as product-list.php
                                    $imagePath = '';
                                    if (!empty($item['image_url'])) {
                                        $imagePath = '/assets/' . $item['image_url'];
                                    }
                                ?>
                                    <tr>
                                        <td class="product-image">
                                            <?php if (!empty($imagePath)): ?>
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <div class="no-image">No Image</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item_total, 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="total-label">Subtotal</td>
                                    <td class="total-value">₱<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="total-label">Total</td>
                                    <td class="total-value">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                
            </div>
        </div>
    </div>
</body>
</html>
