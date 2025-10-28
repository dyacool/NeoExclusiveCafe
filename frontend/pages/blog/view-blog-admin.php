<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once "../../../backend/pages/admin-includes/database.php";

$page_title = "View Blog Post";
$additional_css = [
    "view-blog.css"
];

$head_extra = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">';

require_once "../../user-includes/user-header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: blog-list.php");
    exit();
}

$post_id = (int)$_GET['id'];

$sql = "SELECT * FROM blog_posts WHERE adblog_id = ?";
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
<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<div class="blog-view-container">

    <article class="blog-post">
        <header class="post-header">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                <span class="post-author"><?php echo htmlspecialchars($post['author']); ?></span>
                <span class="post-date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
            </div>
        </header>

        <?php if (!empty($post['image_path'])): ?>
        <div class="post-image">
            <?php
            echo "<!-- DEBUG: Original image_path from DB: " . htmlspecialchars($post['image_path']) . " -->";
            
            $image_url = '/assets/uploaded-images-admin/' . $post['image_path'];
            
            echo "<!-- DEBUG: Constructed image URL: " . htmlspecialchars($image_url) . " -->";
            ?>
            <img src="<?= htmlspecialchars($image_url) ?>" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                 onerror="console.log('Image failed to load: <?= htmlspecialchars($image_url) ?>'); this.style.display='none';">
        </div>
        <?php endif; ?>

        <div class="post-content">
            <?php echo nl2br(htmlspecialchars($post['description'])); ?>
        </div>
    </article>
</div>

<?php require_once "../../user-includes/user-footer.php"; ?>
