<?php
ob_start();
$page_title = "Create Blog Post - NeoExclusiveCafe";
$additional_css = [
    "/frontend/pages/blog/create-blog.css",
    "/frontend/pages/blog/back-button.css"

];

require_once "../../user-includes/user-header.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/login/user/login-signup.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $status = 'published';
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/uploaded-images-users/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedTypes = array("jpg", "jpeg", "png", "gif", "JPG", "JPEG", "PNG", "GIF");
        
        if (in_array($file_extension, $allowedTypes)) {
            $new_filename = uniqid('blog_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'assets/uploaded-images-users/' . $new_filename;
            } else {
                echo "<script>alert('Error uploading image. Please check file permissions.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');</script>";
        }
    }
    
    // Insert blog post
    $query = "INSERT INTO user_blog_post (user_id, title, content, image_path, status, created_at) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $content, $image_path, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: user-blog.php");
        exit();
        $_SESSION['error_message'] = 'Error creating blog post: ' . mysqli_error($conn);
        header("Location: user-blog.php");
        exit();
        echo "<script>alert('Error creating blog post: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Blog Post - NeoExclusiveCafe</title>
  <link rel="icon" type="image/x-icon" href="/frontend/favicon.ico">
</head>
<body>
        <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<div class="wrapper">

    <div class="create-blog-header">
        <div class="title-container">
            <h2 class="cont-title">Create a Post</h2>
        </div>

    </div>

    <form class="post-cont" action="" method="POST" enctype="multipart/form-data">
        <div class="post-container">
            <div class="dtitle">
                <label class="lbl-title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="dimage">
                <label class="lbl-title">Image (Optional)</label>
                <div class="imagecont">
                    <label class="media" for="image">
                        <div class="upload-text">Click to upload image</div>
                    </label>
                    <input multiple type="file" class="images" id="image" name="image" accept="image/*">
                </div>
            </div>

            <div class="ddescription">
                <label class="lbl-title">Description</label>
                <textarea class="description" id="content" name="content" required></textarea>
            </div>
        </div>

        <div class="buttons">
            <input type="button" id="discard" name="discard" value="Discard">
            <button class="submit" type="submit">Publish Post</button>
        </div>
    </form>
    
    <!-- Confirm discard modal -->
    <div class="popup" id="popup">
        <div class="overlay"></div>
        <div class="popup-content">
            <h2>Discard create post</h2>
            <p>Are you sure you want to discard post creation?</p>
            <div class="controls">
                <input type="button" class="cancel-btn" id="cancel-btn" value="Cancel">
                <input type="button" class="confirm-btn" id="confirm-btn" onclick="location='user-blog.php'" value="Confirm">
            </div>
        </div>
    </div>
</div>
</body>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const fileInput = document.getElementById('image');
        const mediaLabel = document.querySelector('label.media');
        
        // Create image preview element
        const imagePreview = document.createElement('img');
        imagePreview.className = 'image-preview';
        mediaLabel.appendChild(imagePreview);
        
        // Create remove image button
        const removeButton = document.createElement('button');
        removeButton.className = 'remove-image';
        removeButton.innerHTML = '×';
        removeButton.type = 'button';
        removeButton.setAttribute('aria-label', 'Remove image');
        mediaLabel.appendChild(removeButton);
        
        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Show image preview
                    imagePreview.src = e.target.result;
                    imagePreview.classList.add('preview-active');
                    
                    // Show remove button
                    removeButton.classList.add('remove-active');
                    
                    // Hide the upload text
                    const uploadText = mediaLabel.querySelector('.upload-text');
                    if (uploadText) {
                        uploadText.style.display = 'none';
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        // Handle remove image button
        removeButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Clear the file input
            fileInput.value = '';
            
            // Hide image preview
            imagePreview.classList.remove('preview-active');
            
            // Hide remove button
            removeButton.classList.remove('remove-active');
            
            // Show the upload text
            const uploadText = mediaLabel.querySelector('.upload-text');
            if (uploadText) {
                uploadText.style.display = 'block';
            }
        });

        // Modal functionality
        const discardBtn = document.querySelector("#discard");
        const popup = document.querySelector("#popup");
        const overlay = document.querySelector(".popup .overlay");
        const cancelBtn = document.querySelector(".cancel-btn");
        
        // Open modal
        discardBtn.addEventListener("click", function() {
            popup.classList.add("active");
        });
        
        // Close modal when clicking cancel
        cancelBtn.addEventListener("click", function() {
            popup.classList.remove("active");
        });
        
        // Close modal when clicking overlay
        overlay.addEventListener("click", function() {
            popup.classList.remove("active");
        });
    });
</script>

<?php require_once "../../user-includes/footer.php"; ?>
<?php ob_end_flush(); ?>