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
require_once __DIR__ . "/../admin-includes/activity-logger.php";

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
    <link rel="stylesheet" href="chatbot-knowledge.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

    <div class="main-container">
        <div class="kb-wrapper fade-in">
            <!-- Header -->
            <div class="kb-header">
                <p>Configure the knowledge base that powers your AI chatbot assistant. Add information about products, services, policies, and more.</p>
            </div>

            <!-- Content Grid -->
            <div class="kb-content">
                <!-- Editor Section -->
                <div class="kb-editor-card">
                    <h2><i class="fas fa-edit"></i> Knowledge Editor</h2>
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
                            <button type="button" class="kb-btn kb-btn-secondary" onclick="resetContent()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="kb-btn kb-btn-primary" id="save-btn">
                                <i class="fas fa-save"></i> Save
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

