<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Include the navbar and database
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Initialize variables
$success_message = '';
$error_message = '';

// Create terms_conditions table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS terms_conditions (
    id INT PRIMARY KEY DEFAULT 1,
    title VARCHAR(255) NOT NULL DEFAULT 'Terms and Conditions',
    content LONGTEXT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table_sql)) {
    $error_message = "Error creating table: " . $conn->error;
}

// Fetch current terms and conditions
$sql = "SELECT * FROM terms_conditions WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $terms = $result->fetch_assoc();
} else {
    // Default content if none found
    $terms = [
        'id' => 1,
        'title' => 'Terms and Conditions',
        'content' => '<h2>Welcome to Neo Exclusive Cafe</h2>
<p>These terms and conditions outline the rules and regulations for the use of Neo Exclusive Cafe\'s services.</p>

<h3>1. Introduction</h3>
<p>By accessing and using our services, you accept and agree to be bound by the terms and provision of this agreement.</p>

<h3>2. Use License</h3>
<p>Permission is granted to temporarily download one copy of the materials on Neo Exclusive Cafe\'s website for personal, non-commercial transitory viewing only.</p>

<h3>3. Disclaimer</h3>
<p>The materials on Neo Exclusive Cafe\'s website are provided on an \'as is\' basis. Neo Exclusive Cafe makes no warranties, expressed or implied.</p>

<h3>4. Limitations</h3>
<p>In no event shall Neo Exclusive Cafe or its suppliers be liable for any damages arising out of the use or inability to use the materials on our website.</p>

<h3>5. Privacy Policy</h3>
<p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of our services.</p>

<h3>6. Contact Information</h3>
<p>If you have any questions about these Terms and Conditions, please contact us at info@neoexclusivecafe.com</p>',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Insert default content
    $insert_sql = "INSERT INTO terms_conditions (id, title, content) 
                  VALUES (1, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param("ss", $terms['title'], $terms['content']);
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
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update
        $upsert_sql = "INSERT INTO terms_conditions (id, title, content) 
                       VALUES (1, ?, ?) 
                       ON DUPLICATE KEY UPDATE 
                       title = VALUES(title), 
                       content = VALUES(content), 
                       last_updated = CURRENT_TIMESTAMP";
        
        $stmt = $conn->prepare($upsert_sql);
        
        if ($stmt) {
            $stmt->bind_param("ss", $title, $content);
            
            if ($stmt->execute()) {
                $success_message = "Terms and conditions updated successfully!";
                logAdminActivity($conn, 'UPDATE', "Updated terms and conditions content", 'terms_conditions', 1);
                
                // Refresh the data
                $terms['title'] = $title;
                $terms['content'] = $content;
                $terms['last_updated'] = date('Y-m-d H:i:s');
            } else {
                $error_message = "Error updating terms and conditions: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Database error: " . $conn->error;
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
    <title>Terms and Conditions Management - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="terms-and-condition-management.css">
    <!-- Quill.js Editor - Free Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

    <div class="admin-main">
        <div class="admin-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-content">
                    <p class="page-subtitle">Manage your website's terms and conditions</p>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="admin-section">
                <p class="settings-info">
                    <?php if (isset($terms['last_updated'])): ?>
                        Last updated: <?php echo date('F j, Y, g:i a', strtotime($terms['last_updated'])); ?>
                    <?php endif; ?>
                </p>

                <form method="POST" action="" id="termsForm" class="admin-form">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-input"
                               value="<?php echo htmlspecialchars($terms['title'] ?? ''); ?>" 
                               placeholder="Enter title for terms and conditions"
                               required>
                        <div class="form-help">This will be displayed as the main heading</div>
                    </div>

                    <div class="form-group">
                        <label for="content">Terms and Conditions Content</label>
                        <div class="editor-wrapper">
                            <div id="editor-container" class="rich-editor"></div>
                            <textarea id="content" 
                                      name="content" 
                                      style="display: none;"
                                      required><?php echo htmlspecialchars($terms['content'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-help">Use the rich text editor to format your content with headings, lists, and styling</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Terms
                        </button>
                    </div>
                </form>
            </div>

            <!-- Content Guidelines -->
            <div class="admin-section">
                <h2>Content Guidelines</h2>
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Legal Compliance</h3>
                        <p>Ensure your terms comply with local laws and regulations. Consider consulting with legal professionals for comprehensive coverage.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 102 0V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 8V5a1 1 0 10-2 0v8a1 1 0 102 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Clear Language</h3>
                        <p>Use clear, understandable language. Avoid overly complex legal jargon that may confuse your users.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Regular Updates</h3>
                        <p>Review and update your terms regularly to reflect changes in your business practices and legal requirements.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="terms-and-condition-management.js"></script>
</body>
</html>