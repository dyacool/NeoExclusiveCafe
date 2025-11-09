<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Handle status updates (approve/reject from list)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $refund_id = (int)$_POST['refund_id'];
    $new_status = $_POST['new_status'] ?? '';
    $is_ajax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    
    $allowed_statuses = ['pending', 'approved', 'rejected', 'completed'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_sql = "UPDATE order_refunds SET refund_status = ?, updated_at = NOW() WHERE refund_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_status, $refund_id);
        $ok = mysqli_stmt_execute($update_stmt);
        $err = mysqli_error($conn);
        mysqli_stmt_close($update_stmt);
        
        // Log the activity
        if ($ok) {
            logAdminActivity($conn, 'UPDATE', "Changed refund request #$refund_id status to '$new_status'", 'order_refunds', $refund_id);
            
            // Create notification for refund status update
            try {
                require_once __DIR__ . "/../admin-includes/notifications/notification.php";
                $notificationHandler = new NotificationHandler($conn);
                
                // Get refund and customer details
                $refund_query = "SELECT r.order_id, o.customer_name, u.username 
                                FROM order_refunds r
                                LEFT JOIN orders o ON r.order_id = o.order_id
                                LEFT JOIN users u ON r.user_id = u.id
                                WHERE r.refund_id = ?";
                $refund_stmt = mysqli_prepare($conn, $refund_query);
                mysqli_stmt_bind_param($refund_stmt, "i", $refund_id);
                mysqli_stmt_execute($refund_stmt);
                $refund_result = mysqli_stmt_get_result($refund_stmt);
                $refund_data = mysqli_fetch_assoc($refund_result);
                mysqli_stmt_close($refund_stmt);
                
                if ($refund_data) {
                    $customer_name = $refund_data['customer_name'] ?? 'Unknown Customer';
                    $username = $refund_data['username'] ?? null;
                    $order_id = $refund_data['order_id'];
                    
                    $notificationHandler->createRefundNotification(
                        $refund_id,
                        $order_id,
                        'refund_status',
                        $customer_name,
                        $username,
                        $new_status
                    );
                }
                
            } catch (Exception $e) {
                error_log("Failed to create refund status notification: " . $e->getMessage());
            }
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok, 'error' => $ok ? null : ($err ?: 'Update failed')]);
            exit();
        }
        
        if ($ok) { 
            $success_message = "Refund status updated successfully!"; 
        } else { 
            $error_message = "Error updating refund status: " . $err; 
        }
    } else {
        if ($is_ajax) { 
            header('Content-Type: application/json'); 
            echo json_encode(['success'=>false,'error'=>'Invalid status']); 
            exit(); 
        }
        $error_message = "Invalid status selected.";
    }
}

// Check if order_refunds table exists
$table_check = "SHOW TABLES LIKE 'order_refunds'";
$table_result = $conn->query($table_check);
$table_exists = $table_result && $table_result->num_rows > 0;

// Pagination setup
$records_per_page = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$total_records = 0;
if ($table_exists) {
    $count_sql = "SELECT COUNT(*) as total FROM order_refunds";
    $count_result = mysqli_query($conn, $count_sql);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $total_records = $count_row['total'];
    }
}

$total_pages = ceil($total_records / $records_per_page);

// Fetch refund requests with pagination
if ($table_exists) {
    $sql = "SELECT 
                r.refund_id,
                r.order_id,
                r.user_id,
                r.refund_reason,
                r.refund_items,
                r.refund_note,
                r.proof_image,
                r.refund_amount,
                r.refund_status,
                r.admin_notes,
                r.created_at,
                r.updated_at,
                o.customer_name,
                o.customer_email,
                o.customer_contact,
                o.status as order_status,
                u.firstname,
                u.lastname,
                u.username,
                rv.voucher_code as refund_coupon_code,
                r.refund_amount as refund_coupon_amount
            FROM order_refunds r
            LEFT JOIN orders o ON r.order_id = o.order_id
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN refund_vouchers rv ON r.refund_id = rv.refund_id
            ORDER BY r.created_at DESC
            LIMIT $records_per_page OFFSET $offset";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        $error_message = "Error fetching refund requests: " . mysqli_error($conn);
        $result = false;
    }
} else {
    $result = false;
    $error_message = "Refund table does not exist. Please run the SQL script: sql_configs/create_order_refunds_table.sql";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Requests Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../bulks/bulk-order-lists.css">
    <style>
        .refund-container {
            font-family: "Inter", sans-serif;
        }
        
        .orders-table tbody tr {
            cursor: pointer;
            transition: background 0.15s;
        }
        
        .orders-table tbody tr:hover, 
        .orders-table tbody tr:focus {
            background: #f3f4f6;
            outline: none;
        }
        
        .ticket-number {
            font-weight: 600;
            color: var(--green-700);
            font-family: "Monaco", "Menlo", "Ubuntu Mono", monospace;
            font-size: 0.875rem;
        }
        
        .refund-amount {
            font-weight: 600;
            color: var(--green-700);
        }
        
        .status-rejected {
            background-color: #fef2f2;
            color: var(--red-600);
        }
        
        .reason-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
            letter-spacing: 0.025em;
            background-color: var(--gray-100);
            color: var(--gray-700);
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            margin-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .pagination-info {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background: white;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }

        .pagination-btn.active {
            background: var(--green-600);
            border-color: var(--green-600);
            color: white;
        }

        .pagination-btn.active:hover {
            background: var(--green-700);
            border-color: var(--green-700);
        }

        .pagination-btn i {
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="refund-container bulk-order-container">
        <div class="page-header">
            <div class="header-content">
                <p>Manage and track all refund requests submitted by customers</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php
        // Calculate statistics from all records (not just current page)
        $total_refunds = 0;
        $pending_refunds = 0;
        $approved_refunds = 0;
        $completed_refunds = 0;
        
        if ($table_exists) {
            // Get statistics for all records, not just current page
            $stats_sql = "SELECT refund_status, COUNT(*) as count FROM order_refunds GROUP BY refund_status";
            $stats_result = mysqli_query($conn, $stats_sql);
            
            if ($stats_result) {
                while ($stat_row = mysqli_fetch_assoc($stats_result)) {
                    switch ($stat_row['refund_status']) {
                        case 'pending':
                            $pending_refunds = $stat_row['count'];
                            break;
                        case 'approved':
                            $approved_refunds = $stat_row['count'];
                            break;
                        case 'completed':
                            $completed_refunds = $stat_row['count'];
                            break;
                    }
                    $total_refunds += $stat_row['count'];
                }
            }
        }
        ?>

        <!-- Statistics/Filter Buttons -->
        <div class="filter-section">
            <label class="filter-label">Filter by Status:</label>
            <div class="stats-grid">
                <button class="stat-card filter-btn active" onclick="filterRefunds('all', this)" data-filter="all">
                    <div class="stat-number"><?php echo $total_refunds; ?></div>
                    <div class="stat-label">Total Requests</div>
                </button>
                <button class="stat-card filter-btn" onclick="filterRefunds('pending', this)" data-filter="pending">
                    <div class="stat-number"><?php echo $pending_refunds; ?></div>
                    <div class="stat-label">Pending</div>
                </button>
                <button class="stat-card filter-btn" onclick="filterRefunds('approved', this)" data-filter="approved">
                    <div class="stat-number"><?php echo $approved_refunds; ?></div>
                    <div class="stat-label">Approved</div>
                </button>
                <button class="stat-card filter-btn" onclick="filterRefunds('completed', this)" data-filter="completed">
                    <div class="stat-number"><?php echo $completed_refunds; ?></div>
                    <div class="stat-label">Completed</div>
                </button>
            </div>
        </div>

        <!-- Refund Requests Table -->
        <div class="orders-table-container">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Ticket Number</th>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Total Items</th>
                            <th>Refund Amount</th>
                            <th>Refund Coupon</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($refund = mysqli_fetch_assoc($result)): ?>
                            <?php
                            // Get customer name
                            $customer_name = '';
                            if (!empty($refund['customer_name'])) {
                                $customer_name = $refund['customer_name'];
                            } elseif (!empty($refund['firstname']) && !empty($refund['lastname'])) {
                                $customer_name = $refund['firstname'] . ' ' . $refund['lastname'];
                            } elseif (!empty($refund['username'])) {
                                $customer_name = $refund['username'];
                            } else {
                                $customer_name = 'Guest User';
                            }
                            
                            // Parse refund items to count total items
                            $refund_items_array = json_decode($refund['refund_items'], true);
                            $total_items = 0;
                            if (is_array($refund_items_array)) {
                                foreach ($refund_items_array as $item) {
                                    $total_items += isset($item['quantity']) ? intval($item['quantity']) : 1;
                                }
                            }
                            
                            // Format ticket number
                            $ticket_number = 'RF-' . str_pad($refund['refund_id'], 6, '0', STR_PAD_LEFT);
                            ?>
                            <tr class="order-row" data-status="<?php echo strtolower($refund['refund_status']); ?>">
                                <td onclick="window.location.href='refund-details.php?id=<?php echo $refund['refund_id']; ?>'" style="cursor:pointer;">
                                    <div class="ticket-number">#<?php echo htmlspecialchars($ticket_number); ?></div>
                                </td>
                                <td onclick="window.location.href='refund-details.php?id=<?php echo $refund['refund_id']; ?>'" style="cursor:pointer;">
                                    <div class="order-id">#<?php echo htmlspecialchars($refund['order_id']); ?></div>
                                </td>
                                <td onclick="window.location.href='refund-details.php?id=<?php echo $refund['refund_id']; ?>'" style="cursor:pointer;">
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($customer_name); ?></div>
                                        <?php if (!empty($refund['customer_email'])): ?>
                                            <div class="username"><?php echo htmlspecialchars($refund['customer_email']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td onclick="window.location.href='refund-details.php?id=<?php echo $refund['refund_id']; ?>'" style="cursor:pointer;">
                                    <?php echo $total_items; ?>
                                </td>
                                <td onclick="window.location.href='refund-details.php?id=<?php echo $refund['refund_id']; ?>'" style="cursor:pointer;">
                                    <div class="refund-amount">₱<?php echo number_format($refund['refund_amount'], 2); ?></div>
                                </td>
                                <td onclick="window.location.href='refund-details.php?id=<?php echo $refund['refund_id']; ?>'" style="cursor:pointer;">
                                    <?php if (!empty($refund['refund_coupon_code'])): ?>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <div style="font-weight: 600; color: #059669; font-family: 'Monaco', monospace; font-size: 0.875rem;">
                                                <?php echo htmlspecialchars($refund['refund_coupon_code']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6b7280;">
                                                ₱<?php echo number_format($refund['refund_coupon_amount'], 2); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-size: 0.875rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td >
                                    <span class="status-badge status-<?php echo strtolower($refund['refund_status']); ?>">
                                        <?php echo ucfirst($refund['refund_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No refund requests found</h3>
                    <p>There are currently no refund requests in the system.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> refund requests</span>
                </div>
                
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1" class="pagination-btn">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?php echo ($page - 1); ?>" class="pagination-btn">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?>" class="pagination-btn">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?>" class="pagination-btn">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterRefunds(status, buttonElement) {
            // Remove active class from all filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            buttonElement.classList.add('active');
            
            // Get all refund rows
            const refundRows = document.querySelectorAll('.order-row');
            
            // Show/hide rows based on filter
            refundRows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
