<?php
// Redirect if not logged in
// Don't start session if it's already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/login/user/login-signup.php");
    exit();
}

// Database connection
require_once "../../../backend/pages/admin-includes/database.php";

$page_title = "View Blog Post";
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

// Fetch the blog post
$sql = "SELECT * FROM blog_posts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
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
        <a href="blog-list.php">&larr; Back to Blog List</a>
    </div>

    <article class="blog-post">
        <header class="post-header">
            <div class="post-meta">
                <span class="post-author"><?php echo htmlspecialchars($post['author']); ?></span>
                <span class="post-date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
            </div>
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        </header>

        <?php if (!empty($post['image_path'])): ?>
        <div class="post-image">
            <?php
            $image_path = '../../assets/uploaded-images-admin/' . $post['image_path'];
            // Check if the file actually exists
            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/NeoCafe/' . str_replace('../../', '', $image_path);
            if (file_exists($file_path)) {
            ?>
                <img src="<?= htmlspecialchars($image_path) ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>" onerror="this.style.display='none';">
            <?php
            } else {
                // File doesn't exist, don't show the image
                echo "<!-- Image file not found: " . htmlspecialchars($file_path) . " -->";
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['description'])); ?>
        </div>
    </article>
</div>

<?php require_once "../../user-includes/footer.php"; ?>
