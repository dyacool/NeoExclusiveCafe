<?php
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

$page_title = "Neo Cafe's Corner";
$additional_css = [
    "blog-list.css"
];
$additional_js = [
    "blog.js"
];

// Font for headings
$head_extra = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">';

require_once "../../user-includes/user-header.php";
require_once "../../user-includes/navbar/customer-navigation.php";

// Pagination settings
$posts_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Fetch blog posts from the admin blog table
$sql = "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $posts_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total number of posts for pagination
$count_query = "SELECT COUNT(*) as total FROM blog_posts";
$count_result = mysqli_query($conn, $count_query);
$total_row = mysqli_fetch_assoc($count_result);
$total_posts = $total_row['total'];
$total_pages = ceil($total_posts / $posts_per_page);
?>


<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<div class= "blog-container">
    <h1>Neo Cafe's Corner</h1>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="instagram-feed">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <div class="instagram-post">
                    <a href="view-blog-admin.php?id=<?php echo $post['adblog_id']; ?>" class="post-link">
                    <div class="post-header">
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($post['author']); ?></span>
                            <div class="post-date">
                                <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="post-image">
                            <?php
                            // Debug and create image URL
                            echo "<!-- DEBUG: Image path from DB: " . htmlspecialchars($post['image_path']) . " -->";
                            $image_url = '/assets/uploaded-images-admin/' . $post['image_path'];
                            echo "<!-- DEBUG: Final image URL: " . htmlspecialchars($image_url) . " -->";
                            ?>
                            <img src="<?= htmlspecialchars($image_url) ?>" 
                                alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                onerror="console.log('Image load failed: <?= htmlspecialchars($image_url) ?>'); this.style.display='none';">
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                    </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" <?php echo ($page == $i) ? 'class="current"' : ''; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="no-posts">
            <h2>No posts yet!</h2>
            <p>Check back later for the latest updates from Neo Exclusive Cafe.</p>
        </div>
    <?php endif; ?>
</div>


