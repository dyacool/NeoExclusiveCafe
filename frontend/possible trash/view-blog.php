<?php
session_start();
require_once "../../php/includes/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: blog-list.php");
    exit();
}

$post_id = (int)$_GET['id'];

// Get the blog post details
$query = "SELECT p.*, u.username, u.firstname, u.lastname 
          FROM user_blog_post p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.id = ? AND p.status = 'published'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: blog-list.php");
    exit();
}

$post = mysqli_fetch_assoc($result);

// Check if the post is saved by the current user
$is_saved = false;
if (isset($_SESSION['user_id'])) {
    $save_check = mysqli_prepare($conn, "SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?");
    mysqli_stmt_bind_param($save_check, "ii", $_SESSION['user_id'], $post_id);
    mysqli_stmt_execute($save_check);
    $save_result = mysqli_stmt_get_result($save_check);
    $is_saved = mysqli_num_rows($save_result) > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - NeoExclusive</title>
    <link rel="stylesheet" href="/NeoExclusive/css/users/view-blog.css">
    <link rel="stylesheet" href="/NeoExclusive/css/includes/customer-navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include_once "C:/xampp/htdocs/NeoExclusive/php/includes/customer-navigation.php"; ?>

    <div class="container">
        <div class="blog-actions">
            <a href="blog-list.php" class="back-btn">Back to Blog List</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="save-btn <?php echo $is_saved ? 'saved' : ''; ?>" 
                        onclick="toggleSave(<?php echo $post_id; ?>)" 
                        id="saveBtn">
                    <i class="fa-<?php echo $is_saved ? 'solid' : 'regular'; ?> fa-bookmark"></i>
                    <span><?php echo $is_saved ? 'Saved' : 'Save'; ?></span>
                </button>
                
                <?php if ($_SESSION['user_id'] == $post['user_id']): ?>
                    <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="edit-btn">Edit Post</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="blog-header">
            <h1 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="blog-meta">
                By <?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?><br>
                Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
            </div>
        </div>

        <?php if ($post['image_path']): ?>
            <div class="image-preview-container">
                <img src="/NeoExclusive/<?php echo htmlspecialchars($post['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                     class="blog-image"
                     onclick="openImagePreview(this.src)">
            </div>
        <?php endif; ?>

        <div class="blog-content">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="modal">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="previewImage">
    </div>

    <!-- Alert Message -->
    <div id="alertMessage" class="alert-message"></div>

    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });

        // Image preview functionality
        function openImagePreview(imageSrc) {
            const modal = document.getElementById('imagePreviewModal');
            const modalImg = document.getElementById('previewImage');
            modal.style.display = "block";
            modalImg.src = imageSrc;
        }

        document.querySelector('.close-modal').onclick = function() {
            document.getElementById('imagePreviewModal').style.display = "none";
        }

        document.getElementById('imagePreviewModal').onclick = function(e) {
            if (e.target === this) {
                this.style.display = "none";
            }
        }

        // Save/Unsave functionality
        function toggleSave(postId) {
            const saveBtn = document.getElementById('saveBtn');
            const isSaved = saveBtn.classList.contains('saved');
            const action = isSaved ? 'unsave' : 'save';

            fetch('/NeoExclusive/php/blog/save-post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    saveBtn.classList.toggle('saved');
                    const icon = saveBtn.querySelector('i');
                    const text = saveBtn.querySelector('span');
                    
                    if (data.action === 'saved') {
                        icon.className = 'fa-solid fa-bookmark';
                        text.textContent = 'Saved';
                    } else {
                        icon.className = 'fa-regular fa-bookmark';
                        text.textContent = 'Save';
                    }
                    
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred. Please try again.', 'error');
            });
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alertMessage');
            alert.textContent = message;
            alert.className = `alert-message ${type}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                    alert.style.opacity = '1';
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html> 