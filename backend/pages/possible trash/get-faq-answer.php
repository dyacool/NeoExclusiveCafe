<?php
// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . "/../includes/database.php";

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Check if database connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }

    // Get the raw POST data
    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('Failed to read input data');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    if (!isset($data['question'])) {
        throw new Exception('Question is required');
    }

    $question = $data['question'];

    // Prepare and execute the query
    $stmt = $pdo->prepare("SELECT answer FROM chatbot_faq WHERE question = ?");
    if ($stmt === false) {
        throw new Exception('Failed to prepare query');
    }

    $result = $stmt->execute([$question]);
    if ($result === false) {
        throw new Exception('Failed to execute query');
    }

    $answer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($answer) {
        echo json_encode([
            'success' => true,
            'answer' => $answer['answer']
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'No answer found for this question'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}