<?php
// Load database first (it starts the session)
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";

if (!SessionManager::isAdminLoggedIn()) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

$page_title = "View Blog Post - Admin";
$additional_css = [
    "/backend/pages/blog/blog-details.css"
];

$head_extra = '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">';

include __DIR__ . "/../admin-includes/navbar/navbar.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-blog.php");
    exit();
}

$post_id = (int)$_GET['id'];

$sql = "SELECT * FROM blog_posts WHERE adblog_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: admin-blog.php");
    exit();
}

$post = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/backend/pages/blog/blog-details.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php echo $head_extra; ?>
</head>

<body>
    <?php include __DIR__ . '/../admin-includes/breadcrumbs/admin-breadcrumb.php'; ?>

    <div class="admin-blog-view-container fade-in">
        <article class="admin-blog-post">
            <header class="post-header">
                <div class="header-content">
                    <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="actions-dropdown">
                        <button class="action-btn" onclick="toggleActionBox(this)">⋯</button>
                        <div class="action-box">
                            <button class="editBtn" onclick="openEditModal(<?php echo $post['adblog_id']; ?>)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 20.0001H20M4 20.0001V16.0001L12 8.00012M4 20.0001L8 20.0001L16 12.0001M12 8.00012L14.8686 5.13146L14.8704 5.12976C15.2652 4.73488 15.463 4.53709 15.691 4.46301C15.8919 4.39775 16.1082 4.39775 16.3091 4.46301C16.5369 4.53704 16.7345 4.7346 17.1288 5.12892L18.8686 6.86872C19.2646 7.26474 19.4627 7.46284 19.5369 7.69117C19.6022 7.89201 19.6021 8.10835 19.5369 8.3092C19.4628 8.53736 19.265 8.73516 18.8695 9.13061L18.8686 9.13146L16 12.0001M12 8.00012L16 12.0001"></path>
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button class="delete-btn-action" onclick="deletePost(<?php echo $post['adblog_id']; ?>)">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 1.5V2.5H3C2.44772 2.5 2 2.94772 2 3.5V4.5C2 5.05228 2.44772 5.5 3 5.5H21C21.5523 5.5 22 5.05228 22 4.5V3.5C22 2.94772 21.5523 2.5 21 2.5H16V1.5C16 0.947715 15.5523 0.5 15 0.5H9C8.44772 0.5 8 0.947715 8 1.5Z"></path>
                                    <path d="M3.9231 7.5H20.0767L19.1344 20.2216C19.0183 21.7882 17.7135 23 16.1426 23H7.85724C6.28636 23 4.98148 21.7882 4.86544 20.2216L3.9231 7.5Z"></path>
                                </svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="post-meta">
                    <span class="post-author">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo htmlspecialchars($post['author']); ?>
                    </span>
                    <span class="post-date">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php echo date('F j, Y • g:i A', strtotime($post['created_at'])); ?>
                    </span>
                </div>
            </header>

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
                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                     onerror="this.style.display='none';">
            </div>
            <?php endif; ?>

            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['description'])); ?>
            </div>
        </article>
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

    <!-- Delete Modal -->
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

        function openEditModal(postId) {
            const modal = document.getElementById("editModal");
            const editTitle = document.getElementById("editTitle");
            const editDescription = document.getElementById("editDescription");
            const postIdField = document.getElementById("editPostId");
            const editPreviewContainer = document.getElementById("editPreviewContainer");
            const editUploadBtn = document.getElementById("editUploadBtn");
            const editImageInput = document.getElementById("editImageInput");
            
            // Reset modal state first
            editPreviewContainer.innerHTML = '';
            editUploadBtn.style.display = 'flex';
            newImageFile = null;
            editImageInput.value = '';
            currentImagePath = null;
            
            // Fetch post data from server
            fetch(`get-post-data.php?id=${postId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fetched post data:', data);
                    
                    editTitle.value = data.title || '';
                    editDescription.value = data.description || '';
                    postIdField.value = postId;
                    
                    // Handle current image - support both cloud_url and image_path
                    currentImagePath = data.cloud_url || data.image_path;
                    
                    if (currentImagePath && currentImagePath.trim() !== '' && currentImagePath !== null) {
                        const currentImage = document.createElement('img');
                        // Use cloud_url if available, otherwise construct local path
                        if (data.cloud_url) {
                            currentImage.src = data.cloud_url;
                        } else if (data.image_path) {
                            currentImage.src = '/assets/uploaded-images-admin/' + data.image_path;
                        }
                        currentImage.className = 'image-preview';
                        
                        currentImage.onerror = function() {
                            console.error('Failed to load image:', this.src);
                            editUploadBtn.style.display = 'flex';
                            editPreviewContainer.innerHTML = '';
                            currentImagePath = null;
                        };
                        
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
                        
                        editPreviewContainer.innerHTML = '';
                        editPreviewContainer.appendChild(currentImage);
                        editPreviewContainer.appendChild(removeBtn);
                        editUploadBtn.style.display = 'none';
                    } else {
                        editPreviewContainer.innerHTML = '';
                        editUploadBtn.style.display = 'flex';
                    }
                    
                    modal.style.display = "flex";
                })
                .catch(error => {
                    console.error('Error fetching post data:', error);
                    alert('Error loading post data. Please try again.');
                });
        }

        function deletePost(postId) {
            // Show delete modal instead of confirm dialog
            const deleteModal = document.getElementById("deleteModal");
            const confirmDeleteBtn = document.getElementById("confirmDelete");
            
            deleteModal.style.display = "flex";
            
            // Store postId for confirmation
            confirmDeleteBtn.dataset.postId = postId;
        }

        // Add fade-in animation
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.admin-blog-view-container');
            if (container) {
                container.style.opacity = '1';
            }
            
            // Modal functionality
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
                        const imagePreview = document.createElement('img');
                        imagePreview.src = e.target.result;
                        imagePreview.className = 'image-preview';
                        
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
                        
                        editPreviewContainer.innerHTML = '';
                        editPreviewContainer.appendChild(imagePreview);
                        editPreviewContainer.appendChild(removeBtn);
                        editUploadBtn.style.display = 'none';
                        newImageFile = file;
                    };
                    
                    reader.readAsDataURL(file);
                }
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
            });

            // Confirm Delete
            confirmDeleteBtn.addEventListener("click", function() {
                const postId = this.dataset.postId;
                
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
                    console.log('Response:', data);
                    if (data.trim() === "success") {
                        window.location.href = 'admin-blog.php';
                    } else {
                        alert('Error deleting post. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting post. Please try again.');
                })
                .finally(() => {
                    deleteModal.style.display = "none";
                });
            });

            // Save Changes
            saveBtn.addEventListener("click", function () {
                const updatedTitle = editTitle.value.trim();
                const updatedDescription = editDescription.value.trim();
                const postId = postIdField.value;

                console.log('Saving post:', {id: postId, title: updatedTitle, descriptionLength: updatedDescription.length});

                if (!updatedTitle || !updatedDescription) {
                    alert("Please fill in both title and description.");
                    return;
                }

                const formData = new FormData();
                formData.append('id', postId);
                formData.append('title', updatedTitle);
                formData.append('description', updatedDescription);
                
                if (newImageFile) {
                    console.log('Adding new image to form data');
                    formData.append('image', newImageFile);
                } else if (!currentImagePath) {
                    console.log('Marking image for removal');
                    formData.append('remove_image', 'true');
                }

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

            // Close Modal When Clicking Outside
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