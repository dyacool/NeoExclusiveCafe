<?php
$page_title = "My Posts";
$additional_css = [
    "/NeoExclusiveCafe/css/users/user-blog.css"
];
$additional_js = [
    "/NeoExclusiveCafe/js/users/user-blog.js"
];

require_once "../includes/user-header.php";
require_once "../includes/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /NeoExclusiveCafe/pages/auth/login-signup.php");
    exit();
}

// Pagination settings
$posts_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Fetch blog posts from current user
$sql = "SELECT p.*, u.firstname, u.lastname 
        FROM user_blog_post p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $_SESSION['user_id'], $posts_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total number of user's posts for pagination
$count_query = "SELECT COUNT(*) as total FROM user_blog_post WHERE user_id = ?";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_row = mysqli_fetch_assoc($count_result);
$total_posts = $total_row['total'];
$total_pages = ceil($total_posts / $posts_per_page);
?>

<div class="blog-container">
    <div class="blog-header">
        <h1>My Posts</h1>
        <a href="create-blog.php" class="create-post-btn">
            <i class="fas fa-plus"></i> Create Post
        </a>
    </div>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="blog-grid">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <div class="blog-post">
                    <div class="post-header">
                        <div class="post-user">
                            <div class="user-info">
                                <span class="username"><?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?></span>
                                <span class="post-date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($post['image_path'])): ?>
                    <div class="post-image">
                        <img src="/NeoExclusiveCafe/<?php echo htmlspecialchars($post['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="post-excerpt"><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 150) . (strlen($post['content']) > 150 ? '...' : ''))); ?></p>
                        <?php if (strlen($post['content']) > 150): ?>
                            <a href="view-blog.php?id=<?php echo $post['id']; ?>" class="read-more">Read more</a>
                        <?php endif; ?>
                        
                        <div class="post-actions">
                            <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button onclick="deletePost(<?php echo $post['id']; ?>)" class="delete-btn">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
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
            <h2>You haven't created any posts yet!</h2>
            <p>Share your experience at Neo Exclusive Cafe.</p>
            <a href="create-blog.php" class="create-post-btn">Create Your First Post</a>
        </div>
    <?php endif; ?>
</div>

<script>
function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        fetch('delete-blog.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'post_id=' + postId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting post: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting post. Please try again.');
        });
    }
}
</script> 