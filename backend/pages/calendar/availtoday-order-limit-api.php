<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Admin authentication is handled by admin-auth.php include

// Debug logging
error_log("availtoday-order-limit-api.php called");
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

$action = $_GET['action'] ?? $_POST['action'] ?? '';

error_log("Action determined: " . ($action ?: 'EMPTY'));

if (empty($action)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Missing action parameter. Use ?action=get_limit or ?action=update_limit'
    ]);
    exit;
}

switch ($action) {
    case 'get_limit':
        getCurrentLimit();
        break;
    case 'update_limit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            updateLimit();
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request method. Use POST for update_limit']);
        }
        break;
    default:
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid action: ' . htmlspecialchars($action) . '. Valid actions are: get_limit, update_limit'
        ]);
        break;
}

function getCurrentLimit() {
    global $conn;
    
    try {
        // Check if table exists, if not create it
        $checkTable = "SHOW TABLES LIKE 'availtoday_order_limit'";
        $tableExists = $conn->query($checkTable);
        
        if ($tableExists->num_rows == 0) {
            // Create table if it doesn't exist
            $createTable = "CREATE TABLE availtoday_order_limit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                limit_orders INT NOT NULL DEFAULT 50,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($createTable);
            
            // Insert default value
            $insertDefault = "INSERT INTO availtoday_order_limit (limit_orders) VALUES (50)";
            $conn->query($insertDefault);
            
            echo json_encode(['success' => true, 'limit_orders' => 50]);
            return;
        }
        
        // Get the latest limit (highest ID)
        $query = "SELECT limit_orders FROM availtoday_order_limit ORDER BY id DESC LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'limit_orders' => (int)$row['limit_orders']]);
        } else {
            // Insert default if no records exist
            $insertDefault = "INSERT INTO availtoday_order_limit (limit_orders) VALUES (50)";
            $conn->query($insertDefault);
            echo json_encode(['success' => true, 'limit_orders' => 50]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateLimit() {
    global $conn;
    
    try {
        $limit = $_POST['limit'] ?? null;
        
        if ($limit === null || !is_numeric($limit) || $limit < 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid limit value']);
            return;
        }
        
        $limit = (int)$limit;
        
        // Check if table exists, if not create it
        $checkTable = "SHOW TABLES LIKE 'availtoday_order_limit'";
        $tableExists = $conn->query($checkTable);
        
        if ($tableExists->num_rows == 0) {
            // Create table if it doesn't exist
            $createTable = "CREATE TABLE availtoday_order_limit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                limit_orders INT NOT NULL DEFAULT 50,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($createTable);
        }
        
        // Insert new limit record (auto-increment ID)
        $query = "INSERT INTO availtoday_order_limit (limit_orders) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'limit_orders' => $limit]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update limit']);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
