<?php
// Prevent PHP errors from being output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Only set session parameters if session hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../backend/pages/admin-includes/config.php";
require_once __DIR__ . "/../../backend/pages/admin-includes/database.php";
require_once __DIR__ . "/../../includes/session-manager.php";

// Define preview mode and authentication states using SessionManager
$is_preview_mode = SessionManager::isPreviewMode();
$is_user_logged_in = SessionManager::isUserLoggedIn();
$is_admin_logged_in = SessionManager::isAdminLoggedIn();
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
    <link rel="stylesheet" href="/frontend/assets/css/chat-widget.css">
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
    
    <!-- Chat Button -->
    <button class="chat-button" onclick="toggleChat()" title="Chat with us" aria-label="Open chat support">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <line x1="9" y1="10" x2="15" y2="10"></line>
            <line x1="9" y1="14" x2="13" y2="14"></line>
        </svg>
    </button>

    <!-- Chat Window -->
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <h3>Neo Cafe Support</h3>
            <button class="close-chat" onclick="toggleChat()">×</button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be added here -->
        </div>
        <div class="chat-input-wrapper">
            <div class="chat-faq-container" id="chatFaqContainer">
                <div class="faq-header" onclick="toggleFaqSection()">
                    <div class="faq-label">Frequently Asked Questions:</div>
                    <button class="faq-toggle-btn" id="faqToggleBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="18 15 12 9 6 15"></polyline>
                        </svg>
                    </button>
                </div>
                <div class="faq-buttons" id="faqButtons">
                    <button class="faq-button" onclick="fillChatInput('What are your best products?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        Best Products
                    </button>
                    <button class="faq-button" onclick="fillChatInput('What are your business hours?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Operating Hours
                    </button>
                    <button class="faq-button" onclick="fillChatInput('How do I place an order?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Place Order
                    </button>
                    <button class="faq-button" onclick="fillChatInput('What payment methods do you accept?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Payment Methods
                    </button>
                </div>
            </div>
            <div class="chat-input-container">
                <textarea class="chat-input" id="chatInput" placeholder="Type your message..." rows="1" onkeydown="handleKeyPress(event)" oninput="handleInputChange(this)"></textarea>
                <button class="send-button" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to format chatbot response text
        function formatChatbotText(text) {
            // Convert **text** to <strong>text</strong> for bold
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            
            // Format numbered lists (e.g., "1. ", "2. ") - do this before line breaks
            text = text.replace(/(\d+)\.\s/g, '|||NUM|||<strong>$1.</strong> ');
            
            // Format bullet points with dashes (- ) - do this before line breaks
            text = text.replace(/\s-\s/g, '|||BULLET|||&nbsp;&nbsp;• ');
            
            // Convert line breaks: 
            // 1. Double line breaks to paragraph breaks
            text = text.replace(/\n\n/g, '<br><br>');
            // 2. Single line breaks to <br>
            text = text.replace(/\n/g, '<br>');
            
            // Now replace the placeholders with <br> for lists
            text = text.replace(/\|\|\|NUM\|\|\|/g, '<br>');
            text = text.replace(/\|\|\|BULLET\|\|\|/g, '<br>');
            
            // Clean up multiple consecutive <br> tags (more than 2)
            text = text.replace(/(<br>\s*){3,}/g, '<br><br>');
            
            // Convert URLs to clickable links (do this last to avoid breaking the regex)
            const urlRegex = /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/gi;
            text = text.replace(urlRegex, function(url) {
                const href = url.startsWith('http') ? url : 'https://' + url;
                return `<a href="${href}" target="_blank" rel="noopener noreferrer" style="color: #0078ff; text-decoration: underline; font-weight: bold; cursor: pointer; word-break: break-all;">${url}</a>`;
            });
            
            return text;
        }
        
        // Keeping the old function name for backward compatibility
        function linkifyText(text) {
            return formatChatbotText(text);
        }

        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');
            const isActive = chatWindow.classList.contains('active');
            
            if (isActive) {
                chatWindow.classList.remove('active');
                setTimeout(() => {
                    chatWindow.style.display = 'none';
                }, 400); // Match transition duration
            } else {
                chatWindow.style.display = 'block';
                setTimeout(() => {
                    chatWindow.classList.add('active');
                }, 10);
                
                // Add welcome message when chat is opened for the first time
                const chatMessages = document.getElementById('chatMessages');
                if (chatMessages.children.length === 0) {
                    // Add welcome badge
                    const welcomeDiv = document.createElement('div');
                    welcomeDiv.className = 'chat-welcome';
                    welcomeDiv.textContent = 'Welcome to Neo Cafe Support';
                    chatMessages.appendChild(welcomeDiv);
                    
                    // Add bot welcome message
                    setTimeout(() => {
                        addBotMessage('Hello! Welcome to Neo Cafe. How can I help you today?');
                    }, 300);
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

        // Store conversation history
        let conversationHistory = [];

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (message) {
                addUserMessage(message);
                input.value = '';
                input.style.height = '44px';
                
                // Expand FAQ section after sending message
                const faqContainer = document.getElementById('chatFaqContainer');
                const chatWindow = document.getElementById('chatWindow');
                faqContainer.classList.remove('minimized');
                chatWindow.classList.remove('faq-minimized');
                
                // Scroll to bottom after expanding FAQ
                scrollChatToBottom();
                
                // Add to conversation history
                conversationHistory.push({
                    role: 'user',
                    message: message
                });
                
                console.log('Conversation history after user message:', conversationHistory);
                
                // Show typing indicator
                const chatMessages = document.getElementById('chatMessages');
                const typingIndicator = document.createElement('div');
                typingIndicator.className = 'message bot-message typing-indicator';
                typingIndicator.id = 'typing-indicator';
                typingIndicator.innerHTML = '<span></span><span></span><span></span>';
                chatMessages.appendChild(typingIndicator);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Send message with conversation history
                fetch('/backend/pages/user-page-content/chatbot/chatbot-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        history: conversationHistory.slice(-10) // Send last 10 messages
                    })
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
                        const errorMsg = 'Error: ' + data.error;
                        addBotMessage(errorMsg);
                        conversationHistory.push({
                            role: 'bot',
                            message: errorMsg
                        });
                    } else if (data.response) {
                        addBotMessage(data.response);
                        // Add bot response to conversation history
                        conversationHistory.push({
                            role: 'bot',
                            message: data.response
                        });
                        console.log('Conversation history after bot message:', conversationHistory);
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
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function autoResizeTextarea(textarea) {
            // Reset height to get accurate scrollHeight
            textarea.style.height = '44px';
            
            // Calculate new height (min 44px, max 90px)
            const newHeight = Math.min(Math.max(textarea.scrollHeight, 44), 90);
            textarea.style.height = newHeight + 'px';
        }

        function scrollChatToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                setTimeout(() => {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 100);
            }
        }

        function handleInputChange(textarea) {
            autoResizeTextarea(textarea);
            
            // Check if input has text
            const hasText = textarea.value.trim().length > 0;
            const faqContainer = document.getElementById('chatFaqContainer');
            const chatWindow = document.getElementById('chatWindow');
            
            if (hasText) {
                // Minimize FAQ section when user types
                faqContainer.classList.add('minimized');
                chatWindow.classList.add('faq-minimized');
            } else {
                // Expand FAQ section when input is empty
                faqContainer.classList.remove('minimized');
                chatWindow.classList.remove('faq-minimized');
            }
            
            // Scroll to bottom after FAQ state change
            scrollChatToBottom();
        }

        function toggleFaqSection() {
            const faqContainer = document.getElementById('chatFaqContainer');
            const chatWindow = document.getElementById('chatWindow');
            const input = document.getElementById('chatInput');
            
            // Only allow manual toggle if input is not empty
            if (input.value.trim().length > 0) {
                faqContainer.classList.toggle('minimized');
                chatWindow.classList.toggle('faq-minimized');
                
                // Scroll to bottom after toggle
                scrollChatToBottom();
            }
        }

        function fillChatInput(text) {
            const input = document.getElementById('chatInput');
            input.value = text;
            handleInputChange(input);
            input.focus();
            
            // Optional: Auto-send after filling (remove if you want users to click Send manually)
            // sendMessage();
        }
    </script>
    <!-- Responsive Fixes -->
    <script src="/frontend/assets/js/responsive-fixes.js"></script>
</body>
</html>
