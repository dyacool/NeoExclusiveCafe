<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: /login/admin/admin-login.php");
        exit();
    }

    $page_title = "Admin - Neo Cafe's Corner";
    $additional_css = [
        "/backend/pages/blog/admin-blog.css"
    ];
    $additional_js = [
        "/backend/pages/blog/admin-blog.js"
    ];

    // Font for headings
    $head_extra = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">';

    require_once __DIR__ . "/../admin-includes/database.php";
    include __DIR__ . "/../admin-includes/navbar/navbar.php";

    // Pagination settings
    $posts_per_page = 9;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $posts_per_page;

    // Connect to database
    $conn = new mysqli("localhost", "root", "", "crud");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch blog posts from the admin blog table with pagination
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

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?php echo $page_title; ?></title>
        <link rel="stylesheet" href="/backend/pages/blog/admin-blog.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <?php echo $head_extra; ?>
    </head>

    <body>
    
    <div class="blog-container fade-in">        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="instagram-feed">
                <?php while ($post = mysqli_fetch_assoc($result)): ?>
                    <div class="instagram-post">
                        <div class="post-header">
                            <div class="user-info">
                                <span class="username"><?php echo htmlspecialchars($post['author']); ?></span>
                                <div class="actions">
                                    <div class="post-date">
                                        <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                    </div>
                                    <button class="action-btn" onclick="toggleActionBox(this)">⋯</button>
                                        <div class="action-box">
                                        <button class="editBtn" data-id="<?php echo $post['id']; ?>"> 
                                            <span style="vertical-align: middle;">Edit</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#676767" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 5px;">
                                                    <path d="M4 20.0001H20M4 20.0001V16.0001L12 8.00012M4 20.0001L8 20.0001L16 12.0001M12 8.00012L14.8686 5.13146L14.8704 5.12976C15.2652 4.73488 15.463 4.53709 15.691 4.46301C15.8919 4.39775 16.1082 4.39775 16.3091 4.46301C16.5369 4.53704 16.7345 4.7346 17.1288 5.12892L18.8686 6.86872C19.2646 7.26474 19.4627 7.46284 19.5369 7.69117C19.6022 7.89201 19.6021 8.10835 19.5369 8.3092C19.4628 8.53736 19.265 8.73516 18.8695 9.13061L18.8686 9.13146L16 12.0001M12 8.00012L16 12.0001"></path>
                                                </svg>
                                            </button><br>
                                            <button onclick="deletePost()" class="delete-btn">
                                                <span style="vertical-align: middle;">Delete</span>
                                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 5px;">
                                                        <path d="M8 1.5V2.5H3C2.44772 2.5 2 2.94772 2 3.5V4.5C2 5.05228 2.44772 5.5 3 5.5H21C21.5523 5.5 22 5.05228 22 4.5V3.5C22 2.94772 21.5523 2.5 21 2.5H16V1.5C16 0.947715 15.5523 0.5 15 0.5H9C8.44772 0.5 8 0.947715 8 1.5Z" fill="#9a3131"></path>
                                                        <path d="M3.9231 7.5H20.0767L19.1344 20.2216C19.0183 21.7882 17.7135 23 16.1426 23H7.85724C6.28636 23 4.98148 21.7882 4.86544 20.2216L3.9231 7.5Z" fill="#9a3131"></path>
                                                    </svg>
                                            </button>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($post['image_path'])): ?>
                            <div class="post-image">
                                <img src="/assets/uploaded-images-admin/<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="caption-text"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
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
                <p>Create new blog posts to populate your admin blog page.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Edit Post</h2>
            <input type="hidden" id="editPostId"> <!-- Hidden field to store post ID -->
            <label>Title:</label>
            <input type="text" id="editTitle" class="modal-input">

            <label>Description:</label>
            <textarea id="editDescription" class="modal-input"></textarea>

            <div class="modal-buttons">
                <button id="saveEdit" class="save-btn">Save Changes</button>
                <button id="deletePost" class="delete-btn">Delete Post</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>Delete Post</h2>
            <p>Are you sure you want to delete this post? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button id="confirmDelete" class="delete-btn">Delete</button>
                <button id="cancelDelete" class="save-btn">Cancel</button>
            </div>
        </div>
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
        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("editModal");
            const deleteModal = document.getElementById("deleteModal");
            const closeBtn = document.querySelectorAll(".close-btn");
            const saveBtn = document.getElementById("saveEdit");
            const deleteBtn = document.getElementById("deletePost");
            const confirmDeleteBtn = document.getElementById("confirmDelete");
            const cancelDeleteBtn = document.getElementById("cancelDelete");
            const editTitle = document.getElementById("editTitle");
            const editDescription = document.getElementById("editDescription");
            const postIdField = document.getElementById("editPostId");

            // Open Edit Modal on Edit Button Click
            document.querySelectorAll(".editBtn").forEach(button => {
                button.addEventListener("click", function () {
                    const postContent = this.closest(".instagram-post");
                    const postId = this.dataset.id;
                    const title = postContent.querySelector(".post-title").innerText;
                    const fullDescription = postContent.querySelector(".caption-text").innerText;
                    
                    // Remove "..." from truncated descriptions
                    let description = fullDescription;
                    if (fullDescription.endsWith('...')) {
                        // Need to fetch full description from server as it's truncated in the UI
                        fetch(`get-post-data.php?id=${postId}`)
                            .then(response => response.json())
                            .then(data => {
                                editDescription.value = data.description;
                            })
                            .catch(error => {
                                console.error('Error fetching post data:', error);
                                editDescription.value = fullDescription;
                            });
                    } else {
                        editDescription.value = description;
                    }

                    postIdField.value = postId;
                    editTitle.value = title;
                    
                    modal.style.display = "flex";
                });
            });

            // Close Modals
            closeBtn.forEach(btn => {
                btn.addEventListener("click", function () {
                    modal.style.display = "none";
                    deleteModal.style.display = "none";
                });
            });

            // Open Delete Confirmation Modal
            deleteBtn.addEventListener("click", function() {
                modal.style.display = "none";
                deleteModal.style.display = "flex";
            });

            // Cancel Delete
            cancelDeleteBtn.addEventListener("click", function() {
                deleteModal.style.display = "none";
                modal.style.display = "flex";
            });

            // Confirm Delete
            confirmDeleteBtn.addEventListener("click", function() {
                const postId = postIdField.value;
                
                fetch("delete-post.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${postId}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    if (data.trim() === "success") {
                        location.reload();
                    } else {
                        console.log("Server response:", data); // Debug log
                        alert("Error deleting post. Please try again.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error deleting post. Please try again.");
                });
            });

            // Save Changes
            saveBtn.addEventListener("click", function () {
                const updatedTitle = editTitle.value;
                const updatedDescription = editDescription.value;
                const postId = postIdField.value;

                fetch("update-post.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${postId}&title=${encodeURIComponent(updatedTitle)}&description=${encodeURIComponent(updatedDescription)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    if (data.trim() === "success") {
                        location.reload();
                    } else {
                        console.log("Server response:", data); // Debug log
                        alert("Error updating post. Please try again.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error updating post. Please try again.");
                });
            });

            // Close Modals When Clicking Outside
            window.addEventListener("click", function (e) {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
                if (e.target === deleteModal) {
                    deleteModal.style.display = "none";
                }
            });
        });
    </script>
    </body>
</html>