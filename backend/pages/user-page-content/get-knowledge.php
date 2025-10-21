<?php
// Prevent any PHP errors or warnings from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Always output JSON
header('Content-Type: application/json');

// Include admin authentication
require_once __DIR__ . '/../admin-includes/config.php';

try {
    require_once __DIR__ . '/../admin-includes/database.php';

    // Get the latest knowledge base content
    $query = "SELECT content FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'content' => $row['content']]);
    } else {
        // Return empty content if no records found
        echo json_encode(['success' => true, 'content' => '']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
