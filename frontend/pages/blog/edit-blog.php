<?php
ob_start();
// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Edit Post";
$additional_css = [
    "/frontend/pages/blog/create-blog.css"
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

            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                $image_path = $upload_path;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    // If no errors, update the post
    if (!$error) {
        $update_sql = "UPDATE user_blog_post SET title = ?, content = ?, image_path = ? WHERE id = ? AND user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssii", $title, $content, $image_path, $post_id, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success_message'] = "Post updated successfully!";
            // Determine which page to redirect to based on HTTP_REFERER
            $redirect_page = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'user-blog-post.php') !== false ? 'user-blog-post.php' : 'user-blog.php';
            header("Location: $redirect_page");
            exit();
        } else {
            $error = "Failed to update post. Please try again.";
        }
    }
}
?>

<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<!-- Confirmation Popup -->
<div id="confirmationPopup"></div>

<div class="wrapper">
    <div class="create-blog-header">
        <div class="title-container">
            <h2 class="cont-title">Edit Blog Post</h2>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showConfirmation('<?php echo addslashes($error); ?>', 'error');
            });
        </script>
    <?php endif; ?>

    <form class="post-cont" method="POST" enctype="multipart/form-data">
        <div class="post-container">
            <div class="dtitle">
                <label class="lbl-title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>

            <div class="dimage">
                <label class="lbl-title">Image (Optional)</label>
                <div class="imagecont">
                    <label class="media" for="image">
                        <?php if ($post['image_path']): ?>
                            <?php 
                            // Simplified image path handling like in user-blog.php
                            $image_path = $post['image_path'];
                            
                            // If path doesn't start with 'assets/', add the prefix
                            if (strpos($image_path, 'assets/') !== 0) {
                                $image_path = 'assets/uploaded-images-users/' . basename($image_path);
                            }
                            
                            // Create the relative path from the current location
                            $display_path = '../../' . $image_path;
                            ?>
                            <div class="upload-text" style="display: none;">Click to upload new image</div>
                            <img class="image-preview preview-active" src="<?= htmlspecialchars($display_path) ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" onerror="this.style.display='none'; this.parentNode.querySelector('.upload-text').style.display='block';">
                            <button type="button" class="remove-image remove-active">×</button>
                        <?php else: ?>
                            <div class="upload-text">Click to upload image</div>
                        <?php endif; ?>
                    </label>
                    <input type="file" class="images" id="image" name="image" accept="image/*">
                </div>
            </div>

            <div class="ddescription">
                <label class="lbl-title">Description</label>
                <textarea class="description" id="content" name="content" maxlength="500" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
        </div>

        <div class="buttons">
            <input type="button" id="discard" name="discard" value="Discard">
            <button class="submit" type="submit">Update Post</button>
        </div>
    </form>
    
    <!-- Confirm discard modal -->
    <div class="popup" id="popup">
        <div class="overlay"></div>
        <div class="popup-content">
            <h2>Discard changes</h2>
            <p>Are you sure you want to discard your changes?</p>
            <div class="controls">
                <input type="button" class="cancel-btn" id="cancel-btn" value="Cancel">
                <input type="button" class="confirm-btn" id="confirm-btn" onclick="location='<?php echo isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'user-blog-post.php') !== false ? 'user-blog-post.php' : 'user-blog.php'; ?>'" value="Confirm">
            </div>
        </div>
    </div>
</div>
<br>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const fileInput = document.getElementById('image');
        const mediaLabel = document.querySelector('label.media');
        
        // Get existing preview elements or create them
        let imagePreview = document.querySelector('.image-preview');
        let removeButton = document.querySelector('.remove-image');
        
        // Create image preview element if it doesn't exist
        if (!imagePreview) {
            imagePreview = document.createElement('img');
            imagePreview.className = 'image-preview';
            mediaLabel.appendChild(imagePreview);
        }
        
        // Create remove image button if it doesn't exist
        if (!removeButton) {
            removeButton = document.createElement('button');
            removeButton.className = 'remove-image';
            removeButton.innerHTML = '×';
            removeButton.type = 'button';
            removeButton.setAttribute('aria-label', 'Remove image');
            mediaLabel.appendChild(removeButton);
        }
        
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
        if (discardBtn) {
            discardBtn.addEventListener("click", function() {
                popup.classList.add("active");
            });
        }
        
        // Close modal when clicking cancel
        if (cancelBtn) {
            cancelBtn.addEventListener("click", function() {
                popup.classList.remove("active");
            });
        }
        
        // Close modal when clicking overlay
        if (overlay) {
            overlay.addEventListener("click", function() {
                popup.classList.remove("active");
            });
        }
    });
</script>

<style>
    /* Confirmation Popup */
    .confirmation-popup {
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%) translateY(-100px);
        background: white;
        color: #333;
        padding: 16px 24px;
        border-radius: 12px;
        z-index: 10000;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        font-weight: 600;
        min-width: 300px;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        border: 2px solid transparent;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    /* Success State - Green Theme */
    .confirmation-popup.success {
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        color: #2e7d32;
        border-color: #4caf50;
        box-shadow: 0 10px 40px rgba(76, 175, 80, 0.3);
    }

    /* Error State - Red Theme */
    .confirmation-popup.error {
        background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
        color: #c62828;
        border-color: #f44336;
        box-shadow: 0 10px 40px rgba(244, 67, 54, 0.3);
    }

    /* Show Animation */
    .confirmation-popup.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* Hide Animation */
    .confirmation-popup.hide {
        opacity: 0;
        transform: translateX(-50%) translateY(-100px);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .confirmation-popup {
            top: 70px;
            min-width: 280px;
            max-width: 90%;
            padding: 14px 20px;
            font-size: 14px;
        }
    }
</style>

<script>
    // Confirmation popup function
    function showConfirmation(message, type = 'success') {
        const popup = document.getElementById('confirmationPopup');
        const icon = type === 'success' ? '✓' : '✕';
        
        popup.innerHTML = `${icon} ${message}`;
        popup.className = `confirmation-popup ${type}`;
        
        setTimeout(() => {
            popup.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            popup.classList.remove('show');
            popup.classList.add('hide');
            setTimeout(() => {
                popup.className = '';
                popup.innerHTML = '';
            }, 400);
        }, 3000);
    }
</script>

<?php require_once "../../user-includes/footer.php"; ?>
<?php ob_end_flush(); ?>
