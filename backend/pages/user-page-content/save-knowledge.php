<?php
// Prevent any PHP errors or warnings from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Always output JSON
header('Content-Type: application/json');

// Include admin authentication
require_once __DIR__ . '/../admin-includes/config.php';

// Ensure no whitespace or output before this point
try {
    require_once __DIR__ . '/../admin-includes/database.php';
    require_once __DIR__ . '/../admin-includes/activity-logger.php';

    // Check if content is provided
    if (!isset($_POST['content']) || empty(trim($_POST['content']))) {
        echo json_encode(['success' => false, 'error' => 'Content cannot be empty']);
        exit;
    }

    $content = trim($_POST['content']);

    // Check if we have an existing record to update or need to insert
    $checkQuery = "SELECT id FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult && $checkResult->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE chatbot_knowledge SET content = ?, updated_at = NOW() WHERE id = (SELECT id FROM (SELECT id FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1) as temp)");
        $stmt->bind_param("s", $content);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO chatbot_knowledge (content, created_at, updated_at) VALUES (?, NOW(), NOW())");
        $stmt->bind_param("s", $content);
    }
    
    if ($stmt->execute()) {
        // Log the activity (need to start session to get admin info)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['admin_id'])) {
            $action_type = ($checkResult && $checkResult->num_rows > 0) ? 'UPDATE' : 'CREATE';
            logAdminActivity($conn, $action_type, "Updated chatbot knowledge base", 'chatbot_knowledge', 1);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save knowledge base']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 
?>
