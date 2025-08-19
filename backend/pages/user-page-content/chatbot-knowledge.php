<?php
// Start session for potential authentication
session_start();

// Check if user is logged in as admin (implement your authentication logic)
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
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
    <link rel="stylesheet" href="chatbot-knowledge.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="main-container">
        <div class="chatbot-container">
            <!-- Header Section -->
            <div class="chatbot-header">
                <div class="header-content">
                    <div class="header-info">
                        <h1><i class="fas fa-robot"></i> Chatbot Knowledge Base</h1>
                        <p class="header-subtitle">Train your AI assistant with information about your cafe, products, and services</p>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn-secondary" onclick="resetContent()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <button type="submit" form="knowledge-form" class="btn-primary">
                            <i class="fas fa-save"></i> Save Knowledge
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content - 2 Column Layout -->
            <div class="chatbot-content">
                <!-- Left Column - Editor -->
                <div class="editor-column">
                    <div class="editor-card">
                        <div class="card-header">
                            <h3><i class="fas fa-edit"></i> Knowledge Editor</h3>
                            <div class="editor-tools">
                                <span class="word-count">Words: <span id="word-count">0</span></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="knowledge-form">
                                <div class="form-group">
                                    <textarea 
                                        id="knowledge-content" 
                                        name="content" 
                                        class="knowledge-textarea" 
                                        placeholder="Enter comprehensive information about your cafe here...
                                        Examples of what to include:
                                        • Menu items and descriptions
                                        • Opening hours and contact information
                                        • Special offers and promotions
                                        • Policies (refund, delivery, etc.)
                                        • Location and directions
                                        • Frequently asked questions

                                        You can include URLs that will automatically become clickable links."
                                        required></textarea>
                                </div>
                                <div class="editor-tips">
                                    <div class="tip-item">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>URLs will automatically become clickable links</span>
                                    </div>
                                    <div class="tip-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Be specific and detailed for better AI responses</span>
                                    </div>
                                    <div class="tip-item last-updated-tip">
                                        <i class="fas fa-clock"></i>
                                        <span>Last saved: <span id="last-updated">Never</span></span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Preview -->
                <div class="preview-column">
                    <div class="preview-card">
                        <div class="card-header">
                            <h3><i class="fas fa-eye"></i> Live Preview</h3>
                            <div class="preview-tools">
                                <button type="button" class="tool-btn active" onclick="togglePreviewMode('formatted')" title="Formatted View">
                                    <i class="fas fa-align-left"></i>
                                </button>
                                <button type="button" class="tool-btn" onclick="togglePreviewMode('chat')" title="Chat View">
                                    <i class="fas fa-comments"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="knowledge-preview" class="preview-content">
                                <div class="preview-placeholder">
                                    <i class="fas fa-file-alt"></i>
                                    <p>Start typing in the editor to see your content preview...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="chatbot-knowledge.js"></script>
</body>
</html>
