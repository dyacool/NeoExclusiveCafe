<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /NeoExclusiveCafe/pages/auth/login-signup.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $post_id = intval($_POST["id"]);
    
    // First, get the image path to delete the file
    $sql = "SELECT image_path FROM blog_posts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row && !empty($row['image_path'])) {
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
        echo "error";
    }

    $stmt->close();
} else {
    echo "error";
}

$conn->close();
?> 