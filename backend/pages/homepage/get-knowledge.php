<?php
// Prevent any PHP errors or warnings from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Always output JSON
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../admin-includes/database.php';

// Ensure no whitespace or output before this point
try {
    $query = "SELECT id, content FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'id' => $row['id'],
            'content' => $row['content']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'id' => null,
            'content' => ''
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 