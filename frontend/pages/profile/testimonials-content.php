<?php
// This file is meant to be included in user-profile.php
// Database connection and user_id should already be set in parent file

// Initialize variables
$show_create_button = false;
$create_button_message = '';

// Get user email for orders query
$email_query = "SELECT email FROM users WHERE id = ?";
$email_stmt = mysqli_prepare($conn, $email_query);
mysqli_stmt_bind_param($email_stmt, "i", $user_id);
mysqli_stmt_execute($email_stmt);
$email_result = mysqli_stmt_get_result($email_stmt);
$user_email_testimonial = '';
if ($email_result && $email_row = mysqli_fetch_assoc($email_result)) {
    $user_email_testimonial = $email_row['email'];
}
mysqli_stmt_close($email_stmt);

// Check completed orders
$completed_orders = 0;
$order_check_query = "SELECT COUNT(*) as completed_orders FROM orders 
                      WHERE customer_email = ? AND (status = 'Delivered' OR status = 'Picked-up')";
$order_stmt = mysqli_prepare($conn, $order_check_query);

if ($order_stmt && !empty($user_email_testimonial)) {
    mysqli_stmt_bind_param($order_stmt, "s", $user_email_testimonial);
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

// Pagination for My Testimonials
$my_posts_per_page = 6;
$my_page = isset($_GET['my_page']) ? (int)$_GET['my_page'] : 1;
$my_offset = ($my_page - 1) * $my_posts_per_page;

// Fetch user's own testimonials
$my_sql = "SELECT p.*, u.firstname, u.lastname 
        FROM user_blog_post p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$my_stmt = mysqli_prepare($conn, $my_sql);
mysqli_stmt_bind_param($my_stmt, "iii", $user_id, $my_posts_per_page, $my_offset);
mysqli_stmt_execute($my_stmt);
$my_result = mysqli_stmt_get_result($my_stmt);

// Get total of user's posts
$my_count_query = "SELECT COUNT(*) as total FROM user_blog_post WHERE user_id = ?";
$my_count_stmt = mysqli_prepare($conn, $my_count_query);
mysqli_stmt_bind_param($my_count_stmt, "i", $user_id);
mysqli_stmt_execute($my_count_stmt);
$my_count_result = mysqli_stmt_get_result($my_count_stmt);
$my_total_row = mysqli_fetch_assoc($my_count_result);
$my_total_posts = $my_total_row['total'];
$my_total_pages = ceil($my_total_posts / $my_posts_per_page);

// Pagination for Customer Testimonials (all published posts)
$all_posts_per_page = 6;
$all_page = isset($_GET['all_page']) ? (int)$_GET['all_page'] : 1;
$all_offset = ($all_page - 1) * $all_posts_per_page;

// Fetch all published testimonials
$all_sql = "SELECT p.*, u.firstname, u.lastname 
            FROM user_blog_post p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?";
$all_stmt = mysqli_prepare($conn, $all_sql);
mysqli_stmt_bind_param($all_stmt, "ii", $all_posts_per_page, $all_offset);
mysqli_stmt_execute($all_stmt);
$all_result = mysqli_stmt_get_result($all_stmt);

// Get total published posts
$all_count_query = "SELECT COUNT(*) as total FROM user_blog_post WHERE status = 'published'";
$all_count_result = mysqli_query($conn, $all_count_query);
$all_total_row = mysqli_fetch_assoc($all_count_result);
$all_total_posts = $all_total_row['total'];
$all_total_pages = ceil($all_total_posts / $all_posts_per_page);
?>

<style>
.testimonial-section {
    margin-bottom: 3rem;
}

.testimonial-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.testimonial-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.create-post-btn {
    padding: 0.75rem 1.5rem;
    background: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.create-post-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.create-post-disabled {
    padding: 0.75rem 1.5rem;
    background: var(--border-color);
    color: var(--text-light);
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: not-allowed;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.testimonial-card {
    background: var(--bg-white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.testimonial-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.testimonial-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: var(--bg-light);
}

.testimonial-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.testimonial-content {
    padding: 1.25rem;
}

.testimonial-header-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.testimonial-author {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 1rem;
}

.testimonial-date {
    font-size: 0.813rem;
    color: var(--text-light);
}

.testimonial-text {
    color: var(--text-medium);
    font-size: 0.938rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.testimonial-rating {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.testimonial-rating .star {
    font-size: 1.125rem;
    color: var(--border-color);
}

.testimonial-rating .star.filled {
    color: #fbbf24;
}

.testimonial-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

@media (max-width: 767px) {
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .testimonial-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .create-post-btn,
    .create-post-disabled {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- My Testimonials Section -->
<div class="testimonial-section">
    <div class="testimonial-header">
        <h2>My Testimonials</h2>
        <?php if ($show_create_button): ?>
            <a href="../blog/create-blog.php" class="create-post-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Submit Testimonial
            </a>
        <?php else: ?>
            <div class="create-post-disabled" title="<?php echo htmlspecialchars($create_button_message); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <rect x="5" y="11" width="14" height="10" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Submit Testimonial
            </div>
        <?php endif; ?>
    </div>

    <?php if ($my_result && mysqli_num_rows($my_result) > 0): ?>
        <div class="testimonials-grid">
            <?php while ($post = mysqli_fetch_assoc($my_result)): ?>
                <div class="testimonial-card">
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="testimonial-image">
                            <?php 
                            // Handle image path
                            $image_path = $post['image_path'];
                            
                            // If path doesn't start with 'assets/', add the prefix
                            if (strpos($image_path, 'assets/') !== 0) {
                                $image_path = 'assets/uploaded-images-users/' . basename($image_path);
                            }
                            
                            // Create the relative path
                            $display_path = '../../../' . $image_path;
                            ?>
                            <img src="<?php echo htmlspecialchars($display_path); ?>" alt="Testimonial Image" onerror="this.parentElement.style.display='none';">
                        </div>
                    <?php endif; ?>
                    
                    <div class="testimonial-content">
                        <div class="testimonial-header-info">
                            <span class="testimonial-author"><?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?></span>
                            <span class="testimonial-date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                        
                        <?php if (isset($post['rating']) && $post['rating'] > 0): ?>
                            <div class="testimonial-rating">
                                <?php 
                                $rating = intval($post['rating']);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="testimonial-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- My Testimonials Pagination -->
        <?php if ($my_total_pages > 1): ?>
        <div class="pagination">
            <?php if ($my_page > 1): ?>
                <a href="?my_page=<?php echo ($my_page - 1); ?>#testimonials" class="page-btn">&laquo;</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $my_total_pages; $i++): ?>
                <a href="?my_page=<?php echo $i; ?>#testimonials" class="page-btn <?php echo ($i == $my_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($my_page < $my_total_pages): ?>
                <a href="?my_page=<?php echo ($my_page + 1); ?>#testimonials" class="page-btn">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="64" height="64">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            <p>You haven't created any testimonials yet</p>
        </div>
    <?php endif; ?>
</div>

<!-- Customer Testimonials Section -->
<div class="testimonial-section">
    <div class="testimonial-header">
        <h2>Customer Testimonials</h2>
    </div>

    <?php if ($all_result && mysqli_num_rows($all_result) > 0): ?>
        <div class="testimonials-grid">
            <?php while ($post = mysqli_fetch_assoc($all_result)): ?>
                <div class="testimonial-card">
                    <?php if (!empty($post['image_path'])): ?>
                        <div class="testimonial-image">
                            <?php 
                            // Handle image path
                            $image_path = $post['image_path'];
                            
                            // If path doesn't start with 'assets/', add the prefix
                            if (strpos($image_path, 'assets/') !== 0) {
                                $image_path = 'assets/uploaded-images-users/' . basename($image_path);
                            }
                            
                            // Create the relative path
                            $display_path = '../../../' . $image_path;
                            ?>
                            <img src="<?php echo htmlspecialchars($display_path); ?>" alt="Testimonial Image" onerror="this.parentElement.style.display='none';">
                        </div>
                    <?php endif; ?>
                    
                    <div class="testimonial-content">
                        <div class="testimonial-header-info">
                            <span class="testimonial-author"><?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?></span>
                            <span class="testimonial-date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                        
                        <?php if (isset($post['rating']) && $post['rating'] > 0): ?>
                            <div class="testimonial-rating">
                                <?php 
                                $rating = intval($post['rating']);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="testimonial-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- All Testimonials Pagination -->
        <?php if ($all_total_pages > 1): ?>
        <div class="pagination">
            <?php if ($all_page > 1): ?>
                <a href="?all_page=<?php echo ($all_page - 1); ?>#testimonials" class="page-btn">&laquo;</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $all_total_pages; $i++): ?>
                <a href="?all_page=<?php echo $i; ?>#testimonials" class="page-btn <?php echo ($i == $all_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($all_page < $all_total_pages): ?>
                <a href="?all_page=<?php echo ($all_page + 1); ?>#testimonials" class="page-btn">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="64" height="64">
                <path d="M12 20h9"></path>
                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
            </svg>
            <p>No customer testimonials available</p>
        </div>
    <?php endif; ?>
</div>
