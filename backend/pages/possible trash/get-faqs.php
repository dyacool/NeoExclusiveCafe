<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../includes/database.php";

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Check if database connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }

    // Log connection status
    error_log("Database connection established successfully");

    // Prepare and execute the query
    $stmt = $pdo->query("SELECT id, question, answer FROM chatbot_faq ORDER BY id ASC");
    if ($stmt === false) {
        throw new Exception('Failed to execute query');
    }
    
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the number of FAQs found
    error_log("Number of FAQs found: " . count($faqs));

    echo json_encode([
        'success' => true,
        'faqs' => $faqs
    ]);
} catch (Exception $e) {
    error_log("Error in get-faqs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Check server error log for more information'
    ]);
} 