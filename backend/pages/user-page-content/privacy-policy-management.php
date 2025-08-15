<?php
// Start session for authentication
session_start();

// Check if user is logged in as admin
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

// Create privacy_policy table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS privacy_policy (
    id INT PRIMARY KEY DEFAULT 1,
    title VARCHAR(255) NOT NULL DEFAULT 'Privacy Policy',
    content LONGTEXT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table_sql)) {
    $error_message = "Error creating table: " . $conn->error;
}

// Fetch current privacy policy
$sql = "SELECT * FROM privacy_policy WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $privacy = $result->fetch_assoc();
} else {
    // Default content if none found
    $privacy = [
        'id' => 1,
        'title' => 'Privacy Policy',
        'content' => '<h2>Privacy Policy for Neo Exclusive Cafe</h2>
<p>At Neo Exclusive Cafe, we are committed to protecting your privacy and ensuring the security of your personal information.</p>

<h3>1. Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, make a reservation, or contact us.</p>
<ul>
<li>Personal identification information (Name, email address, phone number)</li>
<li>Payment information (processed securely through third-party providers)</li>
<li>Preferences and dietary requirements</li>
</ul>

<h3>2. How We Use Your Information</h3>
<p>We use the information we collect to:</p>
<ul>
<li>Process your orders and reservations</li>
<li>Communicate with you about your account or transactions</li>
<li>Improve our services and customer experience</li>
<li>Send you promotional communications (with your consent)</li>
</ul>

<h3>3. Information Sharing</h3>
<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

<h3>4. Data Security</h3>
<p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h3>5. Your Rights</h3>
<p>You have the right to:</p>
<ul>
<li>Access your personal information</li>
<li>Correct inaccurate information</li>
<li>Request deletion of your information</li>
<li>Opt-out of marketing communications</li>
</ul>

<h3>6. Contact Us</h3>
<p>If you have any questions about this Privacy Policy, please contact us at privacy@neoexclusivecafe.com</p>',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Insert default content
    $insert_sql = "INSERT INTO privacy_policy (id, title, content) 
                  VALUES (1, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param("ss", $privacy['title'], $privacy['content']);
        if (!$stmt->execute()) {
            $error_message = "Error creating default content: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = $_POST['content']; // Don't trim content to preserve formatting
    
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($content)) {
        $error_message = "Content is required.";
    } else {
        // Update or insert privacy policy
        $update_sql = "UPDATE privacy_policy SET title = ?, content = ? WHERE id = 1";
        $stmt = $conn->prepare($update_sql);
        
        if ($stmt) {
            $stmt->bind_param("ss", $title, $content);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Privacy policy updated successfully!";
                    // Refresh the data
                    $privacy['title'] = $title;
                    $privacy['content'] = $content;
                    $privacy['last_updated'] = date('Y-m-d H:i:s');
                } else {
                    // Try insert if update didn't affect any rows
                    $insert_sql = "INSERT INTO privacy_policy (id, title, content) VALUES (1, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("ss", $title, $content);
                        if ($insert_stmt->execute()) {
                            $success_message = "Privacy policy created successfully!";
                            $privacy['title'] = $title;
                            $privacy['content'] = $content;
                            $privacy['last_updated'] = date('Y-m-d H:i:s');
                        } else {
                            $error_message = "Error creating privacy policy: " . $insert_stmt->error;
                        }
                        $insert_stmt->close();
                    }
                }
            } else {
                $error_message = "Error updating privacy policy: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing statement: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy Management - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="privacy-policy-management.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
</head>
<body>
    <div class="dashboard-container">
        <div class="content-wrapper">
            <!-- Header -->
            <header class="page-header">
                <div class="header-content">
                    <h1>Privacy Policy Management</h1>
                    <p>Manage your website's privacy policy content</p>
                </div>
                <div class="header-actions">
                    <a href="../../frontend/pages/privacy-policy/privacy-policy.php" target="_blank" class="btn-preview">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        Preview Page
                    </a>
                </div>
            </header>

            <!-- Status Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Main Form -->
            <div class="form-container">
                <form id="privacyForm" method="POST" action="">
                    <!-- Title Section -->
                    <div class="form-group">
                        <label for="title">Privacy Policy Title</label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               value="<?php echo htmlspecialchars($privacy['title']); ?>" 
                               placeholder="Enter privacy policy title"
                               required>
                        <small class="form-help">This will be displayed as the main heading</small>
                    </div>

                    <!-- Content Section -->
                    <div class="form-group">
                        <label for="content">Privacy Policy Content</label>
                        <div id="editor-container"></div>
                        <textarea id="content" 
                                  name="content" 
                                  style="display: none;"
                                  required><?php echo htmlspecialchars($privacy['content']); ?></textarea>
                        <small class="form-help">Use the rich text editor to format your privacy policy content</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn-draft" onclick="saveDraft()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                            </svg>
                            Save Draft
                        </button>
                        <button type="submit" class="btn-save">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                            Update Privacy Policy
                        </button>
                    </div>
                </form>

                <!-- Last Updated Info -->
                <?php if (isset($privacy['last_updated'])): ?>
                    <div class="last-updated">
                        Last updated: <?php echo date('F j, Y \a\t g:i A', strtotime($privacy['last_updated'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Help Section -->
            <div class="help-section">
                <h3>Privacy Policy Guidelines</h3>
                <div class="help-content">
                    <div class="help-item">
                        <strong>Information Collection:</strong>
                        <p>Clearly describe what personal information you collect from users and how it's collected.</p>
                    </div>
                    <div class="help-item">
                        <strong>Data Usage:</strong>
                        <p>Explain how you use the collected information and the legal basis for processing.</p>
                    </div>
                    <div class="help-item">
                        <strong>Data Sharing:</strong>
                        <p>Specify if and how you share personal information with third parties.</p>
                    </div>
                    <div class="help-item">
                        <strong>User Rights:</strong>
                        <p>Detail user rights regarding their personal data (access, correction, deletion, etc.).</p>
                    </div>
                    <div class="help-item">
                        <strong>Security Measures:</strong>
                        <p>Describe the security measures you implement to protect personal information.</p>
                    </div>
                    <div class="help-item">
                        <strong>Contact Information:</strong>
                        <p>Provide clear contact information for privacy-related inquiries.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script src="privacy-policy-management.js"></script>
</body>
</html>
