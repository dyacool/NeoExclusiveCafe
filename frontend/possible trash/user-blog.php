<?php
// Load database connection first (it starts session)
if (!isset($conn)) {
    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
}

$page_title = "Neo Cafe's Corner";
$additional_css = [
    "/frontend/pages/blog/user-blog.css"
];
$additional_js = [
    "/frontend/pages/blog/user-blog.js"
];

require_once "../../user-includes/user-header.php";

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

// Initialize variables
$user_logged_in = isset($_SESSION['user_id']);
$show_create_button = false;
$create_button_message = '';

if ($user_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    // Get user email for orders query
    $email_query = "SELECT email FROM users WHERE id = ?";
    $email_stmt = mysqli_prepare($conn, $email_query);
    mysqli_stmt_bind_param($email_stmt, "i", $user_id);
    mysqli_stmt_execute($email_stmt);
    $email_result = mysqli_stmt_get_result($email_stmt);
    $user_email = '';
    if ($email_result && $email_row = mysqli_fetch_assoc($email_result)) {
        $user_email = $email_row['email'];
    }
    mysqli_stmt_close($email_stmt);
    
    // Check completed orders
    $completed_orders = 0;
    $order_check_query = "SELECT COUNT(*) as completed_orders FROM orders 
                          WHERE customer_email = ? AND (status = 'Delivered' OR status = 'Picked-up')";
    $order_stmt = mysqli_prepare($conn, $order_check_query);
    
    if ($order_stmt && !empty($user_email)) {
        mysqli_stmt_bind_param($order_stmt, "s", $user_email);
        mysqli_stmt_execute($order_stmt);
        $order_result = mysqli_stmt_get_result($order_stmt);
        if ($order_result) {
            $order_row = mysqli_fetch_assoc($order_result);
            $completed_orders = $order_row['completed_orders'];
        }
        mysqli_stmt_close($order_stmt);
    }
    
    // Check existing blog posts
    $existing_posts = 0;
    $post_check_query = "SELECT COUNT(*) as post_count FROM user_blog_post WHERE user_id = ? AND status = 'published'";
    $post_stmt = mysqli_prepare($conn, $post_check_query);
    
    if ($post_stmt) {
        mysqli_stmt_bind_param($post_stmt, "i", $user_id);
        mysqli_stmt_execute($post_stmt);
        $post_result = mysqli_stmt_get_result($post_stmt);
        if ($post_result) {
            $post_row = mysqli_fetch_assoc($post_result);
            $existing_posts = $post_row['post_count'];
        }
        mysqli_stmt_close($post_stmt);
    }
    
    // Logic: Show button only if user has more completed orders than blog posts
    if ($completed_orders > $existing_posts) {
        $show_create_button = true;
    } else if ($completed_orders == 0) {
        $create_button_message = 'Complete an order first to share your experience';
    } else if ($existing_posts >= $completed_orders) {
        $create_button_message = 'You can create 1 testimonial per completed order';
    }
}
?>

<?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<!-- Confirmation Popup -->
<div id="confirmationPopup"></div>

<div class="blog-container fade-in">

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message-box">
            <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showConfirmation('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="blog-header">
        <h1>Customer Testimonials</h1>
        <?php if ($user_logged_in): ?>
            <?php if ($show_create_button): ?>
                <a href="create-blog.php" class="create-post-btn">
                    <i class="fas fa-plus"></i> Create Post
                </a>
            <?php else: ?>
                <div class="create-post-disabled" title="<?php echo htmlspecialchars($create_button_message); ?>">
                    <i class="fas fa-lock"></i> Create Post
                </div>
            <?php endif; ?>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 20.0001H20M4 20.0001V16.0001L12 8.00012M4 20.0001L8 20.0001L16 12.0001M12 8.00012L14.8686 5.13146L14.8704 5.12976C15.2652 4.73488 15.463 4.53709 15.691 4.46301C15.8919 4.39775 16.1082 4.39775 16.3091 4.46301C16.5369 4.53704 16.7345 4.7346 17.1288 5.12892L18.8686 6.86872C19.2646 7.26474 19.4627 7.46284 19.5369 7.69117C19.6022 7.89201 19.6021 8.10835 19.5369 8.3092C19.4628 8.53736 19.265 8.73516 18.8695 9.13061L18.8686 9.13146L16 12.0001M12 8.00012L16 12.0001"></path>
                                            </svg>
                                            <span>Edit</span>
                                        </a>
                                        <button onclick="deletePost(<?php echo $post['id']; ?>)" class="delete-btn">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 1.5V2.5H3C2.44772 2.5 2 2.94772 2 3.5V4.5C2 5.05228 2.44772 5.5 3 5.5H21C21.5523 5.5 22 5.05228 22 4.5V3.5C22 2.94772 21.5523 2.5 21 2.5H16V1.5C16 0.947715 15.5523 0.5 15 0.5H9C8.44772 0.5 8 0.947715 8 1.5Z"></path>
                                                <path d="M3.9231 7.5H20.0767L19.1344 20.2216C19.0183 21.7882 17.7135 23 16.1426 23H7.85724C6.28636 23 4.98148 21.7882 4.86544 20.2216L3.9231 7.5Z"></path>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Support both old image_path and new cloud_url for backward compatibility
                    $image_url = '';
                    if (!empty($post['cloud_url'])) {
                        $image_url = $post['cloud_url'];
                    } elseif (!empty($post['image_path'])) {
                        // Fallback for old local images
                        $image_path = $post['image_path'];
                        if (strpos($image_path, 'assets/') !== 0) {
                            $image_path = 'assets/uploaded-images-users/' . basename($image_path);
                        }
                        $image_url = '../../' . $image_path;
                    }
                    ?>
                    
                    <?php if (!empty($image_url)): ?>
                    <div class="post-image">
                        <img src="<?= htmlspecialchars($image_url) ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" onerror="this.style.display='none';" />
                    </div>
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <?php if (isset($post['rating']) && $post['rating'] > 0): ?>
                        <div class="post-rating">
                            <?php 
                            $rating = intval($post['rating']);
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                        <p class="post-excerpt"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
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
            fetch("delete-blog.php", {
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

    .error-message-box {
    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
    color: #c62828;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #f44336;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.2);
}

/* Star Rating Display */
.post-rating {
    display: flex;
    gap: 4px;
    margin-bottom: 10px;
    font-size: 20px;
}

.post-rating .star {
    color: #ddd;
}

.post-rating .star.filled {
    color: #ffd700;
}

.create-post-disabled {
    font-size: 16px;
    color: #999;
    cursor: not-allowed;
    width: 150px;
    text-align: center;
    gap: 0.5rem;
    padding: 5px 15px;
    background: #f0f0f0;
    text-decoration: none;
    border-radius: 10px;
    border: 1px solid #ddd;
    opacity: 0.6;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

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

<?php include __DIR__ . "/../../user-includes/user-footer.php"; ?>
