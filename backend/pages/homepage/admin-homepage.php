<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../login/admin/admin-auth.php";

// Fetch statistics
$stats = [];

// Total Users
$users_query = "SELECT COUNT(*) as total_users FROM users";
$users_result = $conn->query($users_query);
$stats['total_users'] = $users_result->fetch_assoc()['total_users'];

// Total Income
$income_query = "SELECT COALESCE(SUM(total_amount), 0) as total_income FROM orders WHERE status IN ('Delivered', 'Picked-up')";
$income_result = $conn->query($income_query);
$stats['total_income'] = $income_result->fetch_assoc()['total_income'];

// Total Orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$orders_result = $conn->query($orders_query);
$stats['total_orders'] = $orders_result->fetch_assoc()['total_orders'];

// Orders in Progress
$progress_query = "SELECT COUNT(*) as in_progress FROM orders WHERE status NOT IN ('Completed', 'Delivered', 'Picked-up')";
$progress_result = $conn->query($progress_query);
$stats['in_progress'] = $progress_result->fetch_assoc()['in_progress'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <!-- CSS files -->
    <link rel="stylesheet" href="../admin-includes/navbar/navbar.css">
    <link rel="stylesheet" href="../admin-includes/navbar/reset.css">
    <link rel="stylesheet" href="../admin-includes/navbar/admin-navigation.css">
    <link rel="stylesheet" href="admin-homepage.css">
    <link rel="stylesheet" href="chatbot.css">
    <link rel="stylesheet" href="../counts.css">

    <!-- Load jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Spectral", serif !important;
            background-color: #f5f5f5;
        }

        .Main {
            margin-left: 80px; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            background-color: #f5f5f5;
            padding: 0;
        }

        .sidebar:not(.collapsed) ~ .Main {
            margin-left: 250px; 
        }

        .main-container {
            display: flex;
            flex-direction: column;
            padding: 20px;
            margin-top: 20px; 
        }

        .container2{
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 20px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .Main {
                margin-left: 0;
            }

            .sidebar:not(.collapsed) ~ .Main {
                margin-left: 0;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 20px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .confirmation-content {
                width: 95%;
                margin: 20% auto;
            }
        }

        .dashboard-flex {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        @media (max-width: 1100px) {
            .dashboard-flex {
                flex-direction: column;
            }
            .dashboard-right, .dashboard-left {
                width: 100%;
                min-width: 0;
            }
        }

        /* Knowledge Base Styles */
        #knowledge-preview {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            max-height: 300px;
            overflow-y: auto;
        }
        
        #knowledge-preview h4 {
            margin-top: 0;
            color: #2f603c;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        #knowledge-preview a {
            color: #0078ff;
            text-decoration: underline;
            font-weight: bold;
            cursor: pointer;
            word-break: break-all;
        }
        
        #knowledge-preview a:hover {
            text-decoration: none;
        }
        
        #knowledge-content {
            min-height: 200px;
            padding: 12px;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .kb-helper-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.5;
        }

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
        opacity: 0;
        animation: fadeIn 1.5s ease forwards;
        }

        /* Dashboard Section Styles */
        .dashboard-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            color: #2f603c;
            margin-bottom: 20px;
        }

        .dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
    </style>
</head>
<body>
    <?php include "../admin-includes/navbar/navbar.php"; ?>

    <div class="main-container dashboard-flex fade-in">
        <div class="container1">
            <div class="content bg-teal">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['total_users']); ?></h2>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="content bg-orange">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2>₱<?php echo number_format($stats['total_income'], 2); ?></h2>
                    <p>Net Income</p>
                </div>
            </div>

            <div class="content bg-purple">
                <div class="container1-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['total_orders']); ?></h2>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="content bg-blue">
                <div class="container1-icon ibg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                    </svg>
                </div>
                <div class="container1-text">
                    <h2><?php echo number_format($stats['in_progress']); ?></h2>
                    <p>Orders in Progress</p>
                </div>
            </div>
        </div>

        <div class="container2">
            <div class="dashboard-left">
                <div class="dashboard-section">
                    <div class="dashboard-header">
                        <h1>Admin Dashboard</h1>
                    </div>
                    <div class="dashboard-content">
                        <p>Welcome to the NeoCafe Admin Dashboard. Use the navigation menu to manage your cafe operations.</p>
                        <p>For calendar management and order tracking, please visit the <a href="../calendar/calendar.php" style="color: #2f603c; text-decoration: underline;">Calendar Management</a> page.</p>
                    </div>
                </div>
            </div>
            <div class="dashboard-right" id="knowledge-base-container">
                <div class="dashboard-section">
                    <h1>Chatbot Knowledge Base</h1>
                    <p class="kb-helper-text">Update the information below to teach your chatbot about your cafe. Include details about products, services, hours, policies, etc. URLs will be displayed as clickable links in the chat.</p>
                    <form id="knowledge-form">
                        <textarea id="knowledge-content" name="content" class="faq-input" style="width:100%;" placeholder="Enter information about your cafe, products, services, policies, etc..." required></textarea>
                        <button type="submit" class="update-btn" style="margin-top:10px;">Save Edits</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to convert plain text URLs to clickable links
        function linkifyText(text) {
            // More comprehensive regex for URLs
            const urlRegex = /(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,})/gi;
            
            // Replace URLs with clickable links
            return text.replace(urlRegex, function(url) {
                const href = url.startsWith('http') ? url : 'https://' + url;
                return `<a href="${href}" target="_blank" rel="noopener noreferrer" style="color: #007bff; text-decoration: underline; cursor: pointer;">${url}</a>`;
            });
        }

        $(document).ready(function() {
            // Load current knowledge base
            fetch('get-knowledge.php')
                .then(response => response.json())
                .then(res => {
                if (res.success) {
                        $('#knowledge-content').val(res.content);
                        $('#knowledge-form').data('id', res.id);
                        
                        // Add a preview div to show how links will appear in chat
                        const previewDiv = $('<div id="knowledge-preview" style="margin-top: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><h3 style="margin-top: 0;">Preview (with clickable links):</h3><div></div></div>');
                        $('#knowledge-form').after(previewDiv);
                        
                        // Update preview when content changes
                        $('#knowledge-content').on('input', function() {
                            const content = $(this).val();
                            const linkified = linkifyText(content);
                            $('#knowledge-preview div').html(linkified).addClass('prev-p');
                        });
                        
                        // Trigger initial preview
                        $('#knowledge-content').trigger('input');
                    } else {
                        console.error('Error loading knowledge base:', res.error);
                        alert('Error loading knowledge base: ' + res.error);
                    }
                })
                .catch(error => {
                    console.error('Failed to load knowledge base:', error);
                    alert('Failed to load knowledge base. Please try again.');
            });

            // Save knowledge base
            $('#knowledge-form').on('submit', function(e) {
                e.preventDefault();
                const content = $('#knowledge-content').val();
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();
                submitBtn.prop('disabled', true).text('Saving...');
                
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
                        alert('Knowledge base updated successfully!');
                    } else {
                        alert('Failed to update: ' + (res.error || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Failed to save knowledge base:', error);
                    alert('Failed to save knowledge base. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.prop('disabled', false).text(originalText);
                });
            });
        });
    </script>
</body>
</html>