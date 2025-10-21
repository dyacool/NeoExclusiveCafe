<?php
// Prevent PHP errors from being output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Only set session parameters if session hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters based on environment
    $session_domain = '';
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        // Only set domain for production environment
        if (strpos($host, 'neocafe.cafe') !== false) {
            $session_domain = 'neocafe.cafe';
        }
    }
    
    session_set_cookie_params([
        'lifetime' => 0,
        'httponly' => true,
        'samesite' => 'Strict',
        'domain' => $session_domain
    ]);
    
    session_start();
}

require_once __DIR__ . "/../../backend/pages/admin-includes/config.php";
require_once __DIR__ . "/../../backend/pages/admin-includes/database.php";

// Define preview mode - check for both user and admin sessions
$is_preview_mode = !isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
$is_user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
$is_admin_logged_in = isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($page_title) ? $page_title . " - " : ""; ?>NeoExclusive</title>
    <!-- Add favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <!-- Base Styles -->
    <link rel="stylesheet" href="/frontend/assets/css/base.css">
    <!-- Component Styles -->
    <link rel="stylesheet" href="/frontend/user-includes/navbar/customer-navigation.css">
    <link rel="stylesheet" href="/frontend/user-includes/footer.css">
    <style>
        
        /* Chat button styles - Matching notification dropdown theme */
        .chat-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4d7e46ff, #0f5132);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(45, 90, 39, 0.3), 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 999;
            border: 2px solid rgba(203, 213, 192, 0.3);
        }

        .chat-button:hover {
            transform: scale(1.1) translateY(-2px);
            box-shadow: 0 15px 40px rgba(45, 90, 39, 0.4), 0 8px 20px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #1a4018, #0f2e0f);
        }

        .chat-button img,
        .chat-button svg {
            width: 30px;
            height: 30px;
            filter: brightness(0) invert(1);
        }

        .chat-window {
            display: none;
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 360px;
            height: 520px;
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 81, 50, 0.15), 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #cbd5c0;
            z-index: 999;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            opacity: 0;
            transform: translateY(15px) scale(0.95);
        }

        .chat-window.active {
            display: block;
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .chat-header {
            background: #0f5132;
            color: white;
            padding: 15px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .chat-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="1.5" fill="rgba(255,255,255,0.08)"/><circle cx="60" cy="70" r="1" fill="rgba(255,255,255,0.06)"/><circle cx="30" cy="80" r="2.5" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.6;
        }

        .chat-header h3 {
            color: white;
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chat-header h3::before {
            content: "💬";
            font-size: 16px;
            opacity: 0.9;
        }

        .close-chat {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            cursor: pointer;
            font-size: 22px;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            font-weight: 300;
        }

        .close-chat:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .chat-messages {
            height: 358px;
            overflow-y: auto;
            padding: 15px;
            background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #cbd5c0;
            border-radius: 3px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #b6ad90;
        }

        .message {
            margin-bottom: 12px;
            padding: 12px 16px;
            border-radius: 15px;
            max-width: 80%;
            position: relative;
            animation: messageSlideIn 0.3s ease;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bot-message {
            background: white;
            margin-right: auto;
            box-shadow: 0 2px 8px rgba(203, 213, 192, 0.2);
            border: 1px solid rgba(203, 213, 192, 0.3);
            color: #333;
        }

        .user-message {
            background: linear-gradient(135deg, #2d5a27 0%, #1a4018 100%);
            color: white;
            margin-left: auto;
            box-shadow: 0 2px 8px rgba(45, 90, 39, 0.3);
        }

        .chat-input-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: white;
            border-top: 1px solid rgba(203, 213, 192, 0.3);
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #cbd5c0;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .chat-input:focus {
            border-color: #2d5a27;
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
        }

        .send-button {
            background: linear-gradient(135deg, #2d5a27 0%, #1a4018 100%);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 12px 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(45, 90, 39, 0.2);
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.3);
            background: linear-gradient(135deg, #1a4018 0%, #0f2e0f 100%);
        }

        .send-button:active {
            transform: translateY(0);
        }

        .message-time {
            font-size: 10px;
            color: #999;
            margin-top: 6px;
            opacity: 0.7;
        }

        .user-message .message-time {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Styling for clickable links in bot messages */
        .bot-message a {
            color: #2d5a27 !important;
            text-decoration: underline !important;
            font-weight: 600;
            cursor: pointer;
            word-break: break-all;
            transition: all 0.2s ease;
        }

        .bot-message a:hover {
            color: #1a4018 !important;
            opacity: 0.8;
        }

        /* Typing indicator for chat */
        .typing-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px 16px;
            background: white;
            border-radius: 15px;
            max-width: 60px;
            box-shadow: 0 2px 8px rgba(203, 213, 192, 0.2);
            border: 1px solid rgba(203, 213, 192, 0.3);
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #2d5a27;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            opacity: 0.4;
            animation: typingPulse 1.4s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(1) {
            animation-delay: 0s;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typingPulse {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            30% {
                transform: translateY(-8px);
                opacity: 1;
            }
        }

        /* Welcome badge in chat */
        .chat-welcome {
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, rgba(203, 213, 192, 0.2), rgba(223, 230, 218, 0.2));
            border-radius: 8px;
            font-size: 12px;
            color: #2d5a27;
            font-weight: 500;
        }

        @media (max-width: 500px) {
            .chat-window {
                width: calc(100% - 20px);
                height: calc(100% - 100px);
                bottom: 90px;
                right: 10px;
                left: 10px;
                margin: 0 auto;
            }

            .chat-button {
                bottom: 15px;
                right: 15px;
                width: 55px;
                height: 55px;
            }

            .message {
                max-width: 85%;
            }
        }

        @media (max-width: 380px) {
            .chat-window {
                width: calc(100% - 10px);
                right: 5px;
                left: 5px;
            }

            .chat-input-container {
                padding: 10px;
            }

            .send-button {
                padding: 10px 18px;
                font-size: 13px;
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
    <?php include_once __DIR__ . "/navbar/customer-navigation.php"; ?>
    <!-- Page content will be inserted here -->
<<<<<<< Updated upstream
=======
    
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
                
                fetch('/backend/pages/admin-includes/chatbot.php', {
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
    <!-- Responsive Fixes -->
    <script src="/frontend/assets/js/responsive-fixes.js"></script>
</body>
</html>
>>>>>>> Stashed changes
