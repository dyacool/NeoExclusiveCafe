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
        // Update or insert terms and conditions
        $update_sql = "UPDATE terms_conditions SET title = ?, content = ? WHERE id = 1";
        $stmt = $conn->prepare($update_sql);
        
        if ($stmt) {
            $stmt->bind_param("ss", $title, $content);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Terms and conditions updated successfully!";
                    // Refresh the data
                    $terms['title'] = $title;
                    $terms['content'] = $content;
                    $terms['last_updated'] = date('Y-m-d H:i:s');
                } else {
                    // No rows affected, try insert
                    $insert_sql = "INSERT INTO terms_conditions (id, title, content) VALUES (1, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("ss", $title, $content);
                        if ($insert_stmt->execute()) {
                            $success_message = "Terms and conditions created successfully!";
                            $terms['title'] = $title;
                            $terms['content'] = $content;
                            $terms['last_updated'] = date('Y-m-d H:i:s');
                        } else {
                            $error_message = "Error creating terms and conditions: " . $insert_stmt->error;
                        }
                        $insert_stmt->close();
                    }
                }
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
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="header">
                <h1>Terms and Conditions Management</h1>
                <p class="subtitle">Manage your website's terms and conditions</p>
                <?php if (isset($terms['last_updated'])): ?>
                    <p class="last-updated">Last updated: <?php echo date('F j, Y, g:i a', strtotime($terms['last_updated'])); ?></p>
                <?php endif; ?>
            </div>

            <form method="POST" action="" id="termsForm">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($terms['title'] ?? ''); ?>" 
                           placeholder="Enter title for terms and conditions"
                           required>
                    <div class="help-text">This will be displayed as the main heading</div>
                </div>

                <div class="form-group">
                    <label for="content">Terms and Conditions Content</label>
                    <div id="editor-container" style="height: 400px;"></div>
                    <textarea id="content" 
                              name="content" 
                              style="display: none;"
                              required><?php echo htmlspecialchars($terms['content'] ?? ''); ?></textarea>
                    <div class="help-text">Use the rich text editor to format your content with headings, lists, and styling</div>
                </div>

                <div class="form-actions">
                    <a href="../../../frontend/pages/terms and condition/terms-and-condition.php" 
                       class="btn-preview" 
                       target="_blank">
                        Preview Frontend
                    </a>
                    <div class="action-buttons">
                        <button type="button" class="btn-draft" onclick="saveDraft()">Save Draft</button>
                        <button type="submit" class="btn-save">Update Terms</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="terms-and-condition-management.js"></script>
</body>
</html>