<?php
// Start output buffering before anything else
ob_start();

// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Include authentication and database (this starts the session)
require_once __DIR__ . '/../../../admin-includes/config.php';
require_once __DIR__ . '/../../../admin-includes/database.php';

// Clear any buffered output AFTER includes
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check admin authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || 
    !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get database configuration from chatbot_settings table
    $query = "SELECT * FROM chatbot_database_settings ORDER BY updated_at DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        $config = !empty($settings['config_json']) ? json_decode($settings['config_json'], true) : [];
        $selectedTables = $config['selected_tables'] ?? [];
        
        $response = [
            'success' => true,
            'data' => [
                'type' => $settings['database_type'] ?? 'MySQL',
                'source' => $settings['source_name'] ?? 'Primary Database',
                'status' => $settings['status'] ?? 'active',
                'last_updated' => $settings['updated_at'] ?? date('Y-m-d H:i:s'),
                'selected_tables' => $selectedTables,
                'table_count' => count($selectedTables)
            ]
        ];
    } else {
        // Default settings if no configuration exists
        $response = [
            'success' => true,
            'data' => [
                'type' => 'MySQL',
                'source' => 'Primary Database',
                'status' => 'active',
                'last_updated' => date('Y-m-d H:i:s'),
                'selected_tables' => [],
                'table_count' => 0
            ]
        ];
    }
    
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Database preview error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load database information'
    ]);
}
?>
