<?php
    // Load database first (it starts the session)
    require_once __DIR__ . "/../admin-includes/database.php";
    require_once __DIR__ . "/../../../includes/session-manager.php";
    require_once __DIR__ . "/../../../config/database-config.php";
    
    if (!SessionManager::isAdminLoggedIn()) {
        header("Location: /login/admin/admin-login.php");
        exit();
    }
    
    // Get database connection
    $conn = getDatabaseConnection();

?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="/backend/pages/blog/admin-blog-createpost.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://kit.fontawesome.com/b99e675b6e.js"></script>
    </head>

    <body>
        <?php
            include __DIR__ . "/../admin-includes/navbar/navbar.php";
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Load Cloudinary helper only when needed
                require_once __DIR__ . "/../../includes/cloudinary-helper.php";
                
                $title = $_POST['title'];
                $description = $_POST['description'];
                $cloud_url = '';
                $cloud_public_id = '';
                
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $allowedTypes = array("jpg", "jpeg", "png", "gif", "JPG", "JPEG", "PNG", "GIF");

                    if (in_array($ext, $allowedTypes)){
                        // Validate file size (max 10MB)
                        if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                            echo"<script>alert('File size exceeds 10MB limit.');</script>";
                        } else {
                            // Generate unique public ID
                            $publicId = 'admin_blog_' . uniqid();
                            
                            // Upload to Cloudinary
                            $result = uploadToCloudinary($_FILES['image']['tmp_name'], 'neocafe/admin_blog', $publicId);
                            
                            if ($result['success']) {
                                $cloud_url = $result['url'];
                                $cloud_public_id = $result['public_id'];
                                
                                $sql = "INSERT INTO blog_posts (title, description, cloud_url, cloud_public_id, cloud_provider, author, created_at) 
                                VALUES (?, ?, ?, ?, 'cloudinary', 'Admin', NOW())";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "ssss", $title, $description, $cloud_url, $cloud_public_id);
                                
                                if(mysqli_stmt_execute($stmt)){
                                    echo"<script>alert('New blog post created successfully'); window.location.href = '/backend/pages/blog/admin-blog.php';</script>";
                                } else {
                                    echo"<script>alert('Error creating blog post: " . mysqli_error($conn) . "');</script>";
                                }
                                mysqli_stmt_close($stmt);
                            } else {
                                echo"<script>alert('Error uploading image to cloud: " . addslashes($result['error']) . "');</script>";
                            }
                        }
                    } else {
                        echo"<script>alert('Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.');</script>";
                    }
                } else {
                    echo"<script>alert('Please select an image to upload.');</script>";
                }
            }
        ?>

        <div class="breadcrumb">
            <a href="/backend/pages/blog/admin-blog.php">Blog Posts</a>
            <span class="separator">></span>
            <span class="current">Create Post</span>
        </div>

        <div class="mainContainer">
            <form method="post" enctype="multipart/form-data" class="post-form">
                <div class="container">
                    <div class="grp1">
                        <label>Post Title:</label>
                        <input class="post-title" type="text" name="title" required>

                        <label>Post Description:</label>
                        <textarea class="post-description" name="description" required></textarea>

                        <div class="image-upload-container">
                            <label class="main-img">Featured Image:</label>
                            <div class="image-upload primary-image-upload">
                                <input type="file" name="image" id="imageInput" accept="image/*" required style="display: none;">
                                <label for="imageInput" class="upload-btn add-img-btn" id="uploadBtn">
                                    Click to Upload Image
                                </label>
                                <div class="primary-preview-container" id="previewContainer"></div>
                            </div>
                            <small>Supported files: .png, .jpg, .jpeg, .gif</small>
                        </div>

                        <div class="btn-changes">
                            <button class="discardBtn" type="button" onclick="showDiscardModal()">Discard</button>
                            <button class="submitBtn" type="submit">Create Post</button>
                        </div>
                    </div>

                    <div class="grp2">
                        <div class="post-preview">
                            <h3>Preview</h3>
                            <div class="preview-card">
                                <div class="preview-image-placeholder" id="previewImagePlaceholder">
                                    <span>Image preview will appear here</span>
                                </div>
                                <div class="preview-content">
                                    <h4 id="previewTitle">Post title will appear here</h4>
                                    <p id="previewDescription">Post description will appear here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>        <!-- Discard Confirmation Modal -->
        <div class="modal" id="discardModal">
            <div class="modal-content confirm-modal">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                    </div>
                    <h3 class="modal-title">Discard Changes</h3>
                    <button class="close" onclick="closeDiscardModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="modal-message">Are you sure you want to discard this post? All changes will be lost.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeDiscardModal()">Cancel</button>
                    <button class="btn btn-danger" onclick="window.location.href='/backend/pages/blog/admin-blog.php'">Discard</button>
                </div>
            </div>
        </div>

        <script>
            // Image preview functionality
            document.addEventListener('DOMContentLoaded', function() {
                const fileInput = document.getElementById('imageInput');
                const uploadBtn = document.getElementById('uploadBtn');
                const previewContainer = document.getElementById('previewContainer');
                const previewImagePlaceholder = document.getElementById('previewImagePlaceholder');
                const titleInput = document.querySelector('input[name="title"]');
                const descriptionInput = document.querySelector('textarea[name="description"]');
                const previewTitle = document.getElementById('previewTitle');
                const previewDescription = document.getElementById('previewDescription');

                console.log('Elements found:', {
                    fileInput: !!fileInput,
                    uploadBtn: !!uploadBtn,
                    previewContainer: !!previewContainer,
                    previewImagePlaceholder: !!previewImagePlaceholder
                });

                // Handle file selection
                fileInput.addEventListener('change', function(e) {
                    console.log('File input changed');
                    const file = e.target.files[0];
                    
                    if (file) {
                        console.log('File selected:', file.name);
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            console.log('File loaded');
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
                                fileInput.value = '';
                                previewContainer.innerHTML = '';
                                uploadBtn.style.display = 'flex';
                                previewImagePlaceholder.innerHTML = '<span>Image preview will appear here</span>';
                            };
                            
                            // Clear container and add new preview
                            previewContainer.innerHTML = '';
                            previewContainer.appendChild(imagePreview);
                            previewContainer.appendChild(removeBtn);
                            
                            // Hide upload button
                            uploadBtn.style.display = 'none';
                            
                            // Update preview placeholder
                            previewImagePlaceholder.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                        };
                        
                        reader.readAsDataURL(file);
                    }
                });

                // Handle live preview updates
                if (titleInput) {
                    titleInput.addEventListener('input', function() {
                        previewTitle.textContent = this.value || 'Post title will appear here';
                    });
                }

                if (descriptionInput) {
                    descriptionInput.addEventListener('input', function() {
                        // Replace line breaks with <br> tags for HTML display
                        const text = this.value || 'Post description will appear here';
                        previewDescription.innerHTML = text.replace(/\n/g, '<br>');
                    });
                }
            });

            // Modal functions
            function showDiscardModal() {
                const modal = document.getElementById('discardModal');
                modal.style.display = 'flex';
            }

            function closeDiscardModal() {
                const modal = document.getElementById('discardModal');
                modal.style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('discardModal');
                if (event.target === modal) {
                    closeDiscardModal();
                }
            };

            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeDiscardModal();
                }
            });
        </script>
    </body>
</html>