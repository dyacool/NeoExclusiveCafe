<?php
// Prevent PHP errors from being output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'samesite' => 'Strict',
    'domain' => 'neocafe.cafe'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../backend/pages/admin-includes/config.php";
require_once __DIR__ . "/database.php";

// Define preview mode - check for both user and admin sessions
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
$is_user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
$is_admin_logged_in = isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - " : ""; ?>NeoExclusive</title>
    <!-- Add favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <link rel="stylesheet" href="/frontend/user-includes/navbar/customer-navigation.css">
    <link rel="stylesheet" href="/frontend/user-includes/footer.css">
    <style>
        /* Chat button styles */
        .chat-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .chat-button:hover {
            transform: scale(1.1);
            background-color: #0056b3;
        }

        .chat-button img {
            width: 30px;
            height: 30px;
        }

        .chat-window {
            display: none;
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .chat-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .close-chat {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .close-chat:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .chat-messages {
            height: 336px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 80%;
            position: relative;
        }

        .bot-message {
            background: white;
            margin-right: auto;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .user-message {
            background: #007bff;
            color: white;
            margin-left: auto;
        }

        .chat-input-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
        }

        .chat-input:focus {
            border-color: #007bff;
        }

        .send-button {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .send-button:hover {
            background: #0056b3;
        }

        .message-time {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }

        .user-message .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Styling for clickable links in bot messages */
        .bot-message a {
            color: #0078ff !important;
            text-decoration: underline !important;
            font-weight: bold;
            cursor: pointer;
            word-break: break-all;
            transition: opacity 0.2s;
        }

        .bot-message a:hover {
            opacity: 0.8;
            text-decoration: none !important;
        }

        /* Typing indicator for chat */
        .typing-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #888;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            opacity: 0.4;
            animation: pulse 1s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes pulse {
            0%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            50% {
                transform: translateY(-5px);
                opacity: 1;
            }
        }

        @media (max-width: 500px) {
            .chat-window {
                width: calc(100% - 40px);
                height: calc(100% - 120px);
                bottom: 80px;
            }
        }
    </style>
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($head_extra)): ?>
        <?php echo $head_extra; ?>
    <?php endif; ?>
</head>
<body>
    <?php include_once __DIR__ . "/customer-navigation.php"; ?>
    <!-- Page content will be inserted here -->
    
    <!-- Chat Button -->
    <button class="chat-button" onclick="toggleChat()" title="Chat with us">
        <img src="/assets/images/chatbot.svg" alt="Chat Icon">
    </button>

    <!-- Chat Window -->
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h3>NeoExclusive Support</h3>
            <button class="close-chat" onclick="toggleChat()">×</button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be added here -->
        </div>
        <div class="chat-input-container">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type your message..." onkeypress="handleKeyPress(event)">
            <button class="send-button" onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to convert URLs to clickable links
        function linkifyText(text) {
            // Comprehensive regex for URLs
            const urlRegex = /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/gi;
            
            // Replace URLs with clickable links
            return text.replace(urlRegex, function(url) {
                const href = url.startsWith('http') ? url : 'https://' + url;
                return `<a href="${href}" target="_blank" rel="noopener noreferrer" style="color: #0078ff; text-decoration: underline; font-weight: bold; cursor: pointer; word-break: break-all;">${url}</a>`;
            });
        }

        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');
            chatWindow.style.display = chatWindow.style.display === 'none' ? 'block' : 'none';
            
            if (chatWindow.style.display === 'block') {
                // Add welcome message when chat is opened
                const chatMessages = document.getElementById('chatMessages');
                if (chatMessages.children.length === 0) {
                    addBotMessage('Hello! Welcome to NeoExclusive Cafe. How can I help you today?');
                }
            }
        }

        function addBotMessage(message) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot-message';
            
            // Convert URLs to clickable links
            const linkifiedMessage = linkifyText(message);
            
            messageDiv.innerHTML = `
                ${linkifiedMessage}
                <div class="message-time">${getCurrentTime()}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function addUserMessage(message) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message user-message';
            messageDiv.innerHTML = `
                ${message}
                <div class="message-time">${getCurrentTime()}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function getCurrentTime() {
            const now = new Date();
            return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (message) {
                addUserMessage(message);
                input.value = '';
                
                // Show typing indicator
                const chatMessages = document.getElementById('chatMessages');
                const typingIndicator = document.createElement('div');
                typingIndicator.className = 'message bot-message typing-indicator';
                typingIndicator.id = 'typing-indicator';
                typingIndicator.innerHTML = '<span></span><span></span><span></span>';
                chatMessages.appendChild(typingIndicator);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Send message to chatbot
                const formData = new FormData();
                formData.append('message', message);
                
                fetch('../../backend/pages/admin-includes/chatbot.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Server did not return JSON');
                    }
                    return response.text().then(text => {
                        try {
                            // Remove any HTML comments or whitespace before parsing
                            const cleanText = text.replace(/<!--[\s\S]*?-->/g, '').trim();
                            if (!cleanText) {
                                throw new Error('Empty response from server');
                            }
                            return JSON.parse(cleanText);
                        } catch (e) {
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server: ' + e.message);
                        }
                    });
                })
                .then(data => {
                    // Remove typing indicator
                    const indicator = document.getElementById('typing-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                    
                    if (data.error) {
                        addBotMessage('Error: ' + data.error);
                    } else if (data.response) {
                        addBotMessage(data.response);
                    } else {
                        throw new Error('Invalid response format from server');
                    }
                })
                .catch(error => {
                    // Remove typing indicator
                    const indicator = document.getElementById('typing-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                    
                    console.error('Error:', error);
                    addBotMessage('Sorry, I encountered an error. Please try again.');
                });
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
    </script>
</body>
</html>
