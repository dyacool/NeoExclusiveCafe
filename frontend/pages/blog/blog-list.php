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


            <button class="cta" onclick="window.location.href='/frontend/pages/blog/blog-dashboard.php'">
            <svg
                id="arrow-horizontal"
                xmlns="http://www.w3.org/2000/svg"
                width="30"
                height="10"
                viewBox="0 0 46 16"
            >
                <path
                id="Path_10"
                data-name="Path 10"
                d="M38,0,39.455,1.455,33.949,6.961H76V9.039H33.949l5.506,5.506L38,16l-8-8Z"
                transform="translate(-25)"
                ></path>
            </svg>
            <span class="hover-underline-animation"> Go Back </span>
        </button>
<div class="blog-container fade-in">

    <h1>Neo Cafe's Corner</h1>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="instagram-feed">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <div class="instagram-post">
                    <a href="view-blog-admin.php?id=<?php echo isset($post['adblog_id']) ? $post['adblog_id'] : $post['id']; ?>" class="post-link">
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
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="caption-text"><?php echo nl2br(htmlspecialchars(substr($post['description'], 0, 170) . (strlen($post['description']) > 170 ? '...' : ''))); ?></p>
                        <?php if (strlen($post['description']) > 170): ?>
                            <a href="view-blog-admin.php?id=<?php echo isset($post['adblog_id']) ? $post['adblog_id'] : $post['id']; ?>" class="read-more">Read more...</a>
                        <?php endif; ?>
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


