<?php
// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Clear any previous output
if (ob_get_level()) ob_end_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Add this at the very top of the file, before any output
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

class CafeChatbot {
    private $apiKey;
    private $useOpenAI = false;
    private $useCohere = true;
    private $cohereApiKey = '';
    private $cohereModel = 'command-a-03-2025';
    private $customApiEndpoint = '';
    private $debugMode = true;
    
    public function __construct() {
        // Get API key from environment variable or hardcoded key for now
        $this->apiKey = '1WqvRzm21WGfExW9OYFfHOyla0tM77FI7tmZwBCr';
        $this->cohereApiKey = $this->apiKey;
        
        if ($this->debugMode) {
            error_log('CafeChatbot initialized with Cohere API key length: ' . strlen($this->cohereApiKey));
        }
    }

    public function setUseOpenAI($useOpenAI) {
        $this->useOpenAI = (bool)$useOpenAI;
    }

    public function setUseCohere($useCohere) {
        $this->useCohere = (bool)$useCohere;
    }

    public function setCohereApiKey($apiKey) {
        $this->cohereApiKey = $apiKey;
    }

    public function setCohereModel($model) {
        $this->cohereModel = $model;
    }

    public function setCustomApiEndpoint($endpoint) {
        $this->customApiEndpoint = $endpoint;
    }

    private $responses = [];

    public function getResponse($userInput) {
        // Always log this request
        error_log("CHATBOT REQUEST: " . $userInput);
        $userInput = "Based on this info:\n" . $this->getKnowledgeBaseContent() . "\n\nUser: " . $userInput;

        try {
            $response = $this->getCohereResponse($userInput);
            if (!empty($response)) {
                error_log("COHERE RESPONSE: " . substr($response, 0, 100) . "...");
            } else {
                error_log("COHERE RESPONSE: empty or null");
            }
            return $response;
        } catch (Exception $e) {
            error_log("COHERE ERROR: " . $e->getMessage());
            return "Error connecting to my knowledge base: " . $e->getMessage();
        }
    }

    private function getCohereResponse($userInput) {
        error_log('Starting getCohereResponse with input: ' . $userInput);
        
        if (empty($this->cohereApiKey)) {
            error_log('ERROR: No API key configured');
            throw new Exception("No API key configured");
        }

        $url = 'https://api.cohere.ai/v1/chat';
        
        error_log('API URL: ' . $url);
        
        // Format the input for chat completion
        $data = [
            'message' => $userInput,
            'chat_history' => [],
            'model' => $this->cohereModel
        ];

        error_log('Request data: ' . json_encode($data));

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->cohereApiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);  // Increased timeout
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            error_log('Sending request to Cohere API...');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                error_log('CURL Error: ' . $error);
                curl_close($ch);
                throw new Exception("Connection error: " . $error);
            }
            
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    error_log("Raw response: " . $response);
                    throw new Exception("Invalid JSON response from API");
                }
                
                if (isset($result['text'])) {
                    return $result['text'];
                } else {
                    error_log("Unexpected format in Cohere response: " . $response);
                    throw new Exception("Unexpected response format from model");
                }
            } else {
                error_log('API Error Response: ' . $response);
                throw new Exception("API error (HTTP $httpCode): " . $response);
            }
        } catch (Exception $e) {
            error_log('Exception caught: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getKnowledgeBaseContent() {
        try {
            require __DIR__ . '/database.php';
            $stmt = $pdo->query("SELECT content FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1");
            $row = $stmt->fetch();
            
            if ($row && !empty($row['content'])) {
                error_log('Successfully retrieved knowledge base content, length: ' . strlen($row['content']));
                return $row['content'];
            } else {
                error_log('Knowledge base is empty or not found');
                return '';
            }
        } catch (Exception $e) {
            error_log('Error retrieving knowledge base: ' . $e->getMessage());
            return '';
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $chatbot = new CafeChatbot();
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            throw new Exception('No message provided');
        }
        
        $response = $chatbot->getResponse($message);
        echo json_encode(['response' => $response]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}
