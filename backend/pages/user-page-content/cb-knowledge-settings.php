<?php
// Handle AJAX requests FIRST before any output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    header('Content-Type: application/json');
    
    // Include only database connection for AJAX
    require_once __DIR__ . '/../admin-includes/database.php';
    
    try {
        $stmt = $pdo->prepare("SELECT content, updated_at FROM chatbot_knowledge ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'content' => $result['content'],
                'updated_at' => $result['updated_at']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'content' => '',
                'updated_at' => null
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX POST request for saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    header('Content-Type: application/json');
    
    // Include only database connection for AJAX
    require_once __DIR__ . '/../admin-includes/database.php';
    
    try {
        $content = $_POST['content'];
        
        // Check if knowledge base entry exists
        $stmt = $pdo->prepare("SELECT id FROM chatbot_knowledge LIMIT 1");
        $stmt->execute();
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE chatbot_knowledge SET content = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$content, $exists['id']]);
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO chatbot_knowledge (content, updated_at) VALUES (?, NOW())");
            $stmt->execute([$content]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Normal page load - Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

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
    <link rel="stylesheet" href="chatbot/chatbot-knowledge.css">
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

            <!-- Database Source Preview -->
            <div class="kb-database-preview">
                <div class="db-preview-header">
                    <h3><i class="fas fa-database"></i> Active Database Source</h3>
                    <button type="button" class="kb-btn kb-btn-settings" id="change-settings-btn">
                        <i class="fas fa-cog"></i> Change Settings
                    </button>
                </div>
                <div class="db-preview-content" id="db-preview-content">
                    <div class="db-loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading database info...
                    </div>
                </div>
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

        // Function to update preview
        function updatePreview() {
            const content = $('#knowledge-content').val();
            const preview = $('#knowledge-preview');
            
            if (!content || content.trim() === '') {
                preview.html(`
                    <div class="kb-preview-placeholder">
                        <i class="fas fa-file-alt"></i>
                        <p>Start typing to see a preview...</p>
                    </div>
                `);
                return;
            }
            
            // Convert line breaks to <br> and linkify URLs
            const escapedContent = escapeHtml(content);
            const formattedContent = linkifyText(escapedContent.replace(/\n/g, '<br>'));
            
            preview.html(`<div class="kb-preview-text">${formattedContent}</div>`);
        }

        // Function to show message
        function showMessage(message, type = 'success') {
            // Create message element if it doesn't exist
            let msgDiv = $('#kb-message');
            if (msgDiv.length === 0) {
                msgDiv = $('<div id="kb-message" class="kb-message"></div>');
                $('.kb-wrapper').prepend(msgDiv);
            }
            
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
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            };
            return date.toLocaleDateString('en-US', options);
        }

        // Function to load knowledge base
        function loadKnowledge() {
            fetch('?action=get')
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        $('#knowledge-content').val(res.content || '');
                        updatePreview();
                        
                        // Update last updated time if available
                        if (res.updated_at && $('#last-updated').length) {
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
                
                // Send data using FormData
                const formData = new FormData();
                formData.append('content', content);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        showMessage('Knowledge base updated successfully!', 'success');
                        if ($('#last-updated').length) {
                            $('#last-updated').text(formatDate(new Date()));
                        }
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

            // Load database preview
            loadDatabasePreview();

            // Change Settings button handler
            $('#change-settings-btn').on('click', function() {
                showOTPModal();
            });
        });

        function loadDatabasePreview() {
            fetch('chatbot/api/get-database-preview.php', {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        const preview = $('#db-preview-content');
                        const tableCount = res.data.table_count || 0;
                        const tables = res.data.selected_tables || [];
                        
                        let tablesHtml = '';
                        if (tableCount > 0) {
                            tablesHtml = `<strong>${tableCount} table(s)</strong>: ${tables.join(', ')}`;
                        } else {
                            tablesHtml = '<strong>Manual knowledge only</strong>';
                        }
                        
                        preview.html(`
                            <div class="db-info-grid">
                                <div class="db-info-item">
                                    <i class="fas fa-server"></i>
                                    <div>
                                        <span class="db-label">Database Type:</span>
                                        <span class="db-value">${res.data.type || 'Default'}</span>
                                    </div>
                                </div>
                                <div class="db-info-item">
                                    <i class="fas fa-database"></i>
                                    <div>
                                        <span class="db-label">Source:</span>
                                        <span class="db-value">${res.data.source || 'Primary Database'}</span>
                                    </div>
                                </div>
                                <div class="db-info-item">
                                    <i class="fas fa-table"></i>
                                    <div>
                                        <span class="db-label">Selected Tables:</span>
                                        <span class="db-value">${tablesHtml}</span>
                                    </div>
                                </div>
                                <div class="db-info-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <span class="db-label">Last Updated:</span>
                                        <span class="db-value">${formatDate(res.data.last_updated)}</span>
                                    </div>
                                </div>
                            </div>
                        `);
                    } else {
                        $('#db-preview-content').html(`
                            <div class="db-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Unable to load database information</p>
                            </div>
                        `);
                    }
                })
                .catch(error => {
                    console.error('Failed to load database preview:', error);
                    $('#db-preview-content').html(`
                        <div class="db-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error loading database information</p>
                        </div>
                    `);
                });
        }

        // Function to show OTP modal
        function showOTPModal() {
            // Create modal if it doesn't exist
            if ($('#otp-modal').length === 0) {
                $('body').append(`
                    <div id="otp-modal" class="otp-modal">
                        <div class="otp-modal-content">
                            <div class="otp-modal-header">
                                <h3><i class="fas fa-shield-alt"></i> Security Verification</h3>
                                <button class="otp-close" onclick="closeOTPModal()">&times;</button>
                            </div>
                            <div class="otp-modal-body">
                                <p class="otp-description">For security purposes, we need to verify your identity before allowing access to database settings.</p>
                                
                                <div id="otp-request-section">
                                    <div class="otp-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span>A One-Time Password will be sent to your registered admin email.</span>
                                    </div>
                                    <button class="kb-btn kb-btn-primary" id="request-otp-btn" onclick="requestOTP()">
                                        <i class="fas fa-paper-plane"></i> Send OTP
                                    </button>
                                </div>

                                <div id="otp-verify-section" style="display: none;">
                                    <div class="otp-success-message">
                                        <i class="fas fa-check-circle"></i>
                                        <span>OTP has been sent to your email. Please check your inbox.</span>
                                    </div>
                                    <div class="otp-input-group">
                                        <label for="otp-input">Enter OTP Code:</label>
                                        <input type="text" id="otp-input" class="otp-input" maxlength="6" placeholder="000000" />
                                        <small class="otp-timer">Code expires in <span id="otp-countdown">5:00</span></small>
                                    </div>
                                    <div class="otp-btn-group">
                                        <button class="kb-btn kb-btn-secondary" onclick="requestOTP()">
                                            <i class="fas fa-redo"></i> Resend OTP
                                        </button>
                                        <button class="kb-btn kb-btn-primary" id="verify-otp-btn" onclick="verifyOTP()">
                                            <i class="fas fa-check"></i> Verify
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }
            
            $('#otp-modal').fadeIn(300);
        }

        // Function to close OTP modal
        function closeOTPModal() {
            $('#otp-modal').fadeOut(300);
            // Reset modal state
            $('#otp-request-section').show();
            $('#otp-verify-section').hide();
            $('#otp-input').val('');
            if (window.otpCountdownInterval) {
                clearInterval(window.otpCountdownInterval);
            }
        }

        // Function to request OTP
        function requestOTP() {
            const btn = $('#request-otp-btn');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

            fetch('chatbot/api/send-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const res = JSON.parse(text);
                    if (res.success) {
                        $('#otp-request-section').hide();
                        $('#otp-verify-section').show();
                        startOTPCountdown(300); // 5 minutes
                        
                        // If development OTP is provided, show it
                        if (res.dev_otp) {
                            showMessage('OTP generated: ' + res.dev_otp + ' (Email unavailable in dev mode)', 'success');
                            console.log('🔐 Development OTP:', res.dev_otp);
                        } else {
                            showMessage('OTP sent successfully! Check your email.', 'success');
                        }
                    } else {
                        showMessage('Failed to send OTP: ' + (res.error || 'Unknown error'), 'error');
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    showMessage('Server returned invalid response. Check console for details.', 'error');
                }
            })
            .catch(error => {
                console.error('Failed to send OTP:', error);
                showMessage('Failed to send OTP. Please try again.', 'error');
            })
            .finally(() => {
                btn.prop('disabled', false).html(originalText);
            });
        }

        // Function to verify OTP
        function verifyOTP() {
            const otp = $('#otp-input').val().trim();
            
            if (otp.length !== 6) {
                showMessage('Please enter a valid 6-digit OTP code.', 'error');
                return;
            }

            const btn = $('#verify-otp-btn');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');

            fetch('chatbot/api/verify-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ otp: otp })
            })
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    showMessage('Verification successful! Redirecting...', 'success');
                    closeOTPModal();
                    setTimeout(() => {
                        window.location.href = 'chatbot/cb-database-settings.php';
                    }, 1000);
                } else {
                    showMessage('Invalid OTP code. Please try again.', 'error');
                    $('#otp-input').val('').focus();
                }
            })
            .catch(error => {
                console.error('Failed to verify OTP:', error);
                showMessage('Verification failed. Please try again.', 'error');
            })
            .finally(() => {
                btn.prop('disabled', false).html(originalText);
            });
        }

        // Function to start OTP countdown
        function startOTPCountdown(seconds) {
            if (window.otpCountdownInterval) {
                clearInterval(window.otpCountdownInterval);
            }

            let remaining = seconds;
            const countdownEl = $('#otp-countdown');

            window.otpCountdownInterval = setInterval(() => {
                remaining--;
                const minutes = Math.floor(remaining / 60);
                const secs = remaining % 60;
                countdownEl.text(`${minutes}:${secs.toString().padStart(2, '0')}`);

                if (remaining <= 0) {
                    clearInterval(window.otpCountdownInterval);
                    countdownEl.text('Expired');
                    showMessage('OTP has expired. Please request a new one.', 'error');
                }
            }, 1000);
        }
    </script>

    <style>
        /* Database Preview Styles */
        .kb-database-preview {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .db-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .db-preview-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.2rem;
        }

        .db-preview-header h3 i {
            color: #4CAF50;
            margin-right: 8px;
        }

        .kb-btn-settings {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .kb-btn-settings:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .db-preview-content {
            min-height: 100px;
        }

        .db-loading, .db-error {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            color: #666;
        }

        .db-error {
            color: #f44336;
            flex-direction: column;
        }

        .db-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            max-height: 150px;
            overflow-y: auto;
        }
        
        .db-info-grid::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .db-info-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .db-info-grid::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .db-info-grid::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .db-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .db-info-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .db-info-item i {
            font-size: 1.5rem;
            color: #4CAF50;
        }

        .db-info-item > div {
            display: flex;
            flex-direction: column;
        }

        .db-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 4px;
        }

        .db-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        .db-value.status-active {
            color: #4CAF50;
        }

        .db-value.status-inactive {
            color: #f44336;
        }

        /* OTP Modal Styles */
        .otp-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .otp-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, -40%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        .otp-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .otp-modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.3rem;
        }

        .otp-modal-header h3 i {
            color: #4CAF50;
            margin-right: 10px;
        }

        .otp-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #999;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s ease;
        }

        .otp-close:hover {
            color: #333;
        }

        .otp-modal-body {
            padding: 25px;
        }

        .otp-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .otp-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e3f2fd;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #1976d2;
        }

        .otp-info i {
            font-size: 1.2rem;
        }

        .otp-success-message {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e8f5e9;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            color: #2e7d32;
        }

        .otp-success-message i {
            font-size: 1.2rem;
        }

        .otp-input-group {
            margin-bottom: 20px;
        }

        .otp-input-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .otp-input {
            width: 100%;
            padding: 12px;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            transition: border-color 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .otp-timer {
            display: block;
            margin-top: 8px;
            color: #666;
            text-align: center;
        }

        .otp-btn-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        #otp-request-section {
            text-align: center;
        }

        #otp-request-section .kb-btn {
            margin-top: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .db-info-grid {
                grid-template-columns: 1fr;
            }

            .otp-modal-content {
                width: 95%;
            }

            .otp-btn-group {
                flex-direction: column;
            }

            .otp-btn-group .kb-btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>

