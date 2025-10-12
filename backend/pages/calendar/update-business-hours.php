<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Set header for JSON response
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$openingTime = $input['openingTime'] ?? null;
$closingTime = $input['closingTime'] ?? null;

// Debug: Log the received values
error_log("Received opening time: " . $openingTime);
error_log("Received closing time: " . $closingTime);

// Validate input
if (!$openingTime || !$closingTime) {
    echo json_encode(['success' => false, 'error' => 'Opening and closing times are required']);
    exit;
}

// Normalize time format - ensure it's HH:MM
function normalizeTime($time) {
    // Remove any extra spaces
    $time = trim($time);
    
    // If it's already in HH:MM format, return as is
    if (preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        return $time;
    }
    
    // Try to parse other formats
    $parsed = date_parse($time);
    if ($parsed && $parsed['hour'] !== false && $parsed['minute'] !== false) {
        return sprintf('%02d:%02d', $parsed['hour'], $parsed['minute']);
    }
    
    return false;
}

$normalizedOpeningTime = normalizeTime($openingTime);
$normalizedClosingTime = normalizeTime($closingTime);

if ($normalizedOpeningTime === false || $normalizedClosingTime === false) {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid time format. Received: ' . $openingTime . ' and ' . $closingTime . '. Expected format: HH:MM'
    ]);
    exit;
}

// Use normalized times for further processing
$openingTime = $normalizedOpeningTime;
$closingTime = $normalizedClosingTime;

// Validate that closing time is after opening time
// Allow special case where both times are 00:00 to indicate closed system
if (!($openingTime === '00:00' && $closingTime === '00:00') && $openingTime >= $closingTime) {
    echo json_encode(['success' => false, 'error' => 'Closing time must be after opening time']);
    exit;
}

try {
    // Check if business_hours table exists, if not create it
    $checkTableQuery = "SHOW TABLES LIKE 'business_hours'";
    $tableExists = $conn->query($checkTableQuery);
    
    if ($tableExists->num_rows == 0) {
        // Create business_hours table
        $createTableQuery = "CREATE TABLE business_hours (
            id INT AUTO_INCREMENT PRIMARY KEY,
            opening_time TIME NOT NULL,
            closing_time TIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($createTableQuery)) {
            throw new Exception('Failed to create business_hours table');
        }
    }
    
    // Upsert latest record (update newest row if exists, else insert)
    $latestQuery = "SELECT id FROM business_hours ORDER BY id DESC LIMIT 1";
    $latestResult = $conn->query($latestQuery);

    if ($latestResult && $latestResult->num_rows > 0) {
        $row = $latestResult->fetch_assoc();
        $latestId = (int)$row['id'];
        $updateQuery = "UPDATE business_hours SET opening_time = ?, closing_time = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $openingTime, $closingTime, $latestId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update business hours');
        }
    } else {
        $insertQuery = "INSERT INTO business_hours (opening_time, closing_time) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ss", $openingTime, $closingTime);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert business hours');
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Business hours updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
