<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Include database configuration - same as other working files
require_once __DIR__ . "/../admin-includes/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
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

    $imagePath = null;
    $updateImage = false;
    $removeImage = false;

    // Check if image should be removed
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === 'true') {
        error_log("Removing image for post ID: $id");
        $removeImage = true;
        $updateImage = true;
        $imagePath = null; // Set to null to remove from database
    }
    // Handle image upload if present
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        error_log("Processing image upload for post ID: $id");
        $imageName = $_FILES['image']['name'];
        $ext = pathinfo($imageName, PATHINFO_EXTENSION);
        $allowedTypes = array("jpg", "jpeg", "png", "gif", "JPG", "JPEG", "PNG", "GIF");
        $tempName = $_FILES['image']['tmp_name'];
        
        if (in_array($ext, $allowedTypes)) {
            // Generate unique filename
            $imagePath = time() . '_' . $imageName;
            $targetPath = $_SERVER['DOCUMENT_ROOT'] . "/assets/uploaded-images-admin/" . $imagePath;
            
            // Create directory if it doesn't exist
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/assets/uploaded-images-admin/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($tempName, $targetPath)) {
                error_log("Image uploaded successfully: $imagePath");
                $updateImage = true;
            } else {
                error_log("Failed to move uploaded file: $tempName to $targetPath");
                echo "error_upload";
                exit();
            }
        } else {
            error_log("Invalid file type: $ext");
            echo "error_filetype";
            exit();
        }
    }

    // Update query - use only adblog_id like other working files
    if ($updateImage) {
        if ($removeImage) {
            // Remove image - set to NULL
            $stmt = mysqli_prepare($conn, "UPDATE blog_posts SET title = ?, description = ?, image_path = NULL WHERE adblog_id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $title, $description, $id);
        } else {
            // Update with new image
            $stmt = mysqli_prepare($conn, "UPDATE blog_posts SET title = ?, description = ?, image_path = ? WHERE adblog_id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $title, $description, $imagePath, $id);
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