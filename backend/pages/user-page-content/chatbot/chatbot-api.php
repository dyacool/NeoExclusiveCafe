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

    public function getResponse($userInput, $conversationHistory = []) {
        // Always log this request
        error_log("CHATBOT REQUEST: " . $userInput);
        
        // Build context-aware prompt
        $knowledgeBase = $this->getKnowledgeBaseContent();
        
        $systemPrompt = "You are a helpful AI assistant for NeoCafe, a coffee shop and cafe. ";
        $systemPrompt .= "Your role is to answer customer questions about products, orders, promotions, business hours, and general inquiries. ";
        $systemPrompt .= "IMPORTANT CONTEXT RULES:\n";
        $systemPrompt .= "1. When customers say 'yes', 'sure', 'okay', 'please' - they're agreeing to your previous question. Provide what they asked for.\n";
        $systemPrompt .= "2. When customers refer to 'number X', 'the Xth one', 'item X' - they mean item X from your previous list. Look back at what you just sent.\n";
        $systemPrompt .= "3. When customers say 'that one', 'it', 'the one you mentioned' - they mean the last product/item you mentioned.\n";
        $systemPrompt .= "4. Always maintain context from previous messages. Read the RECENT CONVERSATION carefully before responding.\n";
        $systemPrompt .= "5. Be conversational, friendly, and helpful. Format lists clearly with numbers.\n\n";
        $systemPrompt .= "KNOWLEDGE BASE:\n" . $knowledgeBase . "\n\n";
        
        // Add conversation history context if available
        if (!empty($conversationHistory)) {
            $systemPrompt .= "RECENT CONVERSATION:\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'Customer' : 'You';
                $systemPrompt .= "$role: " . $msg['message'] . "\n";
            }
            $systemPrompt .= "\n";
        }
        
        $systemPrompt .= "Customer's current question: " . $userInput;

        try {
            $response = $this->getCohereResponse($systemPrompt, $conversationHistory);
            if (!empty($response)) {
                error_log("COHERE RESPONSE: " . substr($response, 0, 100) . "...");
            } else {
                error_log("COHERE RESPONSE: empty or null");
            }
            return $response;
        } catch (Exception $e) {
            error_log("COHERE ERROR: " . $e->getMessage());
            return "I'm having trouble connecting to my knowledge base right now. Please try again in a moment.";
        }
    }

    private function getCohereResponse($userInput, $conversationHistory = []) {
        error_log('Starting getCohereResponse with input length: ' . strlen($userInput));
        
        if (empty($this->cohereApiKey)) {
            error_log('ERROR: No API key configured');
            throw new Exception("No API key configured");
        }

        $url = 'https://api.cohere.ai/v1/chat';
        
        // Convert conversation history to Cohere format
        $chatHistory = [];
        foreach ($conversationHistory as $msg) {
            $chatHistory[] = [
                'role' => $msg['role'] === 'user' ? 'USER' : 'CHATBOT',
                'message' => $msg['message']
            ];
        }
        
        // Format the input for chat completion
        $data = [
            'message' => $userInput,
            'chat_history' => $chatHistory,
            'model' => $this->cohereModel,
            'temperature' => 0.7,
            'max_tokens' => 500
        ];

        error_log('Request with history count: ' . count($chatHistory));

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
            require_once __DIR__ . '/../../admin-includes/database.php';
            
            // Define table relationships and what related tables to fetch
            $tableRelations = [
                'products' => ['related' => ['categories', 'product_images'], 'fetch_related' => true],
                'orders' => ['related' => ['order_items', 'products', 'users'], 'fetch_related' => true],
                'categories' => ['related' => ['products'], 'fetch_related' => true],
                'users' => ['related' => [], 'fetch_related' => false],
                'promotions' => ['related' => ['products'], 'fetch_related' => true],
                'business_hours' => ['related' => [], 'fetch_related' => false],
                'bulk_orders' => ['related' => ['products'], 'fetch_related' => true],
            ];
            
            // Get selected tables from settings
            $selectedTables = [];
            $stmt = $conn->prepare("SELECT config_json FROM chatbot_database_settings ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $settings = $result->fetch_assoc();
                if (!empty($settings['config_json'])) {
                    $config = json_decode($settings['config_json'], true);
                    $selectedTables = $config['selected_tables'] ?? [];
                }
            }
            
            // Automatically include related tables
            $allTablesToFetch = [];
            foreach ($selectedTables as $table) {
                $allTablesToFetch[] = $table;
                if (isset($tableRelations[$table]) && $tableRelations[$table]['fetch_related']) {
                    foreach ($tableRelations[$table]['related'] as $relatedTable) {
                        if (!in_array($relatedTable, $allTablesToFetch)) {
                            $allTablesToFetch[] = $relatedTable;
                        }
                    }
                }
            }
            
            // Get manual knowledge base content
            $knowledgeContent = "=== NeoCafe Knowledge Base ===\n\n";
            $stmt = $conn->prepare("SELECT content FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row && !empty($row['content'])) {
                $knowledgeContent .= "MANUAL KNOWLEDGE:\n" . $row['content'] . "\n\n";
                error_log('Manual knowledge base length: ' . strlen($row['content']));
            }
            
            // If tables are selected, fetch data from them
            if (!empty($allTablesToFetch)) {
                error_log('Tables to fetch for chatbot: ' . implode(', ', $allTablesToFetch));
                $knowledgeContent .= "=== DATABASE INFORMATION ===\n\n";
                
                foreach ($allTablesToFetch as $table) {
                    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
                    
                    try {
                        // Special handling for different tables
                        switch ($safeTable) {
                            case 'orders':
                                $knowledgeContent .= $this->getOrdersAnalysis($conn, $safeTable);
                                break;
                            case 'products':
                                $knowledgeContent .= $this->getProductsData($conn, $safeTable);
                                break;
                            case 'categories':
                                $knowledgeContent .= $this->getCategoriesData($conn, $safeTable);
                                break;
                            case 'users':
                                $knowledgeContent .= $this->getUsersData($conn, $safeTable);
                                break;
                            case 'promotions':
                                $knowledgeContent .= $this->getPromotionsData($conn, $safeTable);
                                break;
                            case 'business_hours':
                                $knowledgeContent .= $this->getBusinessHoursData($conn, $safeTable);
                                break;
                            default:
                                $knowledgeContent .= $this->getGenericTableData($conn, $safeTable);
                        }
                    } catch (Exception $tableError) {
                        error_log("Error fetching data from table $safeTable: " . $tableError->getMessage());
                    }
                }
            }
            
            if (empty($knowledgeContent) || $knowledgeContent === "=== NeoCafe Knowledge Base ===\n\n") {
                return 'I am a chatbot assistant for NeoCafe. How can I help you today?';
            }
            
            return $knowledgeContent;
            
        } catch (Exception $e) {
            error_log('Error retrieving knowledge base: ' . $e->getMessage());
            return 'I am a chatbot assistant for NeoCafe. How can I help you today?';
        }
    }
    
    private function getOrdersAnalysis($conn, $table) {
        $content = "--- ORDERS & BEST SELLERS ANALYSIS ---\n";
        
        // Get best selling products
        $query = "SELECT p.name, p.price, COUNT(oi.product_id) as order_count, SUM(oi.quantity) as total_quantity 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  GROUP BY oi.product_id 
                  ORDER BY total_quantity DESC 
                  LIMIT 10";
        
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $content .= "\nBEST SELLING PRODUCTS:\n";
            $rank = 1;
            while ($row = $result->fetch_assoc()) {
                $content .= sprintf("#%d: %s - Sold %d times (Total: %d units) - Price: ₱%.2f\n", 
                    $rank++, $row['name'], $row['order_count'], $row['total_quantity'], $row['price']);
            }
        }
        
        // Get recent orders summary
        $query = "SELECT COUNT(*) as total_orders, 
                  COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                  COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
                  FROM `$table` WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $content .= sprintf("\nORDERS LAST 30 DAYS: Total: %d, Completed: %d, Pending: %d\n", 
                $row['total_orders'], $row['completed'], $row['pending']);
        }
        
        return $content . "\n";
    }
    
    private function getProductsData($conn, $table) {
        $content = "--- PRODUCTS ---\n";
        $query = "SELECT p.*, c.name as category_name FROM `$table` p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.is_available = 1 
                  ORDER BY p.name LIMIT 100";
        
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $content .= sprintf("- %s (Category: %s) - ₱%.2f - %s\n", 
                    $row['name'], 
                    $row['category_name'] ?? 'Uncategorized', 
                    $row['price'], 
                    $row['description'] ?? 'No description');
            }
        }
        return $content . "\n";
    }
    
    private function getCategoriesData($conn, $table) {
        $content = "--- CATEGORIES ---\n";
        $query = "SELECT * FROM `$table` ORDER BY name";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $content .= sprintf("- %s: %s\n", $row['name'], $row['description'] ?? 'No description');
            }
        }
        return $content . "\n";
    }
    
    private function getUsersData($conn, $table) {
        // Only fetch non-sensitive data
        $content = "--- CUSTOMER BASE ---\n";
        $query = "SELECT COUNT(*) as total_customers FROM `$table` WHERE is_admin = 0";
        $result = $conn->query($query);
        
        if ($result && $row = $result->fetch_assoc()) {
            $content .= sprintf("Total registered customers: %d\n", $row['total_customers']);
        }
        return $content . "\n";
    }
    
    private function getPromotionsData($conn, $table) {
        $content = "--- ACTIVE PROMOTIONS ---\n";
        $query = "SELECT * FROM `$table` WHERE status = 'active' OR end_date >= CURDATE() ORDER BY start_date DESC";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $content .= sprintf("- %s: %s (Valid until: %s)\n", 
                    $row['title'] ?? $row['name'], 
                    $row['description'] ?? '', 
                    $row['end_date'] ?? 'No expiry');
            }
        } else {
            $content .= "No active promotions at the moment.\n";
        }
        return $content . "\n";
    }
    
    private function getBusinessHoursData($conn, $table) {
        $content = "--- BUSINESS HOURS ---\n";
        $query = "SELECT * FROM `$table` ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row['is_closed']) {
                    $content .= sprintf("%s: CLOSED\n", $row['day']);
                } else {
                    $content .= sprintf("%s: %s - %s\n", $row['day'], $row['open_time'], $row['close_time']);
                }
            }
        }
        return $content . "\n";
    }
    
    private function getGenericTableData($conn, $table) {
        $content = "--- " . strtoupper($table) . " ---\n";
        $result = $conn->query("SELECT * FROM `$table` LIMIT 50");
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $content .= json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
        return $content . "\n";
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $chatbot = new CafeChatbot();
        
        // Get message from either POST data or JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? $_POST['message'] ?? '';
        $conversationHistory = $input['history'] ?? [];
        
        // Debug logging
        error_log('=== CHATBOT REQUEST ===');
        error_log('Message: ' . $message);
        error_log('History count: ' . count($conversationHistory));
        error_log('History: ' . json_encode($conversationHistory));
        
        if (empty($message)) {
            throw new Exception('No message provided');
        }
        
        // Limit history to last 5 exchanges (10 messages) to avoid token limits
        $conversationHistory = array_slice($conversationHistory, -10);
        
        $response = $chatbot->getResponse($message, $conversationHistory);
        echo json_encode(['response' => $response]);
        exit;
    } catch (Exception $e) {
        error_log('Chatbot error: ' . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}
