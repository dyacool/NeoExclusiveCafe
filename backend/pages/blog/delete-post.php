<?php
// Load database first (it starts the session)
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../../../config/database-config.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Get database connection
$conn = getDatabaseConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    // Load Cloudinary helper only when needed
    require_once __DIR__ . "/../../includes/cloudinary-helper.php";
    
    $post_id = intval($_POST["id"]);
    
    // First, get the cloud info and image path to delete the file
    $sql = "SELECT cloud_public_id, image_path FROM blog_posts WHERE adblog_id = ? OR id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        // Delete from Cloudinary if cloud image exists
        if (!empty($row['cloud_public_id'])) {
            deleteFromCloudinary($row['cloud_public_id']);
        }
        // Also delete local file if it exists (for backward compatibility)
        elseif (!empty($row['image_path'])) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/assets/uploaded-images-admin/" . $row['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
    
    // Delete the post from database (try both column names)
    $sql = "DELETE FROM blog_posts WHERE adblog_id = ? OR id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $post_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
} else {
    echo "error";
}

$conn->close();
?> 