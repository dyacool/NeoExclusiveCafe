<?php
// Prevent any PHP errors or warnings from being displayed
error_reporting(0);
ini_set('display_errors', 0);

// Always output JSON
header('Content-Type: application/json');

// Skip admin authentication for now to debug
// require_once __DIR__ . '/../includes/admin-auth.php';

// Ensure no whitespace or output before this point
try {
    require_once __DIR__ . '/../includes/database.php';

    // Check if content is provided
    if (!isset($_POST['content']) || empty(trim($_POST['content']))) {
        echo json_encode(['success' => false, 'error' => 'Content cannot be empty']);
        exit;
    }

    $content = trim($_POST['content']);

    // Insert new knowledge base entry
    $stmt = $pdo->prepare("INSERT INTO chatbot_knowledge (content) VALUES (?)");
    $stmt->execute([$content]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} 