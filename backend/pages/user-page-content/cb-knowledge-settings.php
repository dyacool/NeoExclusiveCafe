<?php
// Start session for potential authentication
session_start();

// Check if user is logged in as admin (implement your authentication logic)
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /backend/login/admin/admin-login.php");
    exit();
}

// Include the navbar and database
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";
require_once __DIR__ . "/../admin-includes/database.php";

// Initialize variables
$success_message = '';
$error_message = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Knowledge Base - NeoCafe Admin</title>
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
    
    <!-- CSS files -->
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="../admin-includes/navbar/admin-navigation.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Spectral", serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .main-container {
            margin-left: 80px;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            padding: 30px;
        }

        .sidebar:not(.collapsed) ~ .main-container {
            margin-left: 250px;
        }

        .kb-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header Section */
        .kb-header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .kb-header h1 {
            font-size: 32px;
            color: #2f603c;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .kb-header h1 i {
            font-size: 28px;
        }

        .kb-header p {
            color: #666;
            font-size: 16px;
            margin: 0;
        }

        /* Main Content Area */
        .kb-content {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 30px;
        }

        /* Editor Card */
        .kb-editor-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .kb-editor-card h2 {
            font-size: 20px;
            color: #333;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kb-editor-card h2 i {
            color: #2f603c;
        }

        .kb-form-group {
            margin-bottom: 20px;
        }

        .kb-textarea {
            width: 100%;
            min-height: 450px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            font-family: "Spectral", serif;
            font-size: 15px;
            line-height: 1.6;
            resize: vertical;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .kb-textarea:focus {
            outline: none;
            border-color: #2f603c;
            background: white;
            box-shadow: 0 0 0 3px rgba(47, 96, 60, 0.1);
        }

        .kb-textarea::placeholder {
            color: #999;
        }

        .kb-helper-text {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kb-helper-text i {
            color: #f59e0b;
            font-size: 12px;
        }

        /* Buttons */
        .kb-btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .kb-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kb-btn-primary {
            background: linear-gradient(135deg, #2f603c 0%, #1f4028 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(47, 96, 60, 0.3);
        }

        .kb-btn-primary:hover {
            background: linear-gradient(135deg, #1f4028 0%, #0f2014 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(47, 96, 60, 0.4);
        }

        .kb-btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .kb-btn-secondary {
            background: #f8fafc;
            color: #4b5563;
            border: 2px solid #e5e7eb;
        }

        .kb-btn-secondary:hover {
            background: #f1f5f9;
            border-color: #d1d5db;
        }

        /* Preview Card */
        .kb-preview-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 30px;
        }

        .kb-preview-card h2 {
            font-size: 20px;
            color: #333;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kb-preview-card h2 i {
            color: #2f603c;
        }

        .kb-preview-content {
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #fafbfc;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .kb-preview-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            color: #9ca3af;
            text-align: center;
        }

        .kb-preview-placeholder i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
        }

        .kb-preview-content a {
            color: #0078ff;
            text-decoration: underline;
            font-weight: 600;
            word-break: break-all;
        }

        .kb-preview-content a:hover {
            color: #0056b3;
            text-decoration: none;
        }

        /* Stats */
        .kb-stats {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .kb-stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .kb-stat-item:last-child {
            margin-bottom: 0;
        }

        .kb-stat-label {
            color: #666;
        }

        .kb-stat-value {
            color: #2f603c;
            font-weight: 600;
        }

        /* Success/Error Messages */
        .kb-message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .kb-message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            display: flex;
        }

        .kb-message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            display: flex;
        }

        /* Scrollbar Styling */
        .kb-preview-content::-webkit-scrollbar,
        .kb-textarea::-webkit-scrollbar {
            width: 8px;
        }

        .kb-preview-content::-webkit-scrollbar-track,
        .kb-textarea::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .kb-preview-content::-webkit-scrollbar-thumb,
        .kb-textarea::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .kb-preview-content::-webkit-scrollbar-thumb:hover,
        .kb-textarea::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Responsive Design */
        @media (max-width: 1100px) {
            .kb-content {
                grid-template-columns: 1fr;
            }

            .kb-preview-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 20px;
            }

            .sidebar:not(.collapsed) ~ .main-container {
                margin-left: 0;
            }

            .kb-header {
                padding: 20px;
            }

            .kb-header h1 {
                font-size: 24px;
            }

            .kb-editor-card,
            .kb-preview-card {
                padding: 20px;
            }

            .kb-btn-group {
                flex-direction: column;
            }

            .kb-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="kb-wrapper fade-in">
            <!-- Header -->
            <div class="kb-header">
                <h1><i class="fas fa-robot"></i> Chatbot Knowledge Base Settings</h1>
                <p>Configure the knowledge base that powers your AI chatbot assistant. Add information about products, services, policies, and more.</p>
            </div>

            <!-- Content Grid -->
            <div class="kb-content">
                <!-- Editor Section -->
                <div class="kb-editor-card">
                    <h2><i class="fas fa-edit"></i> Knowledge Editor</h2>
                    
                    <!-- Message Area -->
                    <div id="kb-message" class="kb-message"></div>
                    
                    <!-- Form -->
                    <form id="knowledge-form">
                        <div class="kb-form-group">
                            <textarea 
                                id="knowledge-content" 
                                name="content" 
                                class="kb-textarea" 
                                placeholder="Enter comprehensive information about your cafe here...

Examples:
• Menu items and their descriptions
• Opening hours and location
• Contact information
• Special offers and promotions
• Delivery and pickup policies
• Payment methods
• FAQs and common inquiries

You can include URLs - they will automatically become clickable links in the chatbot."
                                required></textarea>
                            <div class="kb-helper-text">
                                <i class="fas fa-info-circle"></i>
                                <span>Tip: Be detailed and specific for better chatbot responses. URLs will be automatically converted to clickable links.</span>
                            </div>
                        </div>

                        <div class="kb-btn-group">
                            <button type="submit" class="kb-btn kb-btn-primary" id="save-btn">
                                <i class="fas fa-save"></i> Save Knowledge Base
                            </button>
                            <button type="button" class="kb-btn kb-btn-secondary" onclick="resetContent()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preview Section -->
                <div class="kb-preview-card">
                    <h2><i class="fas fa-eye"></i> Live Preview</h2>
                    <div id="knowledge-preview" class="kb-preview-content">
                        <div class="kb-preview-placeholder">
                            <i class="fas fa-file-alt"></i>
                            <p>Start typing to see a preview...</p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="kb-stats">
                        <div class="kb-stat-item">
                            <span class="kb-stat-label">Word Count:</span>
                            <span class="kb-stat-value" id="word-count">0</span>
                        </div>
                        <div class="kb-stat-item">
                            <span class="kb-stat-label">Character Count:</span>
                            <span class="kb-stat-value" id="char-count">0</span>
                        </div>
                        <div class="kb-stat-item">
                            <span class="kb-stat-label">Last Updated:</span>
                            <span class="kb-stat-value" id="last-updated">Never</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to convert plain text URLs to clickable links
        function linkifyText(text) {
            const urlRegex = /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/gi;
            
            return text.replace(urlRegex, function(url) {
                const href = url.startsWith('http') ? url : 'https://' + url;
                return `<a href="${href}" target="_blank" rel="noopener noreferrer">${url}</a>`;
            });
        }

        // Function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Function to update preview and stats
        function updatePreview() {
            const content = $('#knowledge-content').val();
            const preview = $('#knowledge-preview');
            
            if (content.trim() === '') {
                preview.html(`
                    <div class="kb-preview-placeholder">
                        <i class="fas fa-file-alt"></i>
                        <p>Start typing to see a preview...</p>
                    </div>
                `);
                $('#word-count').text('0');
                $('#char-count').text('0');
            } else {
                const escapedContent = escapeHtml(content);
                const linkedContent = linkifyText(escapedContent);
                preview.html(linkedContent);
                
                // Update stats
                const words = content.trim().split(/\s+/).length;
                const chars = content.length;
                $('#word-count').text(words);
                $('#char-count').text(chars);
            }
        }

        // Function to show message
        function showMessage(message, type = 'success') {
            const msgDiv = $('#kb-message');
            msgDiv.removeClass('success error').addClass(type);
            msgDiv.html(`<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`);
            msgDiv.show();
            
            setTimeout(() => {
                msgDiv.fadeOut();
            }, 5000);
        }

        // Function to reset content
        function resetContent() {
            if (confirm('Are you sure you want to reset the editor? This will reload the last saved content.')) {
                loadKnowledge();
            }
        }

        // Function to format date
        function formatDate(dateString) {
            if (!dateString) return 'Never';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Function to load knowledge base
        function loadKnowledge() {
            fetch('get-knowledge.php')
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        $('#knowledge-content').val(res.content || '');
                        updatePreview();
                        
                        // Update last updated time if available
                        if (res.updated_at) {
                            $('#last-updated').text(formatDate(res.updated_at));
                        }
                    } else {
                        showMessage('Error loading knowledge base: ' + (res.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Failed to load knowledge base:', error);
                    showMessage('Failed to load knowledge base. Please refresh the page.', 'error');
                });
        }

        // Document ready
        $(document).ready(function() {
            // Load current knowledge base
            loadKnowledge();

            // Update preview on input
            $('#knowledge-content').on('input', updatePreview);

            // Form submission
            $('#knowledge-form').on('submit', function(e) {
                e.preventDefault();
                
                const content = $('#knowledge-content').val().trim();
                
                if (content === '') {
                    showMessage('Content cannot be empty!', 'error');
                    return;
                }
                
                // Disable button and show loading state
                const submitBtn = $('#save-btn');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                // Send data
                fetch('save-knowledge.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'content=' + encodeURIComponent(content)
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        showMessage('Knowledge base updated successfully!', 'success');
                        $('#last-updated').text(formatDate(new Date()));
                    } else {
                        showMessage('Failed to update: ' + (res.error || 'Unknown error occurred'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Failed to save knowledge base:', error);
                    showMessage('Failed to save knowledge base. Please try again.', 'error');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.prop('disabled', false).html(originalText);
                });
            });
        });
    </script>
</body>
</html>

