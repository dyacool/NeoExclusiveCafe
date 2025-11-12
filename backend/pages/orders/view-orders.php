<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

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

// Get order items with product images from Cloudinary
$items_sql = "SELECT oi.*, pi.cloud_url 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_name = p.name 
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 AND pi.is_removed = 0
              WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_sql);
if (!$items_stmt) {
    die("SQL Error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

// Get proof of delivery if exists
$pod_sql = "SELECT * FROM pod_orders WHERE order_id = ?";
$pod_stmt = mysqli_prepare($conn, $pod_sql);
if ($pod_stmt) {
    mysqli_stmt_bind_param($pod_stmt, "i", $order_id);
    mysqli_stmt_execute($pod_stmt);
    $pod_result = mysqli_stmt_get_result($pod_stmt);
    $pod = mysqli_fetch_assoc($pod_result);
    mysqli_stmt_close($pod_stmt);
} else {
    $pod = null;
}

// Clean up statements
mysqli_stmt_close($stmt);
mysqli_stmt_close($items_stmt);
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
                width: 55mm;
                max-width: 55mm;
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
                padding: 0.5rem;
                margin: 0;
                overflow: hidden;
                border-radius: 0 !important;
                border: none !important;
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
                font-size: 16pt;
                font-weight: 900;
                letter-spacing: 0.5px;
                margin: 0 0 3px 0;
                padding-bottom: 3px;
                border-bottom: none;
            }

            .order-info {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }

            .order-actions {
                display: none !important;
            }
            
            .order-info h2 {
                text-align: center;
                font-size: 12pt;
                font-weight: bold;
                margin: 0 0 2px 0;
                padding: 3px 0 2px 0;
                border-bottom: 1px solid #000;
            }
            
            .order-date {
                text-align: center;
                width: 100%;
                font-size: 8pt;
                font-weight: 500;
                margin: 4px 0 4px 0;
                padding-bottom: 0.5rem;
                border-bottom: 1px dashed #000;
            }
            
            /* Customer details */
            .order-grid {
                display: block;
                width: 100%;
                max-width: 100%;
                overflow: hidden;
            }

            .order-summary::before {
                content: "Purchased Products";
                display: block;
                text-align: center;
                font-size: 9pt;
                font-weight: 600;
            }


            .order-summary,
            .customer-details {
                width: 100%;
                font-weight: 700;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                border-radius: 0 !important;
                border: none !important;
                box-shadow: none !important;
                transition: none !important;
            }

            .detail-group {
                margin-bottom: 0.3rem;
                padding-bottom: 0.2rem;
                border-bottom: 1px dashed #000;
                overflow: hidden;
            }
            
            .detail-group p {
                color: #000;
                margin: 0.25rem 0;
                font-size: 9pt;
                font-weight: 500;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                line-height: 1.2;
            }
            
            .detail-group strong {
                display: inline-block;
                font-weight: bold;
                font-size: 8pt;
                min-width: 15mm;
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
                margin: 0.5rem 0;
                table-layout: fixed;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            
            .items-table thead th {
                display: none;
            }
            
            .items-table tbody tr {
                border-bottom: 1px dotted #000;
                display: block;
                margin-bottom: 0.3rem;
                max-width: 100%;
                background: transparent !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            .items-table tbody tr:last-child {
                border-bottom: none;
            }
            
            .items-table tbody td {
                padding: 1px 0;
                border: none !important;
                font-size: 8pt;
                word-wrap: break-word;
                overflow-wrap: break-word;
                background: transparent !important;
            }
            
            /* Simplified item layout for receipt */
            .items-table tbody td:nth-child(1) {
                display: none; /* Hide image column */
            }
            
            .items-table tbody td:nth-child(2) {
                display: block !important;
                font-weight: bold;
                font-size: 9pt;
                margin-bottom: 2px;
                white-space: pre-wrap !important;
                max-width: 48mm !important;
                width: 48mm !important;
                color: #000 !important;
                text-align: left !important;
            }
            
            .items-table tbody td:nth-child(3),
            .items-table tbody td:nth-child(4),
            .items-table tbody td:nth-child(5) {
                display: inline !important;
                font-size: 7.5pt;
                color: #000 !important;
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
            
            /* Totals - Target specific classes */
            .items-table tfoot {
                border-top: 2px solid #000;
                margin-top: 0.5rem;
                width: 100%;
                background: transparent !important;
                display: block !important;
            }
            
            .items-table tfoot tr {
                display: block !important;
                width: 100%;
                padding: 2px 0;
                gap: 20px !important;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                margin-bottom: 0;
                position: relative;
                height: auto;
                min-height: 14px;
            }
            
            /* Target the specific classes used in HTML */
            .items-table tfoot .total-label {
                border: none !important;
                font-size: 9pt !important;
                font-weight: 700 !important;
                color: #000 !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                display: inline-block !important;
                text-align: left !important;
                line-height: 1.2;
            }
            
            .items-table tfoot .total-value {
                border: none !important;
                font-size: 9pt !important;
                font-weight: 800 !important;
                color: #000 !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                display: inline-block !important;
                text-align: right !important;
                line-height: 1.2;
            }
            
            /* Clear floats after each row */
            .items-table tfoot tr::after {
                content: "";
                display: table;
                clear: both;
            }
            
            /* Special styling for Total row */
            .items-table tfoot tr:last-child .total-label,
            .items-table tfoot tr:last-child .total-value {
                font-size: 11pt !important;
                font-weight: bold !important;
            }
            
            /* Footer */
            .order-summary::after {
                content: "Thank you!";
                display: block;
                text-align: center;
                padding-top: 4px;
                border-top: 1px dashed #000;
                font-size: 9pt;
                font-weight: bold;
            }

            .back-button,
            .order-actions {
                display: none;
            }

            .order-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }

            .order-summary,
            .customer-details {
                box-shadow: none;
                border: 1px solid var(--gray-300);
                page-break-inside: avoid;
            }

            .items-table {
                border-collapse: collapse;
            }

            .items-table th,
            .items-table td {
                border: 1px solid var(--gray-300);
            }

            .admin-breadcrumb-container{
                display: none !important;
            }
        }
    </style>
    <title>Order #<?php echo $order_id; ?> | Neo Exclusive Cafe</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    <?php include __DIR__ . '/../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>

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
                                    
                                    // Use Cloudinary URL directly or fallback to placeholder
                                    $imagePath = '';
                                    if (!empty($item['cloud_url'])) {
                                        $imagePath = $item['cloud_url'];
                                    } else {
                                        // Cloudinary placeholder for missing images
                                        $imagePath = 'https://res.cloudinary.com/dvdccumbs/image/upload/c_fill,w_400,h_400,g_center/e_blur:1000,co_rgb:cccccc,b_rgb:f0f0f0/sample.jpg';
                                    }
                                ?>
                                    <tr>
                                        <td class="product-image">
                                            <?php if (!empty($imagePath)): ?>
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" loading="lazy" onerror="this.src='https://res.cloudinary.com/dvdccumbs/image/upload/c_fill,w_400,h_400,g_center/e_blur:1000,co_rgb:cccccc,b_rgb:f0f0f0/sample.jpg'">
                                            <?php else: ?>
                                                <div class="no-image">No Image</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(wordwrap($item['product_name'], 20, "\n", true)); ?></td>
                                        <td><?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($item_total, 2); ?></td>
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
                
                <!-- Proof of Delivery -->
                <?php if ($pod && !empty($pod['proof_image_path'])): 
                    // Check if it's a Cloudinary URL or local path
                    $proof_url = (strpos($pod['proof_image_path'], 'http') === 0) 
                        ? $pod['proof_image_path'] 
                        : '/' . $pod['proof_image_path'];
                ?>
                <div class="customer-details">
                    <h3>Proof of Delivery</h3>
                    <div class="detail-group">
                        <p><strong>Submitted:</strong> <?php echo date('F d, Y \a\t h:i A', strtotime($pod['submitted_at'])); ?></p>
                        <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($pod['submitted_by'] ?? 'Rider'); ?></p>
                        <div style="margin-top: 16px; text-align: center;">
                            <a href="<?php echo htmlspecialchars($proof_url); ?>" target="_blank" style="display: inline-block;">
                                <img src="<?php echo htmlspecialchars($proof_url); ?>" 
                                     alt="Proof of Delivery" 
                                     style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;"
                                     onmouseover="this.style.transform='scale(1.02)'"
                                     onmouseout="this.style.transform='scale(1)'"
                                     onerror="this.src='https://res.cloudinary.com/dvdccumbs/image/upload/c_fill,w_400,h_400,g_center/e_blur:1000,co_rgb:cccccc,b_rgb:f0f0f0/sample.jpg'">
                            </a>
                            <p style="margin-top: 8px; font-size: 13px; color: #666;">
                                Click image to view full size
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
