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
$upload_error = '';

// Create about_content table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS about_content (
    id INT PRIMARY KEY DEFAULT 1,
    title VARCHAR(255) NOT NULL DEFAULT 'About Neo Exclusive Cafe',
    about_text LONGTEXT NOT NULL,
    image_path VARCHAR(500),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($create_table_sql)) {
    $error_message = "Error creating table: " . $conn->error;
}

// Fetch current about content
$sql = "SELECT * FROM about_content WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $about = $result->fetch_assoc();
} else {
    // Default content if none found
    $about = [
        'id' => 1,
        'title' => 'About Neo Exclusive Cafe',
        'about_text' => '<h2>Welcome to Neo Exclusive Cafe</h2>
<p>Our story begins with a passion for quality coffee and exceptional service that has been brewing since our establishment.</p>

<h3>Our Mission</h3>
<p>At Neo Exclusive Cafe, we believe that every cup tells a story. Our mission is to create memorable experiences through carefully crafted beverages, delicious food, and warm hospitality.</p>

<h3>Quality First</h3>
<p>We source our coffee beans from the finest farms around the world, ensuring that every cup meets our high standards of excellence. Our skilled baristas are trained to bring out the best in every blend.</p>

<h3>Community Focus</h3>
<p>More than just a cafe, we are a gathering place for the community. Whether you\'re catching up with friends, working on your next big project, or simply enjoying a quiet moment, Neo Exclusive Cafe is your home away from home.</p>

<h3>Sustainability</h3>
<p>We are committed to sustainable practices, from eco-friendly packaging to supporting fair trade coffee farmers. Every choice we make considers our impact on the environment and the communities we serve.</p>

<h3>Visit Us Today</h3>
<p>Experience the Neo Exclusive difference for yourself. We look forward to serving you and becoming part of your daily routine.</p>',
        'image_path' => '/backend/assets/images/cafe-default.jpg',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    // Insert default content
    $insert_sql = "INSERT INTO about_content (id, title, about_text, image_path) 
                  VALUES (1, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param("sss", $about['title'], $about['about_text'], $about['image_path']);
        if (!$stmt->execute()) {
            $error_message = "Error creating default content: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $about_text = $_POST['about_text']; // Don't trim content to preserve formatting
    
    $image_path = $about['image_path']; // Default to current image
    
    // Handle image upload if a new image was provided
    if (isset($_FILES['about_image']) && $_FILES['about_image']['size'] > 0) {
        // Fixed path: Navigate up two directories from current location to backend, then to assets/images
        $target_dir = __DIR__ . "/../../assets/images/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $upload_error = "Failed to create upload directory.";
            }
        }
        
        if (empty($upload_error)) {
            $file_extension = strtolower(pathinfo($_FILES["about_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "about_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check file type
            $allowed_types = ["jpg", "jpeg", "png", "gif", "webp"];
            if (!in_array($file_extension, $allowed_types)) {
                $upload_error = "Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            } 
            // Check file size (optional - 5MB limit)
            elseif ($_FILES["about_image"]["size"] > 5000000) {
                $upload_error = "Sorry, your file is too large. Maximum size is 5MB.";
            } 
            else {
                // Try to upload the file
                if (move_uploaded_file($_FILES["about_image"]["tmp_name"], $target_file)) {
                    // Store the web-accessible path in the database
                    $image_path = "/backend/assets/images/" . $new_filename;
                    
                    // Optional: Delete old image file if it exists and is not the default
                    if (!empty($about['image_path']) && 
                        $about['image_path'] !== '/backend/assets/images/cafe-default.jpg' &&
                        file_exists(__DIR__ . "/../../assets/images/" . basename($about['image_path']))) {
                        unlink(__DIR__ . "/../../assets/images/" . basename($about['image_path']));
                    }
                } else {
                    $upload_error = "Sorry, there was an error uploading your file. Please check file permissions.";
                }
            }
        }
    }
    
    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($about_text)) {
        $error_message = "Content is required.";
    } elseif (empty($upload_error)) {
        // Update or insert about content
        $update_sql = "UPDATE about_content SET title = ?, about_text = ?, image_path = ? WHERE id = 1";
        $stmt = $conn->prepare($update_sql);
        
        if ($stmt) {
            $stmt->bind_param("sss", $title, $about_text, $image_path);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "About page content updated successfully!";
                    logAdminActivity($conn, 'UPDATE', "Updated About page content", 'about_content', 1);
                    // Refresh the data
                    $about['title'] = $title;
                    $about['about_text'] = $about_text;
                    $about['image_path'] = $image_path;
                    $about['last_updated'] = date('Y-m-d H:i:s');
                } else {
                    // No rows affected, try insert
                    $insert_sql = "INSERT INTO about_content (id, title, about_text, image_path) VALUES (1, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("sss", $title, $about_text, $image_path);
                        if ($insert_stmt->execute()) {
                            $success_message = "About page content created successfully!";
                            $about['title'] = $title;
                            $about['about_text'] = $about_text;
                            $about['image_path'] = $image_path;
                            $about['last_updated'] = date('Y-m-d H:i:s');
                        } else {
                            $error_message = "Error creating about content: " . $insert_stmt->error;
                        }
                        $insert_stmt->close();
                    }
                }
            } else {
                $error_message = "Error updating about content: " . $stmt->error;
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
    <title>About Page Management - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="terms-and-condition-management.css">
    <!-- Quill.js Editor - Free Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
</head>
<body>
    <div class="admin-main">
        <div class="admin-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-content">
                    <div class="page-title-section">
                        <h1 class="page-title">About Page Management</h1>
                        <p class="page-subtitle">Manage your website's about page content and image</p>
                    </div>
                    <div class="page-actions">
                        <button type="button" class="btn btn-secondary" onclick="previewAbout()">
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

            <?php if ($upload_error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($upload_error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="admin-section">
                <h2>About Page Content</h2>
                <p class="settings-info">
                    Configure your website's about page that will be displayed to users. 
                    Use the rich text editor to format your content with proper styling.
                    <?php if (isset($about['last_updated'])): ?>
                        Last updated: <?php echo date('F j, Y, g:i a', strtotime($about['last_updated'])); ?>
                    <?php endif; ?>
                </p>

                <form method="POST" action="" id="aboutForm" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-input"
                               value="<?php echo htmlspecialchars($about['title'] ?? ''); ?>" 
                               placeholder="Enter title for about page"
                               required>
                        <div class="form-help">This will be displayed as the main heading</div>
                    </div>

                    <div class="form-group">
                        <label for="about_text">About Content</label>
                        <div class="editor-wrapper">
                            <div id="editor-container" class="rich-editor"></div>
                            <textarea id="about_text" 
                                      name="about_text" 
                                      style="display: none;"
                                      required><?php echo htmlspecialchars($about['about_text'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-help">Use the rich text editor to format your content with headings, lists, and styling</div>
                    </div>

                    <div class="form-group">
                        <label for="about_image">About Image</label>
                        <div class="image-upload-wrapper">
                            <div class="image-preview">
                                <?php if (!empty($about['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($about['image_path']); ?>" alt="Current About Image" id="image-preview">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p>No image uploaded yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="file-upload-area">
                                <input type="file" id="about_image" name="about_image" accept="image/*" class="file-input-hidden" onchange="updateFileName(this)">
                                <button type="button" class="btn btn-secondary file-upload-btn" onclick="document.getElementById('about_image').click()">
                                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Choose Image
                                </button>
                                <span class="file-name" id="file-name">No file selected</span>
                            </div>
                        </div>
                        <div class="form-help">Leave empty to keep current image. Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update About Page
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
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Compelling Story</h3>
                        <p>Tell your cafe's unique story - your origins, mission, and what makes you special. Make it personal and engaging.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Values & Mission</h3>
                        <p>Clearly communicate your values, mission, and commitment to quality, sustainability, and community.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3>Visual Appeal</h3>
                        <p>Include high-quality images that showcase your cafe's atmosphere, food, and team to create visual connection.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Team & Culture</h3>
                        <p>Introduce your team and workplace culture. Show the people behind the brand and your commitment to service.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Location & Contact</h3>
                        <p>Include information about your location, hours, and how customers can connect with you beyond just visiting.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                        </div>
                        <h3>Call to Action</h3>
                        <p>End with a clear invitation for customers to visit, try your menu, or get in touch with your establishment.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="about-settings-new.js"></script>
</body>
</html>