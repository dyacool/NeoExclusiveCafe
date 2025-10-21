<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: ../../login/admin/admin-login.php");
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

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

// Fetch all refund requests with order and customer information
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
                u.username
            FROM order_refunds r
            LEFT JOIN orders o ON r.order_id = o.order_id
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC";
    
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
            padding: 2rem;
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
    </style>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <div class="refund-container bulk-order-container">
        <div class="page-header">
            <div class="header-content">
                <h1>Refund Requests</h1>
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
        // Calculate statistics
        $total_refunds = 0;
        $pending_refunds = 0;
        $approved_refunds = 0;
        $completed_refunds = 0;
        
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)) {
                $total_refunds++;
                switch ($row['refund_status']) {
                    case 'pending':
                        $pending_refunds++;
                        break;
                    case 'approved':
                        $approved_refunds++;
                        break;
                    case 'completed':
                        $completed_refunds++;
                        break;
                }
            }
            mysqli_data_seek($result, 0);
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
                                <td>
                                    <select class="status-select-list status-badge status-<?php echo strtolower($refund['refund_status']); ?>" 
                                            data-refund-id="<?php echo (int)$refund['refund_id']; ?>" 
                                            onclick="event.stopPropagation();">
                                        <?php 
                                        $statuses = [
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                            'completed' => 'Completed',
                                        ];
                                        foreach ($statuses as $val => $label): 
                                        ?>
                                            <option value="<?php echo $val; ?>" <?php echo ($refund['refund_status'] === $val) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="saved-indicator" style="display:none; color:#16a34a; margin-left:6px;">
                                        <i class="fas fa-check"></i> Saved
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
    </div>

    <script>
        // Auto-save status changes from list
        (function(){
            function onChange(e){
                const select = e.target;
                if (!select.classList.contains('status-select-list')) return;
                
                const refundId = select.getAttribute('data-refund-id');
                const row = select.closest('tr');
                const saved = row ? row.querySelector('.saved-indicator') : null;
                
                const form = new FormData();
                form.append('action', 'update_status');
                form.append('is_ajax', '1');
                form.append('refund_id', refundId);
                form.append('new_status', select.value);
                
                fetch('', { method: 'POST', body: form })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success) {
                            if (saved) { 
                                saved.style.display = 'inline-flex'; 
                                setTimeout(() => saved.style.display='none', 1500); 
                            }
                            // Update select styling class to reflect status
                            select.className = 'status-select-list status-badge status-' + select.value;
                            row.setAttribute('data-status', select.value);
                        } else {
                            alert('Failed to update status: ' + (data && data.error ? data.error : 'Unknown error'));
                        }
                    })
                    .catch(() => alert('Request failed. Please try again.'));
            }
            document.addEventListener('change', onChange);
        })();
        
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
