<?php
// Load database first (it starts the session)
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    // Load Cloudinary helper only when needed
    require_once __DIR__ . "/../../includes/cloudinary-helper.php";
    
    $id = intval($_POST["id"]);
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);

    // Log the received data for debugging
    error_log("Update post request - ID: $id, Title: $title, Description length: " . strlen($description));

    // Validate inputs
    if (empty($title) || empty($description)) {
        error_log("Validation failed - empty title or description");
        echo "error_validation";
        exit();
    }

    // Fetch current post data to get cloud info
    $fetchSql = "SELECT cloud_url, cloud_public_id FROM blog_posts WHERE adblog_id = ?";
    $fetchStmt = mysqli_prepare($conn, $fetchSql);
    mysqli_stmt_bind_param($fetchStmt, "i", $id);
    mysqli_stmt_execute($fetchStmt);
    $fetchResult = mysqli_stmt_get_result($fetchStmt);
    $currentPost = mysqli_fetch_assoc($fetchResult);
    mysqli_stmt_close($fetchStmt);

    $cloud_url = $currentPost['cloud_url'] ?? '';
    $cloud_public_id = $currentPost['cloud_public_id'] ?? '';
    $updateImage = false;
    $removeImage = false;

    // Check if image should be removed
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === 'true') {
        error_log("Removing image for post ID: $id");
        
        // Delete from Cloudinary if exists
        if (!empty($cloud_public_id)) {
            deleteFromCloudinary($cloud_public_id);
        }
        
        $removeImage = true;
        $updateImage = true;
        $cloud_url = null;
        $cloud_public_id = null;
    }
    // Handle image upload if present
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing image upload for post ID: $id");
        $imageName = $_FILES['image']['name'];
        $ext = pathinfo($imageName, PATHINFO_EXTENSION);
        $allowedTypes = array("jpg", "jpeg", "png", "gif", "JPG", "JPEG", "PNG", "GIF");
        
        if (in_array($ext, $allowedTypes)) {
            // Validate file size (max 10MB)
            if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                error_log("File size exceeds 10MB limit");
                echo "error_filesize";
                exit();
            }
            
            // Delete old image from Cloudinary if exists
            if (!empty($cloud_public_id)) {
                deleteFromCloudinary($cloud_public_id);
            }
            
            // Generate unique public ID
            $publicId = 'admin_blog_' . uniqid();
            
            // Upload to Cloudinary
            $result = uploadToCloudinary($_FILES['image']['tmp_name'], 'neocafe/admin_blog', $publicId);
            
            if ($result['success']) {
                error_log("Image uploaded successfully to Cloudinary: " . $result['url']);
                $cloud_url = $result['url'];
                $cloud_public_id = $result['public_id'];
                $updateImage = true;
            } else {
                error_log("Failed to upload to Cloudinary: " . $result['error']);
                echo "error_upload: " . $result['error'];
                exit();
            }
        } else {
            error_log("Invalid file type: $ext");
            echo "error_filetype";
            exit();
        }
    }

    // Update query
    if ($updateImage) {
        if ($removeImage) {
            // Remove image - set to NULL
            $stmt = mysqli_prepare($conn, "UPDATE blog_posts SET title = ?, description = ?, cloud_url = NULL, cloud_public_id = NULL WHERE adblog_id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $title, $description, $id);
        } else {
            // Update with new image
            $stmt = mysqli_prepare($conn, "UPDATE blog_posts SET title = ?, description = ?, cloud_url = ?, cloud_public_id = ?, cloud_provider = 'cloudinary' WHERE adblog_id = ?");
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $cloud_url, $cloud_public_id, $id);
        }
    } else {
        // Update only title and description
        $stmt = mysqli_prepare($conn, "UPDATE blog_posts SET title = ?, description = ? WHERE adblog_id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $title, $description, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        error_log("Update successful - affected rows: $affected_rows");
        echo "success";
    } else {
        $error = mysqli_error($conn);
        error_log("Update failed - MySQL error: $error");
        echo "error_db: " . $error;
    }

    mysqli_stmt_close($stmt);
} else {
    error_log("Invalid request - Method: " . $_SERVER["REQUEST_METHOD"] . ", ID present: " . (isset($_POST["id"]) ? "yes" : "no"));
    echo "error_request";
}
?> 