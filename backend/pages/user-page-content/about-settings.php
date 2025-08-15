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
    <link rel="stylesheet" href="about-settings.css">
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

        <?php if ($upload_error): ?>
            <div class="alert error">
                <?php echo htmlspecialchars($upload_error); ?>
            </div>
        <?php endif; ?>

        <div class="settings-container">
            <div class="header">
                <h1>About Page Management</h1>
                <p class="subtitle">Manage your website's about page content and image</p>
                <?php if (isset($about['last_updated'])): ?>
                    <p class="last-updated">Last updated: <?php echo date('F j, Y, g:i a', strtotime($about['last_updated'])); ?></p>
                <?php endif; ?>
            </div>

            <form method="POST" action="" id="aboutForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Page Title</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($about['title'] ?? ''); ?>" 
                           placeholder="Enter title for about page"
                           required>
                    <div class="help-text">This will be displayed as the main heading</div>
                </div>

                <div class="form-group">
                    <label for="about_text">About Content</label>
                    <div id="editor-container" style="height: 400px;"></div>
                    <textarea id="about_text" 
                              name="about_text" 
                              style="display: none;"
                              required><?php echo htmlspecialchars($about['about_text'] ?? ''); ?></textarea>
                    <div class="help-text">Use the rich text editor to format your content with headings, lists, and styling</div>
                </div>

                <div class="form-group">
                    <label for="about_image">About Image</label>
                    <div class="image-preview">
                        <?php if (!empty($about['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($about['image_path']); ?>" alt="Current About Image" id="image-preview">
                        <?php else: ?>
                            <p>No image uploaded yet</p>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="about_image" name="about_image" accept="image/*">
                    <div class="help-text">Leave empty to keep current image. Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</div>
                </div>

                <div class="form-actions">
                    <a href="../../../frontend/pages/about/about-page.php" 
                       class="btn-preview" 
                       target="_blank">
                        Preview Frontend
                    </a>
                    <div class="action-buttons">
                        <button type="button" class="btn-draft" onclick="saveDraft()">Save Draft</button>
                        <button type="submit" class="btn-save">Update About Page</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="about-settings.js"></script>
</body>
</html>