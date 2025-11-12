<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

header('Content-Type: application/json');


if (!SessionManager::isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


// Include database configuration
require_once __DIR__ . '/database-config.php';
require_once __DIR__ . '/../admin-includes/activity-logger.php';

$conn = getDBConnection();
createPromotionsTable($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'datatableDisplay':
        handleDataTableDisplay($conn);
        break;
    case 'getFilterCounts':
        handleGetFilterCounts($conn);
        break;
    case 'add_voucher':
        handleAddVoucher($conn);
        break;
    case 'update_voucher':
        handleUpdateVoucher($conn);
        break;
    case 'delete_voucher':
        handleDeleteVoucher($conn);
        break;
    case 'getVoucherDetails':
        handleGetVoucherDetails($conn);
        break;
    case 'reactivate_voucher':
        handleReactivateVoucher($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleDataTableDisplay($conn) {
    // Update expired vouchers before displaying
    updateExpiredVouchers($conn);
    
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $searchValue = $_POST['search']['value'] ?? '';
    

    $voucherType = $_POST['voucher_type'] ?? '';
    $valueMin = $_POST['value_min'] ?? '';
    $valueMax = $_POST['value_max'] ?? '';
    $minPurchaseMin = $_POST['min_purchase_min'] ?? '';
    $minPurchaseMax = $_POST['min_purchase_max'] ?? '';
    $status = $_POST['status'] ?? '';
    $appliesTo = $_POST['applies_to'] ?? '';
    $usageLimitMin = $_POST['usage_limit_min'] ?? '';
    $usageLimitMax = $_POST['usage_limit_max'] ?? '';
    $usageLimitType = $_POST['usage_limit_type'] ?? '';
    $usageLimitUserMin = $_POST['usage_limit_user_min'] ?? '';
    $usageLimitUserMax = $_POST['usage_limit_user_max'] ?? '';
    $usageLimitUserType = $_POST['usage_limit_user_type'] ?? '';
    $validityFrom = $_POST['validity_from'] ?? '';
    $validityTo = $_POST['validity_to'] ?? '';
    

    $whereConditions = [];
    $params = [];
    $paramTypes = '';
    

    if (!empty($searchValue)) {
        $whereConditions[] = "(title LIKE ? OR code LIKE ?)";
        $searchParam = "%$searchValue%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $paramTypes .= 'ss';
    }
    

    if (!empty($voucherType)) {
        $whereConditions[] = "type = ?";
        $params[] = $voucherType;
        $paramTypes .= 's';
    }
    
    if (!empty($valueMin)) {
        $whereConditions[] = "value >= ?";
        $params[] = $valueMin;
        $paramTypes .= 'd';
    }
    
    if (!empty($valueMax)) {
        $whereConditions[] = "value <= ?";
        $params[] = $valueMax;
        $paramTypes .= 'd';
    }
    
    if (!empty($minPurchaseMin)) {
        $whereConditions[] = "min_purchase >= ?";
        $params[] = $minPurchaseMin;
        $paramTypes .= 'd';
    }
    
    if (!empty($minPurchaseMax)) {
        $whereConditions[] = "min_purchase <= ?";
        $params[] = $minPurchaseMax;
        $paramTypes .= 'd';
    }
    
    if (!empty($status)) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
        $paramTypes .= 's';
    }
    
    if (!empty($appliesTo)) {
        $whereConditions[] = "applicable_to = ?";
        $params[] = $appliesTo;
        $paramTypes .= 's';
    }
    
    if (!empty($usageLimitMin)) {
        $whereConditions[] = "usage_limit >= ?";
        $params[] = $usageLimitMin;
        $paramTypes .= 'i';
    }
    
    if (!empty($usageLimitMax)) {
        $whereConditions[] = "usage_limit <= ?";
        $params[] = $usageLimitMax;
        $paramTypes .= 'i';
    }
    
    if (!empty($usageLimitType)) {
        if ($usageLimitType === 'unlimited') {
            $whereConditions[] = "usage_limit IS NULL";
        } elseif ($usageLimitType === 'limited') {
            $whereConditions[] = "usage_limit IS NOT NULL";
        }
    }
    
    if (!empty($usageLimitUserMin)) {
        $whereConditions[] = "usage_limit_per_user >= ?";
        $params[] = $usageLimitUserMin;
        $paramTypes .= 'i';
    }
    
    if (!empty($usageLimitUserMax)) {
        $whereConditions[] = "usage_limit_per_user <= ?";
        $params[] = $usageLimitUserMax;
        $paramTypes .= 'i';
    }
    
    if (!empty($usageLimitUserType)) {
        if ($usageLimitUserType === 'unlimited') {
            $whereConditions[] = "usage_limit_per_user IS NULL";
        } elseif ($usageLimitUserType === 'limited') {
            $whereConditions[] = "usage_limit_per_user IS NOT NULL";
        }
    }
    
    if (!empty($validityFrom)) {
        $whereConditions[] = "activation_date >= ?";
        $params[] = $validityFrom;
        $paramTypes .= 's';
    }
    
    if (!empty($validityTo)) {
        $whereConditions[] = "expiration_date <= ?";
        $params[] = $validityTo;
        $paramTypes .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    

    $countSql = "SELECT COUNT(*) as total FROM promotions $whereClause";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($paramTypes, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
    

    $filteredCount = $totalRecords;
    

    $sql = "SELECT * FROM promotions $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;
    $paramTypes .= 'ii';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {

        $currentDate = date('Y-m-d');
        $status = $row['status'];
        
        if ($row['activation_date'] > $currentDate) {
            $status = 'upcoming';
        } elseif ($row['expiration_date'] < $currentDate) {
            $status = 'expired';
        } elseif ($row['status'] === 'active') {
            $status = 'active';
        }
        

        $discount = '';
        if ($row['type'] === 'percentage') {
            $discount = $row['value'] . '%';
        } elseif ($row['type'] === 'fixed') {
            $discount = '₱' . number_format($row['value'], 2);
        } else {
            $discount = 'Free Shipping Only';
        }
        

        if ($row['include_free_shipping'] == 1 && $row['type'] !== 'free_shipping') {
            $discount .= '<br><span class="status-badge" style="background:#E3FCF4;color:#039855;font-size:12px;padding:2px 8px;border-radius:12px;">Free Shipping</span>';
        }
        
        $restrictions = [];
        if ($row['min_purchase'] > 0) {
            $restrictions[] = 'Min: ₱' . number_format($row['min_purchase'], 2);
        }
        if ($row['usage_limit']) {
            $restrictions[] = 'Limit: ' . $row['usage_limit'];
        }
        if ($row['usage_limit_per_user']) {
            $restrictions[] = 'Per user: ' . $row['usage_limit_per_user'];
        }
        $restrictionsText = !empty($restrictions) ? implode(', ', $restrictions) : 'None';
        
        $usageLimit = $row['usage_limit'] ? $row['usage_limit'] : '∞';
        $usagePerUser = $row['usage_limit_per_user'] ? $row['usage_limit_per_user'] : '∞';
        $usage = $row['used_count'] . ' / ' . $usageLimit . ' (Per user: ' . $usagePerUser . ')';
        
  
        $validPeriod = date('M j, Y', strtotime($row['activation_date'])) . ' - ' . date('M j, Y', strtotime($row['expiration_date']));
        
  
        $applicationMethod = $row['application_method'] === 'voucher_code' ? 'Voucher Code' : 'Automatic Discount';
        
        $data[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'application_method' => $applicationMethod,
            'code' => $row['code'],
            'discount' => $discount,
            'restrictions' => $restrictionsText,
            'usage' => $usage,
            'valid_period' => $validPeriod,
            'sale_channel' => 'Online', 
            'status' => $status,
            'type' => $row['type'],
            'value' => $row['value'],
            'min_purchase' => $row['min_purchase'],
            'applicable_to' => $row['applicable_to'],
            'usage_limit' => $row['usage_limit'],
            'usage_limit_per_user' => $row['usage_limit_per_user'],
            'used_count' => $row['used_count'],
            'activation_date' => $row['activation_date'],
            'expiration_date' => $row['expiration_date'],
            'include_free_shipping' => $row['include_free_shipping'],
            'prevent_discounted' => $row['prevent_discounted']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredCount,
        'data' => $data
    ]);
}

function handleAddVoucher($conn) {
    $title = trim($_POST['title']);
    $code = strtoupper(trim($_POST['code']));
    $application_method = $_POST['application_method'] ?? 'voucher_code';
    $type = $_POST['type'];
    $value = !empty($_POST['value']) ? floatval($_POST['value']) : null;
    $min_purchase = floatval($_POST['min_purchase'] ?? 0);
    $applicable_to = $_POST['applicable_to'] ?? 'all';
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $usage_limit_per_user = !empty($_POST['usage_limit_per_user']) ? intval($_POST['usage_limit_per_user']) : null;
    $activation_date = $_POST['activation_date'];
    $expiration_date = $_POST['expiration_date'];
    $include_free_shipping = isset($_POST['include_free_shipping']) ? 1 : 0;
    $prevent_discounted = isset($_POST['prevent_discounted']) ? 1 : 0;
    

    if (empty($title) || empty($code) || empty($type) || empty($activation_date) || empty($expiration_date)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit();
    }
    
  
    $check_sql = "SELECT id FROM promotions WHERE code = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Coupon code already exists']);
        exit();
    }
    
   
    if ($type !== 'free_shipping') {
        if ($value === null || $value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Discount value must be greater than 0']);
            exit();
        }
        
        if ($type === 'percentage' && $value > 100) {
            echo json_encode(['success' => false, 'message' => 'Percentage discount cannot be more than 100%']);
            exit();
        }
    } else {
        $value = null; 
    }
    

    if (strtotime($activation_date) >= strtotime($expiration_date)) {
        echo json_encode(['success' => false, 'message' => 'Expiration date must be after activation date']);
        exit();
    }
    
 
    $columns_result = $conn->query("SHOW COLUMNS FROM promotions");
    $existing_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    

    $columns = ['title', 'code', 'applicable_to', 'usage_limit', 'usage_limit_per_user', 'used_count', 'status'];
    $values = [$title, $code, $applicable_to, $usage_limit, $usage_limit_per_user, 0, 'active'];
    $placeholders = ['s', 's', 's', 'i', 'i', 'i', 's'];
    
 
    if (in_array('application_method', $existing_columns)) {
        $columns[] = 'application_method';
        $values[] = $application_method;
        $placeholders[] = 's';
    }
    
    if (in_array('type', $existing_columns)) {
        $columns[] = 'type';
        $values[] = $type;
        $placeholders[] = 's';
    } elseif (in_array('discount_type', $existing_columns)) {
        $columns[] = 'discount_type';
        $values[] = $type;
        $placeholders[] = 's';
    }
    
    if (in_array('value', $existing_columns)) {
        $columns[] = 'value';
        $values[] = $value;
        $placeholders[] = 'd';
    } elseif (in_array('discount_value', $existing_columns)) {
        $columns[] = 'discount_value';
        $values[] = $value;
        $placeholders[] = 'd';
    }
    
    if (in_array('min_purchase', $existing_columns)) {
        $columns[] = 'min_purchase';
        $values[] = $min_purchase;
        $placeholders[] = 'd';
    } elseif (in_array('min_spend', $existing_columns)) {
        $columns[] = 'min_spend';
        $values[] = $min_purchase;
        $placeholders[] = 'd';
    }
    
    if (in_array('activation_date', $existing_columns)) {
        $columns[] = 'activation_date';
        $values[] = $activation_date;
        $placeholders[] = 's';
    } elseif (in_array('start_date', $existing_columns)) {
        $columns[] = 'start_date';
        $values[] = $activation_date;
        $placeholders[] = 's';
    }
    
    if (in_array('expiration_date', $existing_columns)) {
        $columns[] = 'expiration_date';
        $values[] = $expiration_date;
        $placeholders[] = 's';
    } elseif (in_array('end_date', $existing_columns)) {
        $columns[] = 'end_date';
        $values[] = $expiration_date;
        $placeholders[] = 's';
    }
    
    if (in_array('include_free_shipping', $existing_columns)) {
        $columns[] = 'include_free_shipping';
        $values[] = $include_free_shipping;
        $placeholders[] = 'i';
    }
    
    if (in_array('prevent_discounted', $existing_columns)) {
        $columns[] = 'prevent_discounted';
        $values[] = $prevent_discounted;
        $placeholders[] = 'i';
    }
    

    $columns_str = implode(', ', $columns);
    $placeholders_str = str_repeat('?,', count($placeholders) - 1) . '?';
    $sql = "INSERT INTO promotions ($columns_str) VALUES ($placeholders_str)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(implode('', $placeholders), ...$values);
    
    if ($stmt->execute()) {
        $new_promo_id = $conn->insert_id;
        logAdminActivity($conn, 'CREATE', "Created promotion/coupon: {$data['coupon_code']}", 'promotions', $new_promo_id);
        echo json_encode(['success' => true, 'message' => 'Coupon created successfully', 'id' => $new_promo_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating coupon: ' . $conn->error]);
    }
    
    $stmt->close();
}

function handleUpdateVoucher($conn) {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $code = strtoupper(trim($_POST['code']));
    $application_method = $_POST['application_method'] ?? 'voucher_code';
    $type = $_POST['type'];
    $value = !empty($_POST['value']) ? floatval($_POST['value']) : null;
    $min_purchase = floatval($_POST['min_purchase'] ?? 0);
    $applicable_to = $_POST['applicable_to'] ?? 'all';
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $usage_limit_per_user = !empty($_POST['usage_limit_per_user']) ? intval($_POST['usage_limit_per_user']) : null;
    $activation_date = $_POST['activation_date'];
    $expiration_date = $_POST['expiration_date'];
    $status = $_POST['status'] ?? 'active';
    $include_free_shipping = isset($_POST['include_free_shipping']) ? 1 : 0;
    $prevent_discounted = isset($_POST['prevent_discounted']) ? 1 : 0;
    
 
    if (empty($title) || empty($code) || empty($type) || empty($activation_date) || empty($expiration_date)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit();
    }
    

    $check_sql = "SELECT id FROM promotions WHERE code = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $code, $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Coupon code already exists']);
        exit();
    }
    
   
    if ($type !== 'free_shipping') {
        if ($value === null || $value <= 0) {
            echo json_encode(['success' => false, 'message' => 'Discount value must be greater than 0']);
            exit();
        }
        
        if ($type === 'percentage' && $value > 100) {
            echo json_encode(['success' => false, 'message' => 'Percentage discount cannot be more than 100%']);
            exit();
        }
    } else {
        $value = null; 
    }
    
   
    if (strtotime($activation_date) >= strtotime($expiration_date)) {
        echo json_encode(['success' => false, 'message' => 'Expiration date must be after activation date']);
        exit();
    }
    

    $sql = "UPDATE promotions SET 
            title = ?, 
            code = ?, 
            application_method = ?, 
            type = ?, 
            value = ?, 
            min_purchase = ?, 
            applicable_to = ?, 
            usage_limit = ?, 
            usage_limit_per_user = ?, 
            activation_date = ?, 
            expiration_date = ?, 
            status = ?,
            include_free_shipping = ?,
            prevent_discounted = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssddsiisssiii", $title, $code, $application_method, $type, $value, $min_purchase, $applicable_to, $usage_limit, $usage_limit_per_user, $activation_date, $expiration_date, $status, $include_free_shipping, $prevent_discounted, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Coupon updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or coupon not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating coupon: ' . $conn->error]);
    }
    
    $stmt->close();
}

function handleDeleteVoucher($conn) {
    $id = $_POST['id'] ?? null;
    
    // Get coupon code for logging
    $get_coupon_query = "SELECT coupon_code FROM promotions WHERE id = ?";
    $get_coupon_stmt = $conn->prepare($get_coupon_query);
    $get_coupon_stmt->bind_param("i", $id);
    $get_coupon_stmt->execute();
    $coupon_result = $get_coupon_stmt->get_result();
    $coupon_data = $coupon_result->fetch_assoc();
    $get_coupon_stmt->close();
    
    $sql = "DELETE FROM promotions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            if ($coupon_data) {
                logAdminActivity($conn, 'DELETE', "Deleted promotion/coupon: {$coupon_data['coupon_code']}", 'promotions', $id);
            }
            echo json_encode(['success' => true, 'message' => 'Coupon deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Coupon not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting coupon: ' . $conn->error]);
    }
    
    $stmt->close();
}

function handleGetVoucherDetails($conn) {
    $voucher_id = intval($_POST['voucher_id']);
    
    $sql = "SELECT * FROM promotions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voucher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $voucher = $result->fetch_assoc();
        
    
        $currentDate = date('Y-m-d');
        $status = $voucher['status'];
        
        if ($voucher['activation_date'] > $currentDate) {
            $status = 'upcoming';
        } elseif ($voucher['expiration_date'] < $currentDate) {
            $status = 'expired';
        }
        
        $voucher['status'] = $status;
        
        echo json_encode(['success' => true, ...$voucher]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Voucher not found']);
    }
    
    $stmt->close();
}

function handleReactivateVoucher($conn) {
    $voucher_id = intval($_POST['voucher_id']);
    $activation_date = $_POST['activation_date'];
    $expiration_date = $_POST['expiration_date'];
    
    // Validate voucher_id is provided and not zero
    if (empty($voucher_id) || $voucher_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid voucher ID']);
        exit();
    }
 
    if (strtotime($activation_date) >= strtotime($expiration_date)) {
        echo json_encode(['success' => false, 'message' => 'Expiration date must be after activation date']);
        exit();
    }
    
    // Use explicit WHERE clause to ensure only one voucher is updated
    $sql = "UPDATE promotions 
            SET activation_date = ?, 
                expiration_date = ?, 
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND id > 0";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("ssi", $activation_date, $expiration_date, $voucher_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Voucher reactivated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Voucher not found or no changes made']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error reactivating voucher: ' . $conn->error]);
    }
    
    $stmt->close();
}

function handleGetFilterCounts($conn) {
    // First, update expired vouchers
    updateExpiredVouchers($conn);
    
    // Get counts for all filter categories
    $counts = [
        'all' => 0,
        'active' => 0,
        'expired' => 0,
        'fixed' => 0,
        'percentage' => 0,
        'free_shipping' => 0
    ];
    
    // Count all vouchers
    $sql = "SELECT COUNT(*) as count FROM promotions";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['all'] = (int)$row['count'];
    }
    
    // Count by status - active (case-insensitive)
    $sql = "SELECT COUNT(*) as count FROM promotions WHERE LOWER(status) = 'active'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['active'] = (int)$row['count'];
    }
    
    // Count by status - expired (case-insensitive)
    $sql = "SELECT COUNT(*) as count FROM promotions WHERE LOWER(status) = 'expired'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['expired'] = (int)$row['count'];
    }
    
    // Count by type - fixed
    $sql = "SELECT COUNT(*) as count FROM promotions WHERE type = 'fixed'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['fixed'] = (int)$row['count'];
    }
    
    // Count by type - percentage
    $sql = "SELECT COUNT(*) as count FROM promotions WHERE type = 'percentage'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['percentage'] = (int)$row['count'];
    }
    
    // Count by type - free_shipping
    $sql = "SELECT COUNT(*) as count FROM promotions WHERE type = 'free_shipping'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $counts['free_shipping'] = (int)$row['count'];
    }
    
    echo json_encode(['success' => true, 'counts' => $counts]);
}

// Helper function to update expired vouchers
function updateExpiredVouchers($conn) {
    $today = date('Y-m-d');
    $sql = "UPDATE promotions 
            SET status = 'expired' 
            WHERE expiration_date < ? 
            AND status = 'active'";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $stmt->close();
    }
}
$conn->close();
?>
