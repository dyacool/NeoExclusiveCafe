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
$upload_error = '';

// Fetch current about content
$sql = "SELECT * FROM about_content WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $about = $result->fetch_assoc();
} else {
    // Default content if none found
    $about = [
        'id' => 1,
        'title' => 'About Neo Exclusive Cafe',
        'about_text' => 'Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality coffee and exceptional service.',
        'image_path' => '/backend/assets/images/cafe-default.jpg',
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert default content
    $insert_sql = "INSERT INTO about_content (id, title, about_text, image_path) 
                  VALUES (1, '{$about['title']}', '{$about['about_text']}', '{$about['image_path']}')";
    
    if ($conn->query($insert_sql) !== TRUE) {
        $error_message = "Error creating default content: " . $conn->error;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $title = $conn->real_escape_string($_POST['title']);
    
    // Don't use real_escape_string for the about_text as it can mess with line breaks
    // Instead, we'll use prepared statements for security
    $about_text = $_POST['about_text'];
    
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
    
    // If no upload errors, update the database
    if (empty($upload_error)) {
        // Use prepared statement to properly handle text with line breaks
        $update_sql = "UPDATE about_content SET 
                      title = ?,
                      about_text = ?,
                      image_path = ?
                      WHERE id = 1";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sss", $title, $about_text, $image_path);
        
        if ($stmt->execute()) {
            $success_message = "About page content updated successfully!";
            
            // Update local variable to reflect changes
            $about['title'] = $title;
            $about['about_text'] = $about_text;
            $about['image_path'] = $image_path;
        } else {
            $error_message = "Error updating content: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Page Settings - Admin</title>
    <link rel="stylesheet" href="/backend/pages/user-page-content/about-settings.css">
</head>
<body>
    <div class="container">        
        <?php if (!empty($success_message)): ?>
            <div class="alert success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($upload_error)): ?>
            <div class="alert error"><?php echo $upload_error; ?></div>
        <?php endif; ?>
        
        <div class="settings-container">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Page Title:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($about['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="about_text">About Content:</label>
                    <textarea id="about_text" name="about_text" rows="10" required><?php echo htmlspecialchars($about['about_text']); ?></textarea>
                    <p class="help-text">Use a new line to create a new paragraph.</p>
                </div>
                
                <div class="form-group">
                    <label for="about_image">About Image:</label>
                    <div class="image-preview">
                        <?php if (!empty($about['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($about['image_path']); ?>" alt="Current About Image" id="image-preview">
                        <?php else: ?>
                            <p>No image uploaded yet</p>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="about_image" name="about_image" accept="image/*">
                    <p class="help-text">Leave empty to keep the current image. Maximum file size: 5MB</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
    
    <script>
        // Preview uploaded image before saving
        document.getElementById('about_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (5MB limit)
                if (file.size > 5000000) {
                    alert('File is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('image-preview');
                    if (preview) {
                        preview.src = event.target.result;
                    } else {
                        // Create img element if it doesn't exist
                        const imagePreviewDiv = document.querySelector('.image-preview');
                        imagePreviewDiv.innerHTML = '<img src="' + event.target.result + '" alt="Current About Image" id="image-preview">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>