<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

session_start();

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../admin-includes/database.php';
require_once __DIR__ . '/../admin-includes/activity-logger.php';

// Direct database connection (avoid getDBConnection which uses die())
$servername = "mysql-neoexclusivecafe.alwaysdata.net";
$username = "429123";
$password = "NeoCafe123";
$dbname = "neoexclusivecafe_crud";

$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

@$conn->set_charset("utf8");

// Check if delivery_locations table exists, if not create it
$table_check = "SHOW TABLES LIKE 'delivery_locations'";
$table_result = $conn->query($table_check);

if ($table_result->num_rows == 0) {
    // Create table if it doesn't exist
    $create_table = "CREATE TABLE delivery_locations (
        delivery_id INT AUTO_INCREMENT PRIMARY KEY,
        municipality VARCHAR(255) NOT NULL,
        city VARCHAR(255) NOT NULL,
        postal_code VARCHAR(4) NOT NULL,
        delivery_fee DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create table: ' . $conn->error]);
        exit();
    }
}

// Get the action
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        ob_end_clean();
        addLocation($conn);
        break;
    case 'update':
        ob_end_clean();
        updateLocation($conn);
        break;
    case 'delete':
        ob_end_clean();
        deleteLocation($conn);
        break;
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

exit();

function addLocation($conn) {
    // Validate required fields
    $municipality = trim($_POST['municipality'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $delivery_fee = $_POST['delivery_fee'] ?? '';
    
    if (empty($municipality) || empty($city) || empty($postal_code) || empty($delivery_fee)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate postal code (4 digits only)
    if (!preg_match('/^[0-9]{4}$/', $postal_code)) {
        echo json_encode(['success' => false, 'message' => 'Postal code must be exactly 4 digits']);
        return;
    }
    
    // Validate delivery fee (positive number)
    if (!is_numeric($delivery_fee) || $delivery_fee < 0) {
        echo json_encode(['success' => false, 'message' => 'Delivery fee must be a positive number']);
        return;
    }
    
    // Check for duplicate postal code
    $check_stmt = $conn->prepare("SELECT delivery_id FROM delivery_locations WHERE postal_code = ?");
    $check_stmt->bind_param("s", $postal_code);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A location with this postal code already exists']);
        $check_stmt->close();
        return;
    }
    $check_stmt->close();
    
    // Insert new location
    $stmt = $conn->prepare("INSERT INTO delivery_locations (municipality, city, postal_code, delivery_fee) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $municipality, $city, $postal_code, $delivery_fee);
    
    if ($stmt->execute()) {
        $new_location_id = $conn->insert_id;
        logAdminActivity($conn, 'CREATE', "Added delivery location: $municipality, $city ($postal_code)", 'delivery_locations', $new_location_id);
        echo json_encode(['success' => true, 'message' => 'Location added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add location: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function updateLocation($conn) {
    // Validate required fields
    $delivery_id = $_POST['delivery_id'] ?? '';
    $municipality = trim($_POST['municipality'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $delivery_fee = $_POST['delivery_fee'] ?? '';
    
    if (empty($delivery_id) || empty($municipality) || empty($city) || empty($postal_code) || empty($delivery_fee)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate postal code (4 digits only)
    if (!preg_match('/^[0-9]{4}$/', $postal_code)) {
        echo json_encode(['success' => false, 'message' => 'Postal code must be exactly 4 digits']);
        return;
    }
    
    // Validate delivery fee (positive number)
    if (!is_numeric($delivery_fee) || $delivery_fee < 0) {
        echo json_encode(['success' => false, 'message' => 'Delivery fee must be a positive number']);
        return;
    }
    
    // Check for duplicate postal code (excluding current record)
    $check_stmt = $conn->prepare("SELECT delivery_id FROM delivery_locations WHERE postal_code = ? AND delivery_id != ?");
    $check_stmt->bind_param("si", $postal_code, $delivery_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A location with this postal code already exists']);
        $check_stmt->close();
        return;
    }
    $check_stmt->close();
    
    // Update location
    $stmt = $conn->prepare("UPDATE delivery_locations SET municipality = ?, city = ?, postal_code = ?, delivery_fee = ? WHERE delivery_id = ?");
    $stmt->bind_param("sssdi", $municipality, $city, $postal_code, $delivery_fee, $delivery_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            logAdminActivity($conn, 'UPDATE', "Updated delivery location: $municipality, $city ($postal_code)", 'delivery_locations', $delivery_id);
            echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or location not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update location: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function deleteLocation($conn) {
    $delivery_id = $_POST['delivery_id'] ?? '';
    
    if (empty($delivery_id) || !is_numeric($delivery_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid location ID']);
        return;
    }
    
    // Get location details for logging
    $get_location_query = "SELECT municipality, city, postal_code FROM delivery_locations WHERE delivery_id = ?";
    $get_location_stmt = $conn->prepare($get_location_query);
    $get_location_stmt->bind_param("i", $delivery_id);
    $get_location_stmt->execute();
    $location_result = $get_location_stmt->get_result();
    $location_data = $location_result->fetch_assoc();
    $get_location_stmt->close();
    
    // Delete location
    $stmt = $conn->prepare("DELETE FROM delivery_locations WHERE delivery_id = ?");
    $stmt->bind_param("i", $delivery_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            if ($location_data) {
                logAdminActivity($conn, 'DELETE', "Deleted delivery location: {$location_data['municipality']}, {$location_data['city']} ({$location_data['postal_code']})", 'delivery_locations', $delivery_id);
            }
            echo json_encode(['success' => true, 'message' => 'Location deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Location not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete location: ' . $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>
