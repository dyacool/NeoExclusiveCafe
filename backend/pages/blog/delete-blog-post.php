<?php
// Include database configuration
require_once __DIR__ . "/../../../config/database-config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    // Get database connection
    $conn = getDatabaseConnection();

    $post_id = intval($_POST["id"]);
    
    // First, get the image path to delete the file (try both column names)
    $sql = "SELECT image_path FROM blog_posts WHERE adblog_id = ? OR id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        // Delete the image file if it exists
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/assets/uploaded-images-admin/" . $row['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Delete the post from database (try both column names)
    $sql = "DELETE FROM blog_posts WHERE adblog_id = ? OR id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $post_id);
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?> 