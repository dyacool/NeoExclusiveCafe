<?php
// Redirect if not logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}
$page_title = "Neo Cafe's Corner";
$additional_css = [
    "/NeoExclusiveCafe/css/users/user-blog.css"
];
$additional_js = [
    "/NeoExclusiveCafe/js/users/user-blog.js"
];

require_once "../../user-includes/user-header.php";
require_once "../../user-includes/database.php";

// Pagination settings
$posts_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Fetch blog posts from user blog posts table
$sql = "SELECT p.*, u.firstname, u.lastname 
        FROM user_blog_post p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'published' 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $posts_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get total number of posts for pagination
$count_query = "SELECT COUNT(*) as total FROM user_blog_post WHERE status = 'published'";
$count_result = mysqli_query($conn, $count_query);
$total_row = mysqli_fetch_assoc($count_result);
$total_posts = $total_row['total'];
$total_pages = ceil($total_posts / $posts_per_page);
?>

<button class="cta" onclick="window.location.href='/NeoExclusiveCafe/pages/users/blog-page.php'">
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

    <div class="blog-header">
        <h1>Customer Reviews & Stories</h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="create-blog.php" class="create-post-btn">
                <i class="fas fa-plus"></i> Create Post
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="blog-grid">
            <?php while ($post = mysqli_fetch_assoc($result)): ?>
                <div class="blog-post">
                    <div class="post-header">
                        <div class="post-user">
                            <div class="user-info">
                                <span class="username"><?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?></span>
                                <div class="actions">
                                    <span class="post-date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                                    <button class="action-btn" onclick="toggleActionBox(this)">⋯</button>
                                    <div class="action-box">
                                        <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="edit-btn">
                                            <span style="vertical-align: middle;">Edit</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#676767" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 5px;">
                                                <path d="M4 20.0001H20M4 20.0001V16.0001L12 8.00012M4 20.0001L8 20.0001L16 12.0001M12 8.00012L14.8686 5.13146L14.8704 5.12976C15.2652 4.73488 15.463 4.53709 15.691 4.46301C15.8919 4.39775 16.1082 4.39775 16.3091 4.46301C16.5369 4.53704 16.7345 4.7346 17.1288 5.12892L18.8686 6.86872C19.2646 7.26474 19.4627 7.46284 19.5369 7.69117C19.6022 7.89201 19.6021 8.10835 19.5369 8.3092C19.4628 8.53736 19.265 8.73516 18.8695 9.13061L18.8686 9.13146L16 12.0001M12 8.00012L16 12.0001"></path>
                                            </svg>
                                        </a><br>
                                        <button onclick="deletePost(<?php echo $post['id']; ?>)" class="delete-btn">
                                            <span style="vertical-align: middle;">Delete</span>
                                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 5px;">
                                                    <path d="M8 1.5V2.5H3C2.44772 2.5 2 2.94772 2 3.5V4.5C2 5.05228 2.44772 5.5 3 5.5H21C21.5523 5.5 22 5.05228 22 4.5V3.5C22 2.94772 21.5523 2.5 21 2.5H16V1.5C16 0.947715 15.5523 0.5 15 0.5H9C8.44772 0.5 8 0.947715 8 1.5Z" fill="#9a3131"></path>
                                                    <path d="M3.9231 7.5H20.0767L19.1344 20.2216C19.0183 21.7882 17.7135 23 16.1426 23H7.85724C6.28636 23 4.98148 21.7882 4.86544 20.2216L3.9231 7.5Z" fill="#9a3131"></path>
                                                </svg>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($post['image_path'])): ?>
                    <div class="post-image">
                        <img src="<?= htmlspecialchars($post['image_path'] ?: '../../assets/images/default-blog.png') ?>" alt="Blog Image" width="50" />
                    </div>
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="post-excerpt"><?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 170) . (strlen($post['content']) > 170 ? '...' : ''))); ?></p>
                        <?php if (strlen($post['content']) > 170): ?>
                            <a href="view-blog.php?id=<?= htmlspecialchars($post['id']) ?>" class="read-more">Read more...</a>
                        <?php endif; ?>
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
            <h2>No posts yet!</h2>
            <p>Be the first to share your experience at Neo Exclusive Cafe.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create-blog.php" class="create-post-btn">Create Your First Post</a>
            <?php else: ?>
                <p>Please <a href="/NeoExclusiveCafe/pages/auth/login-signup.php">log in</a> to create a post.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleActionBox(button) {
        // Get the action box element
        const actionBox = button.nextElementSibling;

        // Toggle the action box
        const allActionBoxes = document.querySelectorAll('.action-box');
        allActionBoxes.forEach(box => {
        if (box !== actionBox) {
            box.style.display = 'none';
        }
        });
        actionBox.style.display = actionBox.style.display === 'none' || actionBox.style.display === '' ? 'block' : 'none';

        // Add click event listener to document
        const closeActionBox = (event) => {
        if (!actionBox.contains(event.target) && !button.contains(event.target)) {
            actionBox.style.display = 'none';
            document.removeEventListener('click', closeActionBox);
        }
        };

        if (actionBox.style.display === 'block') {
        // Add small delay to prevent immediate closure
        setTimeout(() => {
            document.addEventListener('click', closeActionBox);
        }, 0);
        }
    }

    function deletePost(postId) {
        if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
            fetch("../../php/users/delete-blog.php", {
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

<style>
.back-btn-basic {
    display: inline-block;
    margin: 18px 0 10px 0;
    padding: 8px 18px;
    background: #388e3c;
    color: #fff;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
    border: none;
    transition: background 0.2s;
}
.back-btn-basic:hover {
    background: #256029;
}
</style>

