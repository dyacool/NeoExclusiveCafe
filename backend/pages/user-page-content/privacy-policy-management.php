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
require_once __DIR__ . "/../admin-includes/activity-logger.php";

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
                    logAdminActivity($conn, 'UPDATE', "Updated privacy policy content", 'privacy_policy', 1);
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
    <div class="admin-main">
        <div class="admin-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-content">
                    <div class="page-title-section">
                        <h1 class="page-title">Privacy Policy Management</h1>
                        <p class="page-subtitle">Manage your website's privacy policy content</p>
                    </div>
                    <div class="page-actions">
                        <button type="button" class="btn btn-secondary" onclick="previewPrivacyPolicy()">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Preview
                        </button>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="admin-section">
                <h2>Privacy Policy Content</h2>
                <p class="settings-info">
                    Configure your website's privacy policy that will be displayed to users. 
                    Use the rich text editor to format your content with proper styling.
                    <?php if (isset($privacy['last_updated'])): ?>
                        Last updated: <?php echo date('F j, Y, g:i a', strtotime($privacy['last_updated'])); ?>
                    <?php endif; ?>
                </p>
                <form id="privacyForm" method="POST" action="" class="admin-form">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-input"
                               value="<?php echo htmlspecialchars($privacy['title']); ?>" 
                               placeholder="Enter privacy policy title"
                               required>
                        <div class="form-help">This will be displayed as the main heading</div>
                    </div>

                    <div class="form-group">
                        <label for="content">Privacy Policy Content</label>
                        <div class="editor-wrapper">
                            <div id="editor-container" class="rich-editor"></div>
                            <textarea id="content" 
                                      name="content" 
                                      style="display: none;"
                                      required><?php echo htmlspecialchars($privacy['content']); ?></textarea>
                        </div>
                        <div class="form-help">Use the rich text editor to format your privacy policy content with headings, lists, and styling</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Privacy Policy
                        </button>
                    </div>
                </form>
            </div>

            <!-- Privacy Policy Guidelines -->
            <div class="admin-section">
                <h2>Privacy Policy Guidelines</h2>
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Information Collection</h3>
                        <p>Clearly describe what personal information you collect from users and how it's collected.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Data Usage</h3>
                        <p>Explain how you use the collected information and the legal basis for processing.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3>User Rights</h3>
                        <p>Detail user rights regarding their personal data (access, correction, deletion, etc.).</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Security Measures</h3>
                        <p>Describe the security measures you implement to protect personal information.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-1.106-1.106A6.002 6.002 0 004 10c0 .639.1 1.255.288 1.857l1.870-1.87zm3.415-2.676a4.002 4.002 0 011.17.24l1.106-1.106A6.002 6.002 0 0010 4a5.99 5.99 0 00-2.936.768l1.507 1.507a3.997 3.997 0 011.002-.134zm2.776-1.284l1.581-1.581A5.987 5.987 0 0010 4v2a3.97 3.97 0 012.35.653zM6.228 6.228L4.647 4.647A5.987 5.987 0 004 10h2c0-.87.228-1.681.628-2.386l-.4-.386z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Data Sharing</h3>
                        <p>Specify if and how you share personal information with third parties.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                        </div>
                        <h3>Contact Information</h3>
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
