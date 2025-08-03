<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../user-includes/preview-mode.php";
require_once "../../user-includes/database.php";

$page_title = "View Blog";
$additional_css = [
    "view-blog.css"
];

// Font for headings
$head_extra = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">';

require_once "../../user-includes/user-header.php";

// Get the blog post ID from URL
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
?>

<div class="blog-view-container">
    <div class="back-link">
        <a href="user-blog.php">&larr; Back to Blog List</a>
    </div>

    <article class="blog-post">
        <header class="post-header">
            <div class="post-meta">
                <span class="post-author"><?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?></span>
                <span class="post-date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
            </div>
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        </header>

                              <?php if (!empty($post['image_path'])): ?>
                      <div class="post-image">
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
                              <img src="<?= htmlspecialchars($image_path) ?>" alt="Blog Image" width="50" onerror="this.style.display='none';" />
                          <?php 
                          } else {
                              // File doesn't exist, don't show the image
                              echo "<!-- Image file not found: " . htmlspecialchars($file_path) . " -->";
                          }
                          ?>
                      </div>
                      <?php endif; ?>

        <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
        </div>
    </article>
</div>

<!-- Image Preview Modal -->
<div id="imagePreviewModal" class="modal">
    <span class="close-modal">&times;</span>
    <img class="modal-content" id="previewImage">
</div>

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
</script>

<?php require_once "../../php/includes/user-footer.php"; ?>