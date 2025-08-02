<?php
session_start();
require_once "../../php/includes/database.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login-signup.php");
    exit();
}

// Pagination settings
$posts_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Get total number of saved posts
$count_query = "SELECT COUNT(*) as total FROM saved_posts sp 
                JOIN user_blog_post p ON sp.post_id = p.id 
                WHERE sp.user_id = ? AND p.status = 'published'";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($count_stmt);
$total_result = mysqli_stmt_get_result($count_stmt);
$total_row = mysqli_fetch_assoc($total_result);
$total_posts = $total_row['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get saved posts with pagination
$query = "SELECT p.*, u.firstname, u.lastname, sp.saved_at 
          FROM saved_posts sp 
          JOIN user_blog_post p ON sp.post_id = p.id 
          JOIN users u ON p.user_id = u.id 
          WHERE sp.user_id = ? AND p.status = 'published' 
          ORDER BY sp.saved_at DESC 
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $_SESSION['user_id'], $posts_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Posts - NeoExclusiveCafe</title>
    <link rel="stylesheet" href="../../css/users/view-blog.css">
    <link rel="stylesheet" href="../../css/includes/customer-navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/users/saved-posts.css">
    <style>
        .saved-posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .post-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .post-content {
            padding: 1.5rem;
        }

        .post-title {
            font-size: 1.5rem;
            margin: 0 0 1rem 0;
            color: #333;
        }

        .post-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .post-excerpt {
            color: #444;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .read-more:hover {
            background: #444;
        }

        .no-posts {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            color: #333;
            margin: 0;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: #fff;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
        }

        .pagination a:hover {
            background: #f5f5f5;
        }

        .pagination .active {
            background: #333;
            color: #fff;
            border-color: #333;
        }
    </style>
</head>
<body>
    <?php include_once "C:/xampp/htdocs/NeoExclusive/php/includes/customer-navigation.php"; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Saved Posts</h1>
            <a href="blog-list.php" class="back-btn">Back to Blog List</a>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="saved-posts-grid">
                <?php while ($post = mysqli_fetch_assoc($result)): ?>
                    <div class="post-card">
                        <?php if ($post['image_path']): ?>
                            <img src="<?= htmlspecialchars($post['image_path'] ?: '../../assets/images/default-blog.png') ?>" 
                                 alt="Blog Image" width="50" class="post-image">
                        <?php endif; ?>
                        <div class="post-content">
                            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                            <div class="post-meta">
                                By <?php echo htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?><br>
                                Saved on <?php echo date('F j, Y', strtotime($post['saved_at'])); ?>
                            </div>
                            <p class="post-excerpt">
                                <?php echo htmlspecialchars(substr($post['content'], 0, 150)) . '...'; ?>
                            </p>
                            <a href="view-blog.php?id=<?= htmlspecialchars($post['id']) ?>" class="read-more">Read More</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="<?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-posts">
                <h2>No saved posts yet</h2>
                <p>When you save posts, they'll appear here.</p>
                <a href="blog-list.php" class="read-more">Browse Posts</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('show');
        });
    </script>
</body>
</html> 