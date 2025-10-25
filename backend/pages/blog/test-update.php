<?php
// Test update functionality
session_start();
$_SESSION["is_admin"] = true; // Force admin session for testing

echo "<h2>Update Post Test</h2>";

if ($_POST) {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    if ($_FILES) {
        echo "<h3>FILES Data:</h3>";
        echo "<pre>" . print_r($_FILES, true) . "</pre>";
    }
    
    // Include the update logic
    require_once "update-post.php";
} else {
    ?>
    <form method="post" enctype="multipart/form-data">
        <label>Post ID:</label>
        <input type="number" name="id" value="1" required><br><br>
        
        <label>Title:</label>
        <input type="text" name="title" value="Test Title Update" required><br><br>
        
        <label>Description:</label>
        <textarea name="description" required>Test description update content</textarea><br><br>
        
        <label>Image (optional):</label>
        <input type="file" name="image" accept="image/*"><br><br>
        
        <label>Remove Image:</label>
        <input type="checkbox" name="remove_image" value="true"><br><br>
        
        <button type="submit">Test Update</button>
    </form>
    <?php
}
?>