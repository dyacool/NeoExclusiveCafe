<?php
// Simple test version
ob_start();
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';
ob_clean();

header('Content-Type: application/json');

try {
    // Basic validation
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Check session
    if (!SessionManager::isAdminLoggedIn()) {
        throw new Exception('Not logged in as admin');
    }
    
    // Check basic fields
    if (empty($_POST['title'])) {
        throw new Exception('Title is required');
    }
    
    if (empty($_POST['code'])) {
        throw new Exception('Code is required');
    }
    
    // Test database connection
    $conn = new mysqli("localhost", "root", "", "crud");
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'All basic checks passed',
        'received_data' => [
            'title' => $_POST['title'] ?? 'missing',
            'code' => $_POST['code'] ?? 'missing',
            'discount_type' => $_POST['discount_type'] ?? 'missing'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
?>
