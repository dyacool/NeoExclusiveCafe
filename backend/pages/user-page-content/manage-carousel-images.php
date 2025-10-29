<?php
$page_title = "Manage Carousel Images";


require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Function to get the next available order number
function getNextAvailableOrder($conn) {
    $query = "SELECT MAX(display_order) as max_order FROM carousel_images";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    
    if ($data['max_order']) {
        return $data['max_order'] + 1;
    }
    
    return 1; // Start with 1 if no images exist
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Check if the carousel_images table exists, create it if it doesn't
$table_check_query = "SHOW TABLES LIKE 'carousel_images'";
$table_check_result = mysqli_query($conn, $table_check_query);

if (mysqli_num_rows($table_check_result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS carousel_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<div class='alert alert-success'>Carousel images table created successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error creating table: " . mysqli_error($conn) . "</div>";
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new image
    if (isset($_POST['add_image'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $display_order = (int)$_POST['display_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if display_order already exists
        $check_order_query = "SELECT COUNT(*) as count FROM carousel_images WHERE display_order = ?";
        $check_order_stmt = mysqli_prepare($conn, $check_order_query);
        mysqli_stmt_bind_param($check_order_stmt, "i", $display_order);
        mysqli_stmt_execute($check_order_stmt);
        $check_order_result = mysqli_stmt_get_result($check_order_stmt);
        $check_order_data = mysqli_fetch_assoc($check_order_result);
        
        if ($check_order_data['count'] > 0) {
            $error_message = "Display order " . $display_order . " is already in use. Please choose a different order number.";
        } else {
            // Read image metadata from hidden fields (uploaded via AJAX)
            $image_url = $_POST['carousel_image_url'] ?? '';
            $public_id = $_POST['carousel_image_public_id'] ?? '';
            
            if (empty($image_url) || empty($public_id)) {
                $error_message = "Please upload an image first.";
            } else {
                // Insert carousel image with Cloudinary metadata
                $insert_query = "INSERT INTO carousel_images 
                                (image_url, cloud_url, cloud_public_id, cloud_provider, 
                                 title, display_order, is_active, created_by) 
                                VALUES (?, ?, ?, 'cloudinary', ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssssiis", 
                    $image_url, $image_url, $public_id, 
                    $title, $display_order, $is_active, $_SESSION['admin_id']);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    // Remove from temp_uploaded_images table
                    $delete_temp = "DELETE FROM temp_uploaded_images WHERE public_id = ?";
                    $delete_stmt = mysqli_prepare($conn, $delete_temp);
                    mysqli_stmt_bind_param($delete_stmt, "s", $public_id);
                    mysqli_stmt_execute($delete_stmt);
                    
                    $success_message = "Carousel image added successfully!";
                    $new_image_id = mysqli_insert_id($conn);
                    logAdminActivity($conn, 'CREATE', "Added new carousel image: $title", 'carousel_images', $new_image_id);
                } else {
                    $error_message = "Error adding image: " . mysqli_error($conn);
                }
            }
        }
    }
    
    // Update image
    if (isset($_POST['update_image'])) {
        $image_id = (int)$_POST['image_id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $display_order = (int)$_POST['display_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if display_order already exists for other images
        $check_order_query = "SELECT COUNT(*) as count FROM carousel_images WHERE display_order = ? AND id != ?";
        $check_order_stmt = mysqli_prepare($conn, $check_order_query);
        mysqli_stmt_bind_param($check_order_stmt, "ii", $display_order, $image_id);
        mysqli_stmt_execute($check_order_stmt);
        $check_order_result = mysqli_stmt_get_result($check_order_stmt);
        $check_order_data = mysqli_fetch_assoc($check_order_result);
        
        if ($check_order_data['count'] > 0) {
            $error_message = "Display order " . $display_order . " is already in use. Please choose a different order number.";
        } else {
            // Check if new image is uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = __DIR__ . '/../backend/assets/images/carousel/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Get old image to delete it
                $get_old_image_query = "SELECT image_url FROM carousel_images WHERE id = ?";
                $get_old_image_stmt = mysqli_prepare($conn, $get_old_image_query);
                mysqli_stmt_bind_param($get_old_image_stmt, "i", $image_id);
                mysqli_stmt_execute($get_old_image_stmt);
                $old_image_result = mysqli_stmt_get_result($get_old_image_stmt);
                $old_image_data = mysqli_fetch_assoc($old_image_result);
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'images/carousel/' . $file_name;
                    
                    // Delete old image file
                    if ($old_image_data && $old_image_data['image_url']) {
                        $old_image_path = __DIR__ . '/../backend/assets/' . $old_image_data['image_url'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    
                    // Update with new image
                    $update_query = "UPDATE carousel_images SET image_url = ?, title = ?, display_order = ?, 
                                    is_active = ?, updated_by = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ssiisi", $image_url, $title, $display_order, $is_active, $_SESSION['admin_id'], $image_id);
                } else {
                    $error_message = "Failed to upload image.";
                }
            } else {
                // Update without changing image
                $update_query = "UPDATE carousel_images SET title = ?, display_order = ?, is_active = ?, 
                                updated_by = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "siisi", $title, $display_order, $is_active, $_SESSION['admin_id'], $image_id);
            }
            
            if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
                $success_message = "Image updated successfully!";
                logAdminActivity($conn, 'UPDATE', "Updated carousel image: $title", 'carousel_images', $image_id);
            } else {
                $error_message = "Error updating image: " . mysqli_error($conn);
            }
        }
    }
    
    // Toggle image status
    if (isset($_POST['toggle_status'])) {
        $image_id = (int)$_POST['image_id'];
        $new_status = (int)$_POST['new_status'];
        
        // Get image title for logging
        $get_title_query = "SELECT title FROM carousel_images WHERE id = ?";
        $get_title_stmt = mysqli_prepare($conn, $get_title_query);
        mysqli_stmt_bind_param($get_title_stmt, "i", $image_id);
        mysqli_stmt_execute($get_title_stmt);
        $title_result = mysqli_stmt_get_result($get_title_stmt);
        $title_data = mysqli_fetch_assoc($title_result);
        
        $update_query = "UPDATE carousel_images SET is_active = ?, updated_by = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "isi", $new_status, $_SESSION['admin_id'], $image_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $status_text = $new_status ? "activated" : "deactivated";
            $success_message = "Image $status_text successfully!";
            logAdminActivity($conn, 'UPDATE', "Carousel image $status_text: {$title_data['title']}", 'carousel_images', $image_id);
        } else {
            $error_message = "Error updating image status: " . mysqli_error($conn);
        }
    }
    
    // Delete image
    if (isset($_POST['delete_image'])) {
        $image_id = (int)$_POST['image_id'];
        
        // Get the image URL and title for logging
        $get_image_query = "SELECT image_url, title FROM carousel_images WHERE id = ?";
        $get_image_stmt = mysqli_prepare($conn, $get_image_query);
        mysqli_stmt_bind_param($get_image_stmt, "i", $image_id);
        mysqli_stmt_execute($get_image_stmt);
        $image_result = mysqli_stmt_get_result($get_image_stmt);
        
        if ($image_data = mysqli_fetch_assoc($image_result)) {
            // Correct path for deletion
            $image_path = __DIR__ . '/../backend/assets/' . $image_data['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the image file
            }
        }
        
        $delete_query = "DELETE FROM carousel_images WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $image_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success_message = "Image deleted successfully!";
            logAdminActivity($conn, 'DELETE', "Deleted carousel image: {$image_data['title']}", 'carousel_images', $image_id);
        } else {
            $error_message = "Error deleting image: " . mysqli_error($conn);
        }
    }
}

// Get all carousel images - prioritize Cloudinary URLs
$images_query = "SELECT id, COALESCE(cloud_url, image_url) as image_url, title, display_order, is_active, created_at, updated_at FROM carousel_images ORDER BY display_order ASC";
$images_result = mysqli_query($conn, $images_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Image - Neo Cafe Admin</title>
    <link rel="stylesheet" href="manage-carousel.css">
    <link rel="stylesheet" href="css/carousel-image-ajax.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

<main class="admin-main">
    <div class="admin-container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Add New Image Form -->
        <section class="admin-section">
            <h2>Add New Carousel Image</h2>
            <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
                <!-- CSRF Token -->
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- Hidden fields for image metadata (populated by AJAX) -->
                <input type="hidden" id="carousel_image_url" name="carousel_image_url">
                <input type="hidden" id="carousel_image_public_id" name="carousel_image_public_id">
                
                <div class="form-group">
                    <label for="title">Image Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group carousel-image-upload">
                    <label for="carouselImageInput" class="carousel-upload-btn">
                        <i class="fas fa-cloud-upload-alt"></i> Click to Upload Image
                    </label>
                    <input type="file" 
                           id="carouselImageInput" 
                           name="image" 
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           style="display: none;">
                    <span class="image-size-info">Recommended: 1920x1080px | Max: 5MB | Formats: JPEG, PNG, GIF, WebP</span>
                    
                    <!-- Image Preview Container -->
                    <div id="carouselPreviewContainer" class="carousel-preview-container"></div>
                    
                    <!-- Loading Indicator -->
                    <div id="carouselLoadingIndicator" class="loading-indicator" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Uploading image...
                    </div>
                    
                    <!-- Success Indicator -->
                    <div id="carouselSuccessIndicator" class="success-indicator" style="display: none;">
                        <i class="fas fa-check-circle"></i> Upload successful!
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order (Must be unique)</label>
                    <input type="number" id="display_order" name="display_order" min="1" value="<?php echo getNextAvailableOrder($conn); ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <label for="is_active">Show in carousel</label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_image" class="btn btn-primary">Add Image</button>
                </div>
            </form>
        </section>
        
        <!-- Existing Images -->
        <section class="admin-section">
            <h2>Existing Carousel Images</h2>
            
            <?php if (isset($images_result) && mysqli_num_rows($images_result) > 0): ?>
                <div class="slides-grid">
                    <?php while ($image = mysqli_fetch_assoc($images_result)): ?>
                        <div class="slide-card <?php echo $image['is_active'] ? 'active' : 'inactive'; ?>">
                            <div class="slide-image">
                                <?php
                                // Handle both Cloudinary URLs and local paths
                                $image_url = $image['image_url'];
                                if (strpos($image_url, 'http://') === 0 || strpos($image_url, 'https://') === 0) {
                                    $image_path = $image_url; // Cloudinary URL
                                } else {
                                    $image_path = '/assets/' . $image_url; // Local path
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
                            </div>
                            
                            <div class="slide-details">
                                <h3><?php echo htmlspecialchars($image['title']); ?></h3>
                                <div class="slide-meta">
                                    <span>Order: <?php echo $image['display_order']; ?></span>
                                    <span class="status-badge <?php echo $image['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $image['is_active'] ? 'Status: Active' : 'Status: Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="slide-actions">
                                <div class="action-buttons">
                                    <div class= "edit-act">
                                        <button class="btn btn-edit" onclick="editImage(<?php echo $image['id']; ?>)">Edit</button>
                                    </div>
                                    <!-- Toggle Status Button -->
                                    <form action="" method="POST" class="toggle-form">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $image['is_active'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_status" class="btn <?php echo $image['is_active'] ? 'btn-deactivate' : 'btn-activate'; ?>">
                                            <?php echo $image['is_active'] ? 'Hide' : 'Show'; ?>
                                        </button>
                                    </form>

                                    <form action="" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                        <button type="submit" name="delete_image" class="btn btn-delete">Delete</button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Edit Form (Hidden by default) -->
                            <div id="edit-form-<?php echo $image['id']; ?>" class="edit-form" style="display: none;">
                                <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="edit-title-<?php echo $image['id']; ?>">Image Title</label>
                                        <input type="text" id="edit-title-<?php echo $image['id']; ?>" name="title" value="<?php echo htmlspecialchars($image['title']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-image-<?php echo $image['id']; ?>">Image (Leave empty to keep current)</label>
                                        <input type="file" id="edit-image-<?php echo $image['id']; ?>" name="image" accept="image/*">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="edit-display-order-<?php echo $image['id']; ?>">Display Order (Must be unique)</label>
                                        <input type="number" id="edit-display-order-<?php echo $image['id']; ?>" name="display_order" min="1" value="<?php echo $image['display_order']; ?>">
                                    </div>
                                    
                                    <div class="form-group checkbox-group">
                                        <input type="checkbox" id="edit-is-active-<?php echo $image['id']; ?>" name="is_active" <?php echo $image['is_active'] ? 'checked' : ''; ?>>
                                        <label for="edit-is-active-<?php echo $image['id']; ?>">Active (Show in carousel)</label>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_image" class="btn btn-primary">Update Image</button>
                                        <button type="button" class="btn btn-secondary" onclick="cancelEdit(<?php echo $image['id']; ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-slides">
                    <p>No carousel images found. Add your first image above.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
        require_once __DIR__ . "/../admin-includes/footer/admin-footer.php";
    ?>
</main>
<script src="js/carousel-image-ajax.js"></script>
<script>
function editImage(imageId) {
    document.getElementById('edit-form-' + imageId).style.display = 'block';
}

function cancelEdit(imageId) {
    document.getElementById('edit-form-' + imageId).style.display = 'none';
}
</script>
</body>
</html>

