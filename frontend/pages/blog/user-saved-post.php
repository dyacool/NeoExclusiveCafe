<?php
session_start();
require_once "../../user-includes/database.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle unsave post action
if (isset($_POST['unsave_post'])) {
    $post_id = $_POST['post_id'];
    $delete_query = "DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($delete_stmt);
}

// Fetch saved posts for the current user, including author information
$query = "SELECT p.*, sp.saved_at, u.username as author_name, u.profile_image as author_image
          FROM blog_posts p 
          INNER JOIN saved_posts sp ON p.id = sp.post_id 
          INNER JOIN users u ON p.user_id = u.id
          WHERE sp.user_id = ? 
          AND p.user_id != ? -- Exclude user's own posts
          ORDER BY sp.saved_at DESC";

try {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new mysqli_sql_exception("Error preparing statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new mysqli_sql_exception("Error executing statement: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new mysqli_sql_exception("Error getting result: " . mysqli_error($conn));
    }

    $saved_posts = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $hasPosts = !empty($saved_posts);

} catch (mysqli_sql_exception $e) {
    error_log("Database error in user-saved-post.php: " . $e->getMessage());
    $error_message = "An error occurred while retrieving saved posts. Please try again later.";
    $hasPosts = false;
    $saved_posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Posts - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/users/user-saved-post.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap");

        :root {
            --primary-color: #256035;
            --primary-hover: #1a4a28;
            --text-color: #256035;
            --text-light: #437c52;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --shadow: 0 4px 6px rgba(37, 96, 53, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Spectral", serif;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            padding: 2rem;
            color: var(--text-color);
        }

        .saved-posts-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .post-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .post-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-content {
            padding: 1.5rem;
        }

        .post-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .post-excerpt {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-light);
            font-size: 0.85rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .post-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: 5px;
        }

        .action-btn:hover {
            background-color: var(--bg-color);
            color: var(--primary-hover);
        }

        .no-posts {
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            color: var(--text-light);
        }

        .no-posts h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .no-posts p {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Add new styles for author information */
        .post-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--text-light);
        }

        .author-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .author-name {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Update existing post-meta style */
        .post-meta {
            margin-top: 1rem;
        }

        .post-date {
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include_once "../../php/includes/customer-navigation.php"; ?>

    <div class="saved-posts-container">
        <h1 class="page-title">Your Saved Posts</h1>

        <div class="posts-grid">
            <?php if ($hasPosts): ?>
                <?php foreach ($saved_posts as $post): ?>
                    <div class="post-card">
                        <div class="post-image">
                            <img src="<?= htmlspecialchars($post['image_path'] ?: '../../assets/images/default-blog.png') ?>" alt="Blog Image" width="50" />
                        </div>
                        <div class="post-content">
                            <div class="post-author">
                                <img src="<?php echo htmlspecialchars($post['author_image'] ?? '/NeoExclusiveCafe/assets/images/default-avatar.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($post['author_name']); ?>"
                                     class="author-avatar">
                                <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                            </div>
                            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                            <p class="post-excerpt">
                                <?php 
                                $excerpt = strip_tags($post['content']);
                                echo htmlspecialchars(substr($excerpt, 0, 150)) . (strlen($excerpt) > 150 ? '...' : '');
                                ?>
                            </p>
                            <div class="post-meta">
                                <span class="post-date">
                                    <i class="far fa-calendar"></i>
                                    Saved on <?php echo date('F j, Y', strtotime($post['saved_at'])); ?>
                                </span>
                                <div class="post-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" name="unsave_post" class="action-btn" title="Remove from saved">
                                            <i class="fas fa-bookmark"></i>
                                        </button>
                                    </form>
                                    <a href="/NeoExclusiveCafe/pages/users/view-blog.php?id=<?php echo $post['id']; ?>" 
                                       class="action-btn" title="View Post">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-posts">
                    <h3>No Saved Posts Yet</h3>
                    <p>Save interesting posts from other users to view them here later.</p>
                    <a href="/NeoExclusiveCafe/pages/users/blog-list.php" class="action-btn" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: var(--primary-color); color: white; text-decoration: none; border-radius: 5px;">
                        <i class="fas fa-book-open"></i> Browse Blog Posts
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Confirm before unsaving
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to remove this post from your saved items?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
