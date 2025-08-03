<?php
ob_start();
// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Edit Post";
$additional_css = [
    "/frontend/pages/blog/edit-blog.css",
    "/frontend/pages/blog/back-button.css"
];

// Process all redirects first
if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/login/user/login-signup.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: user-blog-post.php");
    exit();
}

// Include header after all possible redirects
require_once "../../user-includes/user-header.php";

$post_id = $_GET['id'];

// Fetch the post details
$sql = "SELECT * FROM user_blog_post WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if post exists and belongs to the user
if (mysqli_num_rows($result) === 0) {
    header("Location: user-blog-post.php");
    exit();
}

$post = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $current_image = $post['image_path'];
    $image_path = $current_image;
    $error = null;

    // Validate inputs
    if (empty($title) || empty($content)) {
        $error = "Title and content are required.";
    }

    // Handle image upload if a new image is provided
    if (!$error && isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $error = "File is too large. Maximum size is 5MB.";
        } else {
            // Delete old image if it exists
            if ($current_image && file_exists("../../" . $current_image)) {
                unlink("../../" . $current_image);
            }

            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $upload_path = "assets/uploaded-images-users/" . $filename;
            $full_path = "../../" . $upload_path;

            // Create directory if it doesn't exist
            if (!file_exists(dirname($full_path))) {
                mkdir(dirname($full_path), 0777, true);
            }
        }
    }

    // If no errors, update the post
    if (!$error) {
        $update_sql = "UPDATE user_blog_post SET title = ?, content = ?, image_path = ? WHERE id = ? AND user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssii", $title, $content, $image_path, $post_id, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success_message'] = "Post updated successfully.";
            // Determine which page to redirect to based on HTTP_REFERER
            $redirect_page = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'user-blog-post.php') !== false ? 'user-blog-post.php' : 'user-blog.php';
            header("Location: $redirect_page");
            exit();
        } else {
            $error = "Failed to update post.";
        }
    }
}
?>

<div class="wrapper">
<div class="blog-container">
    <div class="blog-header">
        <button class="back-btn" onclick="location='user-blog-post.php'">
            <div class="arrow-wrapper">
                <div class="arrow"></div>
            </div>
            Back
        </button>
        <div class="title-container">
            <h2 class="cont-title">Edit Blog Post</h2>
        </div>
    </div>

    <div class="edit-form-container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="blog-form">
            <div class="post-container">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input class="blog-title" type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>

                <div class="dimage">
                    <label class="lbl-title">Image</label>
                    <div class="imagecont">
                        <label class="media" for="image">
                            <?php if ($post['image_path']): ?>
                                <div class="upload-text">Click to upload new image</div>
                                <?php 
                                // Handle different path formats in the database
                                $image_path = $post['image_path'];
                                if (strpos($image_path, 'uploads/blog/') === 0) {
                                    // Remove the incorrect prefix and use the correct path
                                    $image_path = '../../assets/uploaded-images-users/' . basename($image_path);
                                } elseif (strpos($image_path, 'assets/uploaded-images-users/') === 0) {
                                    // Path already has the correct prefix, just add the relative path
                                    $image_path = '../../' . $image_path;
                                } elseif (strpos($image_path, 'blog_') === 0 || strpos($image_path, '6823') === 0) {
                                    // These are user blog images without path prefix
                                    $image_path = '../../assets/uploaded-images-users/' . $image_path;
                                } else {
                                    // Default case - assume it's a user image
                                    $image_path = '../../assets/uploaded-images-users/' . $image_path;
                                }
                                
                                // Check if the file actually exists
                                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/NeoCafe/' . str_replace('../../', '', $image_path);
                                if (file_exists($file_path)) {
                                ?>
                                    <img class="image-preview preview-active" src="<?= htmlspecialchars($image_path) ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" onerror="this.style.display='none';">
                                <?php 
                                } else {
                                    // File doesn't exist, show placeholder
                                    echo '<div class="upload-text">Click to upload new image</div>';
                                    echo '<img class="image-preview" src="/placeholder.svg">';
                                }
                                ?>
                            <?php else: ?>
                                <div class="upload-text">Click to upload new image</div>
                                <img class="image-preview" src="/placeholder.svg">
                            <?php endif; ?>
                            <button type="button" class="remove-image <?php echo $post['image_path'] ? 'remove-active' : ''; ?>">×</button>
                        </label>
                        <input multiple type="file" class="images" id="image" name="image" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="content">Description</label>
                    <textarea class="description" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?php echo isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'user-blog-post.php') !== false ? 'user-blog-post.php' : 'user-blog.php'; ?>" class="cancel-btn">Cancel</a>
                <button type="submit" class="submit-btn">Update Post</button>
            </div>
        </form>
    </div>
</div>
</div>
<br>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const fileInput = document.getElementById('image');
    const mediaLabel = document.querySelector('label.media');
    const imagePreview = document.querySelector('.image-preview');
    const removeButton = document.querySelector('.remove-image');
    const uploadText = document.querySelector('.upload-text');
    
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
        imagePreview.src = '';
        
        // Hide remove button
        removeButton.classList.remove('remove-active');
        
        // Show the upload text
        if (uploadText) {
            uploadText.style.display = 'block';
        }
    });
});
</script>
<?php require_once "../../user-includes/footer.php"; ?>
<?php ob_end_flush(); ?>
