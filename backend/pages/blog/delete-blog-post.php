<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $conn = new mysqli("localhost", "root", "", "crud");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $post_id = intval($_POST["id"]);
    
    // First, get the image path to delete the file
    $sql = "SELECT image_path FROM blog_posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
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
    
    // Delete the post from database
    $sql = "DELETE FROM blog_posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
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