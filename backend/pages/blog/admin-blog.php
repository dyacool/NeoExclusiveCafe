<?php
    // Load database first (it starts the session)
    require_once __DIR__ . "/../admin-includes/database.php";
    require_once __DIR__ . "/../../../includes/session-manager.php";
    
    if (!SessionManager::isAdminLoggedIn()) {
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

    include __DIR__ . "/../admin-includes/navbar/navbar.php";


    // Include database configuration
    require_once __DIR__ . "/../../../config/database-config.php";

    // Pagination settings
    $posts_per_page = 9;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $posts_per_page;

    // Get database connection
    $conn = getDatabaseConnection();

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
                    <div class="instagram-post" onclick="openBlogDetails(<?php echo isset($post['adblog_id']) ? $post['adblog_id'] : $post['id']; ?>)" style="cursor: pointer;">
                        <div class="post-header">
                            <div class="user-info">
                                <span class="username"><?php echo htmlspecialchars($post['author']); ?></span>
                                <div class="actions">
                                    <div class="post-date">
                                        <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                    </div>
                                    <button class="action-btn" onclick="event.stopPropagation(); toggleActionBox(this)">⋯</button>
                                    <div class="action-box">
                                        <button class="editBtn" data-id="<?php echo isset($post['adblog_id']) ? $post['adblog_id'] : $post['id']; ?>" onclick="event.stopPropagation();"> 
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M4 20.0001H20M4 20.0001V16.0001L12 8.00012M4 20.0001L8 20.0001L16 12.0001M12 8.00012L14.8686 5.13146L14.8704 5.12976C15.2652 4.73488 15.463 4.53709 15.691 4.46301C15.8919 4.39775 16.1082 4.39775 16.3091 4.46301C16.5369 4.53704 16.7345 4.7346 17.1288 5.12892L18.8686 6.86872C19.2646 7.26474 19.4627 7.46284 19.5369 7.69117C19.6022 7.89201 19.6021 8.10835 19.5369 8.3092C19.4628 8.53736 19.265 8.73516 18.8695 9.13061L18.8686 9.13146L16 12.0001M12 8.00012L16 12.0001"></path>
                                            </svg>
                                            <span>Edit</span>
                                        </button>
                                        <button class="delete-btn-action" data-id="<?php echo isset($post['adblog_id']) ? $post['adblog_id'] : $post['id']; ?>" onclick="event.stopPropagation();">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 1.5V2.5H3C2.44772 2.5 2 2.94772 2 3.5V4.5C2 5.05228 2.44772 5.5 3 5.5H21C21.5523 5.5 22 5.05228 22 4.5V3.5C22 2.94772 21.5523 2.5 21 2.5H16V1.5C16 0.947715 15.5523 0.5 15 0.5H9C8.44772 0.5 8 0.947715 8 1.5Z"></path>
                                                <path d="M3.9231 7.5H20.0767L19.1344 20.2216C19.0183 21.7882 17.7135 23 16.1426 23H7.85724C6.28636 23 4.98148 21.7882 4.86544 20.2216L3.9231 7.5Z"></path>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
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
                            $image_url = '/assets/uploaded-images-admin/' . $post['image_path'];
                        }
                        ?>
                        
                        <?php if (!empty($image_url)): ?>
                            <div class="post-image">
                                <img src="<?= htmlspecialchars($image_url) ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
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
        <div class="modal-content edit-modal-content">
            <span class="close-btn">&times;</span>
            <h2>Edit Post</h2>
            <input type="hidden" id="editPostId"> <!-- Hidden field to store post ID -->
            
            <div class="edit-form-container">
                <div class="grp1">
                    <label>Post Title:</label>
                    <input class="post-title" type="text" id="editTitle" required>

                    <label>Post Description:</label>
                    <textarea class="post-description" id="editDescription" required></textarea>

                    <div class="image-upload-container">
                        <label class="main-img">Featured Image:</label>
                        <div class="image-upload primary-image-upload" id="editImageUpload">
                            <input type="file" id="editImageInput" accept="image/*" style="display: none;">
                            <label for="editImageInput" class="upload-btn add-img-btn" id="editUploadBtn">
                                Click to Upload Image
                            </label>
                            <div class="primary-preview-container" id="editPreviewContainer"></div>
                        </div>
                        <small>Supported files: .png, .jpg, .jpeg, .gif</small>
                    </div>

                    <div class="btn-changes">
                        <button id="cancelEdit" class="discardBtn" type="button">Cancel</button>
                        <button id="saveEdit" class="submitBtn" type="button">Save Changes</button>
                    </div>
                </div>
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
                <button id="cancelDelete" class="save-btn">Cancel</button>
                <button id="confirmDelete" class="delete-btn">Delete</button>

            </div>
        </div>
    </div>

    <script>
        function openBlogDetails(postId) {
            window.location.href = `blog-details.php?id=${postId}`;
        }

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
                fetch("delete-post.php", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: 'id=' + postId
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    console.log('Response:', data); // Debug log
                    if (data.trim() === "success") {
                        window.location.reload();
                    } else {
                        alert('Error deleting post. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting post. Please try again.');
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("editModal");
            const deleteModal = document.getElementById("deleteModal");
            const closeBtn = document.querySelectorAll(".close-btn");
            const saveBtn = document.getElementById("saveEdit");
            const cancelBtn = document.getElementById("cancelEdit");
            const confirmDeleteBtn = document.getElementById("confirmDelete");
            const cancelDeleteBtn = document.getElementById("cancelDelete");
            const editTitle = document.getElementById("editTitle");
            const editDescription = document.getElementById("editDescription");
            const postIdField = document.getElementById("editPostId");
            
            // Image upload elements
            const editImageInput = document.getElementById("editImageInput");
            const editUploadBtn = document.getElementById("editUploadBtn");
            const editPreviewContainer = document.getElementById("editPreviewContainer");
            let currentImagePath = null;
            let newImageFile = null;

            // Handle edit image upload
            editImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Create image preview
                        const imagePreview = document.createElement('img');
                        imagePreview.src = e.target.result;
                        imagePreview.className = 'image-preview';
                        
                        // Create remove button
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-btn';
                        removeBtn.innerHTML = '×';
                        removeBtn.type = 'button';
                        removeBtn.onclick = function(event) {
                            event.preventDefault();
                            event.stopPropagation();
                            editImageInput.value = '';
                            editPreviewContainer.innerHTML = '';
                            editUploadBtn.style.display = 'flex';
                            newImageFile = null;
                            
                            // If there was a current image, restore it
                            if (currentImagePath) {
                                const currentImage = document.createElement('img');
                                currentImage.src = '/assets/uploaded-images-admin/' + currentImagePath;
                                currentImage.className = 'image-preview';
                                
                                const restoreRemoveBtn = document.createElement('button');
                                restoreRemoveBtn.className = 'remove-btn';
                                restoreRemoveBtn.innerHTML = '×';
                                restoreRemoveBtn.type = 'button';
                                restoreRemoveBtn.onclick = function(event) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    editPreviewContainer.innerHTML = '';
                                    editUploadBtn.style.display = 'flex';
                                    currentImagePath = null;
                                };
                                
                                editPreviewContainer.appendChild(currentImage);
                                editPreviewContainer.appendChild(restoreRemoveBtn);
                                editUploadBtn.style.display = 'none';
                            }
                        };
                        
                        // Clear container and add new preview
                        editPreviewContainer.innerHTML = '';
                        editPreviewContainer.appendChild(imagePreview);
                        editPreviewContainer.appendChild(removeBtn);
                        
                        // Hide upload button
                        editUploadBtn.style.display = 'none';
                        
                        // Store the new file
                        newImageFile = file;
                    };
                    
                    reader.readAsDataURL(file);
                }
            });

            // Open Edit Modal on Edit Button Click
            document.querySelectorAll(".editBtn").forEach(button => {
                button.addEventListener("click", function () {
                    const postContent = this.closest(".instagram-post");
                    const postId = this.dataset.id;
                    const title = postContent.querySelector(".post-title").innerText;
                    
                    // Reset modal state first
                    editPreviewContainer.innerHTML = '';
                    editUploadBtn.style.display = 'flex';
                    newImageFile = null;
                    editImageInput.value = '';
                    currentImagePath = null;
                    
                    // Always fetch full description from server to preserve original formatting
                    fetch(`get-post-data.php?id=${postId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Fetched post data:', data); // Debug log
                            console.log('Data type:', typeof data); // Debug log
                            console.log('Data keys:', Object.keys(data)); // Debug log
                            
                            // Force set the values and log them
                            if (data.title) {
                                editTitle.value = data.title;
                                console.log('Title set successfully:', editTitle.value);
                            } else {
                                console.log('No title found in data');
                            }
                            
                            if (data.description) {
                                editDescription.value = data.description;
                                console.log('Description set successfully:', editDescription.value);
                                console.log('Description length:', data.description.length);
                            } else {
                                console.log('No description found in data');
                            }
                            
                            postIdField.value = postId;
                            console.log('Post ID set to:', postIdField.value);
                            
                            // Handle current image - support both cloud_url and image_path
                            currentImagePath = data.cloud_url || data.image_path;
                            console.log('Image path/URL:', currentImagePath); // Debug log
                            
                            if (currentImagePath && currentImagePath.trim() !== '' && currentImagePath !== null) {
                                console.log('Displaying existing image:', currentImagePath); // Debug log
                                
                                const currentImage = document.createElement('img');
                                // Use cloud_url if available, otherwise construct local path
                                if (data.cloud_url) {
                                    currentImage.src = data.cloud_url;
                                } else if (data.image_path) {
                                    currentImage.src = '/assets/uploaded-images-admin/' + data.image_path;
                                }
                                currentImage.className = 'image-preview';
                                
                                // Add error handling for image loading
                                currentImage.onerror = function() {
                                    console.error('Failed to load image:', this.src);
                                    editUploadBtn.style.display = 'flex';
                                    editPreviewContainer.innerHTML = '';
                                    currentImagePath = null;
                                };
                                
                                currentImage.onload = function() {
                                    console.log('Image loaded successfully:', this.src);
                                };
                                
                                // Create remove button for existing image
                                const removeBtn = document.createElement('button');
                                removeBtn.className = 'remove-btn';
                                removeBtn.innerHTML = '×';
                                removeBtn.type = 'button';
                                removeBtn.onclick = function(event) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    editPreviewContainer.innerHTML = '';
                                    editUploadBtn.style.display = 'flex';
                                    currentImagePath = null;
                                    newImageFile = null;
                                };
                                
                                // Clear container first, then add image and remove button
                                editPreviewContainer.innerHTML = '';
                                editPreviewContainer.appendChild(currentImage);
                                editPreviewContainer.appendChild(removeBtn);
                                editUploadBtn.style.display = 'none';
                            } else {
                                console.log('No image path found, showing upload button'); // Debug log
                                editPreviewContainer.innerHTML = '';
                                editUploadBtn.style.display = 'flex';
                            }
                            
                            // Show the modal after everything is loaded
                            modal.style.display = "flex";
                            
                            // Double-check the values after a short delay
                            setTimeout(() => {
                                console.log('Final check - Title value:', editTitle.value);
                                console.log('Final check - Description value:', editDescription.value);
                                console.log('Final check - Description element:', editDescription);
                            }, 100);
                        })
                        .catch(error => {
                            console.error('Error fetching post data:', error);
                            // Fallback: try to get description from title or use empty
                            editDescription.value = '';
                            editTitle.value = title;
                            postIdField.value = postId;
                            currentImagePath = null;
                            editPreviewContainer.innerHTML = '';
                            editUploadBtn.style.display = 'flex';
                            modal.style.display = "flex";
                        });
                });
            });

            // Handle delete button click from action box
            document.querySelectorAll(".delete-btn-action").forEach(button => {
                button.addEventListener("click", function () {
                    const postId = this.dataset.id;
                    postIdField.value = postId;
                    deleteModal.style.display = "flex";
                });
            });

            // Close Modals
            closeBtn.forEach(btn => {
                btn.addEventListener("click", function () {
                    modal.style.display = "none";
                    deleteModal.style.display = "none";
                });
            });

            // Cancel Edit
            cancelBtn.addEventListener("click", function() {
                modal.style.display = "none";
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
                const updatedTitle = editTitle.value.trim();
                const updatedDescription = editDescription.value.trim();
                const postId = postIdField.value;

                console.log('Saving post:', {id: postId, title: updatedTitle, descriptionLength: updatedDescription.length});

                // Validate inputs
                if (!updatedTitle || !updatedDescription) {
                    alert("Please fill in both title and description.");
                    return;
                }

                // Create FormData for file upload
                const formData = new FormData();
                formData.append('id', postId);
                formData.append('title', updatedTitle);
                formData.append('description', updatedDescription);
                
                // Handle image updates
                if (newImageFile) {
                    // New image selected
                    console.log('Adding new image to form data');
                    formData.append('image', newImageFile);
                } else if (!currentImagePath) {
                    // Image was removed (no current image and no new image)
                    console.log('Marking image for removal');
                    formData.append('remove_image', 'true');
                }

                // Log form data for debugging
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + (typeof pair[1] === 'object' ? 'File object' : pair[1]));
                }

                fetch("update-post.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.text();
                })
                .then(data => {
                    console.log("Server response:", data);
                    if (data.trim() === "success") {
                        alert("Post updated successfully!");
                        location.reload();
                    } else {
                        console.error("Update failed with response:", data);
                        if (data.includes('error_validation')) {
                            alert("Validation error: Please fill in all required fields.");
                        } else if (data.includes('error_upload')) {
                            alert("Error uploading image. Please try again.");
                        } else if (data.includes('error_filetype')) {
                            alert("Invalid file type. Please use JPG, PNG, or GIF files.");
                        } else if (data.includes('error_db')) {
                            alert("Database error: " + data);
                        } else {
                            alert("Error updating post: " + data);
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert("Network error updating post: " + error.message);
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