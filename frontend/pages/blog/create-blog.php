<?php
ob_start();

// Load database first (starts session)
if (!isset($conn)) {
    require_once __DIR__ . "/../../../backend/pages/admin-includes/database.php";
}
require_once __DIR__ . "/../../../includes/session-manager.php";

$page_title = "Create Blog Post - NeoExclusiveCafe";
$additional_css = [
    "/frontend/pages/blog/create-blog.css",
    "/frontend/pages/blog/back-button.css"

];

require_once "../../user-includes/user-header.php";

// Require user login
SessionManager::requireUserLogin('/frontend/login/user/login-signup.php');

// Check if user has completed orders (delivered or picked up)
$user_id = SessionManager::getUserId();

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

$order_check_query = "SELECT COUNT(*) as completed_orders FROM orders 
                      WHERE customer_email = ? AND (status = 'Delivered' OR status = 'Picked-up')";
$order_stmt = mysqli_prepare($conn, $order_check_query);

$completed_orders_count = 0;
$has_completed_orders = false;

if ($order_stmt && !empty($user_email)) {
    mysqli_stmt_bind_param($order_stmt, "s", $user_email);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    if ($order_result) {
        $order_row = mysqli_fetch_assoc($order_result);
        $completed_orders_count = $order_row['completed_orders'];
        $has_completed_orders = $completed_orders_count > 0;
    }
    mysqli_stmt_close($order_stmt);
} else {
    // If orders table doesn't exist or query fails, allow testimonials for now
    $has_completed_orders = true;
    $completed_orders_count = 999;
}

// Get count of existing published posts by this user
$existing_posts_count = 0;
$posts_check_query = "SELECT COUNT(*) as post_count FROM user_blog_post 
                      WHERE user_id = ? AND status = 'published'";
$posts_stmt = mysqli_prepare($conn, $posts_check_query);

if ($posts_stmt) {
    mysqli_stmt_bind_param($posts_stmt, "i", $user_id);
    mysqli_stmt_execute($posts_stmt);
    $posts_result = mysqli_stmt_get_result($posts_stmt);
    if ($posts_result) {
        $posts_row = mysqli_fetch_assoc($posts_result);
        $existing_posts_count = $posts_row['post_count'];
    }
    mysqli_stmt_close($posts_stmt);
}

// User can create a post if they have more completed orders than published posts
$can_create_post = $completed_orders_count > $existing_posts_count;

// If user doesn't have completed orders, redirect with message
if (!$has_completed_orders) {
    $_SESSION['error_message'] = "You need to have at least one completed order (delivered or picked up) before submitting a testimonial.";
    header("Location: user-blog.php");
    exit();
}

// If user has reached their post limit, redirect with message
if (!$can_create_post) {
    $_SESSION['error_message'] = "You've reached your post limit. You have {$existing_posts_count} post(s) with {$completed_orders_count} completed order(s). Complete another order to create more testimonials.";
    header("Location: user-blog.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $status = 'published';
    
    // Get rating from POST
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    
    // Insert blog post
    $query = "INSERT INTO user_blog_post (user_id, title, content, rating, status, created_at) 
              VALUES (?, '', ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isis", $user_id, $content, $rating, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = 'Your testimonial has been published successfully!';
        header("Location: user-blog.php");
        exit();
    } else {
        $_SESSION['error_message'] = 'Error creating testimonial: ' . mysqli_error($conn);
        header("Location: user-blog.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Testimonial - NeoExclusiveCafe</title>
  <link rel="icon" type="image/x-icon" href="/frontend/favicon.ico">
  <style>
    /* Star Rating Styles */
    .star-rating-container {
        margin: 20px 0;
    }
    
    .star-rating {
        display: flex;
        gap: 10px;
        font-size: 40px;
        margin-top: 10px;
    }
    
    .star-rating input {
        display: none;
    }
    
    .star-rating label {
        cursor: pointer;
        color: #ddd;
        transition: all 0.2s ease;
    }
    
    .star-rating label:hover {
        transform: scale(1.1);
    }
    
    .star-rating label.filled {
        color: #ffd700;
    }

    /* Submit Button Loader */
    .submit {
        position: relative;
        transition: all 0.3s ease;
    }

    .submit.loading {
        color: transparent;
        pointer-events: none;
        cursor: not-allowed;
    }

    .submit .loader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        display: none;
    }

    .submit.loading .loader {
        display: block;
    }

    @keyframes spin {
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

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
  </style>
</head>
<body>
        <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

<!-- Confirmation Popup -->
<div id="confirmationPopup"></div>

<div class="content-wrapper">

    <div class="create-blog-header">
        <div class="title-container">
            <h2 class="cont-title">Submit a Testimonial</h2>
        </div>

    </div>

    <form class="post-cont" action="" method="POST">
        <div class="post-container">
            <div class="star-rating-container">
                <label class="lbl-title">Rating:</label>
                <div class="star-rating">
                    <input type="radio" id="star1" name="rating" value="1" />
                    <label for="star1" title="1 star" data-rating="1">★</label>
                    
                    <input type="radio" id="star2" name="rating" value="2" />
                    <label for="star2" title="2 stars" data-rating="2">★</label>
                    
                    <input type="radio" id="star3" name="rating" value="3" />
                    <label for="star3" title="3 stars" data-rating="3">★</label>
                    
                    <input type="radio" id="star4" name="rating" value="4" />
                    <label for="star4" title="4 stars" data-rating="4">★</label>
                    
                    <input type="radio" id="star5" name="rating" value="5" checked />
                    <label for="star5" title="5 stars" data-rating="5">★</label>
                </div>
            </div>

            <div class="ddescription">
                <label class="lbl-title">Description:</label>
                <textarea class="description" id="content" name="content" maxlength="500" placeholder="Describe your experience with Neo Café…" required></textarea>
            </div>
        </div>

        <div class="buttons">
            <input type="button" id="discard" name="discard" value="Discard">
            <button class="submit" type="submit">
                <span class="loader"></span>
                Submit
            </button>
        </div>
    </form>
    
    <!-- Confirm discard modal -->
    <div class="popup" id="popup">
        <div class="overlay"></div>
        <div class="popup-content">
            <h2>Discard create post</h2>
            <p>Are you sure you want to discard post creation?</p>
            <div class="controls">
                <input type="button" class="cancel-btn" id="cancel-btn" value="Cancel">
                <input type="button" class="confirm-btn" id="confirm-btn" onclick="location='/frontend/pages/profile/user-profile.php#testimonials'" value="Confirm">
            </div>
        </div>
    </div>
</div>
</body>

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

    document.addEventListener('DOMContentLoaded', function() {
        // Star Rating System
        const starLabels = document.querySelectorAll('.star-rating label');
        const starInputs = document.querySelectorAll('.star-rating input');
        
        // Function to fill stars up to a certain rating
        function fillStars(rating) {
            starLabels.forEach(label => {
                const labelRating = parseInt(label.getAttribute('data-rating'));
                if (labelRating <= rating) {
                    label.classList.add('filled');
                } else {
                    label.classList.remove('filled');
                }
            });
        }
        
        // Initialize with default 5 stars
        fillStars(5);
        
        // Add click event to each star
        starLabels.forEach(label => {
            label.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                fillStars(rating);
            });
            
            // Hover effect
            label.addEventListener('mouseenter', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                fillStars(rating);
            });
        });
        
        // Restore selected rating when mouse leaves
        document.querySelector('.star-rating').addEventListener('mouseleave', function() {
            const checkedInput = document.querySelector('.star-rating input:checked');
            if (checkedInput) {
                fillStars(parseInt(checkedInput.value));
            }
        });
        
        // Form submission with loader
        const form = document.querySelector('.post-cont');
        const submitBtn = document.querySelector('.submit');
        
        form.addEventListener('submit', function(e) {
            // Show loader
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });
        
        // Modal functionality
        const discardBtn = document.querySelector("#discard");
        const popup = document.querySelector("#popup");
        const overlay = document.querySelector(".popup .overlay");
        const cancelBtn = document.querySelector(".cancel-btn");
        
        // Open modal
        discardBtn.addEventListener("click", function() {
            popup.classList.add("active");
        });
        
        // Close modal when clicking cancel
        cancelBtn.addEventListener("click", function() {
            popup.classList.remove("active");
        });
        
        // Close modal when clicking overlay
        overlay.addEventListener("click", function() {
            popup.classList.remove("active");
        });
    });
</script>

<?php require_once "../../user-includes/footer.php"; ?>
<?php ob_end_flush(); ?>