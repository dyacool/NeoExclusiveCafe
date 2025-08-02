<?php
$page_title = "Manage Carousel Images";
$additional_css = [
    "/backend/pages/user-page-content/manage-carousel.css"
];

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";

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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

    // Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /login/admin/admin-login.php?error=unauthorized");
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
            // Handle image upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = __DIR__ . '/../../../assets/images/carousel/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Store path relative to root without leading slash
                    $image_url = 'assets/images/carousel/' . $file_name;
                } else {
                    $error_message = "Failed to upload image.";
                }
            } else {
                $error_message = "Please select an image.";
            }
            
            if (!isset($error_message)) {
                $insert_query = "INSERT INTO carousel_images (image_url, title, display_order, is_active, created_by) 
                                VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssiis", $image_url, $title, $display_order, $is_active, $_SESSION['user_id']);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_message = "Image added successfully!";
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
                $upload_dir = __DIR__ . '/../../../assets/images/carousel/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'assets/images/carousel/' . $file_name;
                    
                    // Update with new image
                    $update_query = "UPDATE carousel_images SET image_url = ?, title = ?, display_order = ?, 
                                    is_active = ?, updated_by = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ssiisi", $image_url, $title, $display_order, $is_active, $_SESSION['user_id'], $image_id);
                } else {
                    $error_message = "Failed to upload image.";
                }
            } else {
                // Update without changing image
                $update_query = "UPDATE carousel_images SET title = ?, display_order = ?, is_active = ?, 
                                updated_by = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "siisi", $title, $display_order, $is_active, $_SESSION['user_id'], $image_id);
            }
            
            if (isset($update_stmt) && mysqli_stmt_execute($update_stmt)) {
                $success_message = "Image updated successfully!";
            } else {
                $error_message = "Error updating image: " . mysqli_error($conn);
            }
        }
    }
    
    // Toggle image status
    if (isset($_POST['toggle_status'])) {
        $image_id = (int)$_POST['image_id'];
        $new_status = (int)$_POST['new_status'];
        
        $update_query = "UPDATE carousel_images SET is_active = ?, updated_by = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "isi", $new_status, $_SESSION['user_id'], $image_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $status_text = $new_status ? "activated" : "deactivated";
            $success_message = "Image $status_text successfully!";
        } else {
            $error_message = "Error updating image status: " . mysqli_error($conn);
        }
    }
    
    // Delete image
    if (isset($_POST['delete_image'])) {
        $image_id = (int)$_POST['image_id'];
        
        // Get the image URL to delete the file
        $get_image_query = "SELECT image_url FROM carousel_images WHERE id = ?";
        $get_image_stmt = mysqli_prepare($conn, $get_image_query);
        mysqli_stmt_bind_param($get_image_stmt, "i", $image_id);
        mysqli_stmt_execute($get_image_stmt);
        $image_result = mysqli_stmt_get_result($get_image_stmt);
        
        if ($image_data = mysqli_fetch_assoc($image_result)) {
            $image_path = '../../' . $image_data['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the image file
            }
        }
        
        $delete_query = "DELETE FROM carousel_images WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $image_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success_message = "Image deleted successfully!";
        } else {
            $error_message = "Error deleting image: " . mysqli_error($conn);
        }
    }
}

// Get all carousel images
$images_query = "SELECT * FROM carousel_images ORDER BY display_order ASC";
$images_result = mysqli_query($conn, $images_query);
?>

<main class="admin-main">
    <div class="admin-container">
        <h1 class="admin-title">Manage Carousel Images</h1>
    
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
                <div class="form-group">
                    <label for="title">Image Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="image">Image (Recommended: 1920x1080px)</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order (Must be unique)</label>
                    <input type="number" id="display_order" name="display_order" min="1" value="<?php echo getNextAvailableOrder($conn); ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <label for="is_active">Active (Show in carousel)</label>
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
                                <img src="/NeoExclusiveCafe/<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
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

<script>
function editImage(imageId) {
    document.getElementById('edit-form-' + imageId).style.display = 'block';
}

function cancelEdit(imageId) {
    document.getElementById('edit-form-' + imageId).style.display = 'none';
}
</script>

<style>
/* Manage Carousel Styles */
.admin-main {
  width: 100%;
  margin: 0;
  padding: 0;
}

.admin-container {
  width: 100%;
  margin: 0;
  padding: 2rem;
  box-sizing: border-box;
}

.admin-title {
  font-size: 2rem;
  margin-bottom: 1.5rem;
  color: #333;
}

.admin-nav {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.admin-section {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  margin-bottom: 2rem;
}

.admin-section h2 {
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  color: #333;
  border-bottom: 1px solid #eee;
  padding-bottom: 0.5rem;
}

.settings-info {
  margin-bottom: 1.5rem;
  color: #666;
  font-style: italic;
}

/* Form Styles */
.admin-form {
  display: grid;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #555;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group input[type="file"] {
  padding: 0.5rem 0;
}

.checkbox-group {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

/* Alert Styles */
.alert {
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1.5rem;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* Slides Grid */
.slides-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.slide-card {
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}

.slide-card.inactive {
  opacity: 0.7;
}

.slide-image {
  height: 180px;
  overflow: hidden;
}

.slide-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.slide-details {
  padding: 1rem;
}

.slide-details h3 {
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
}

.slide-details p {
  color: #666;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.slide-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
  font-size: 0.85rem;
}

.slide-meta span {
  background: #f5f5f5;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.slide-actions {
  display: flex;
  justify-content: space-between;
  padding: 1rem;
  border-top: 1px solid #eee;
}

.delete-form {
  margin: 0;
}

/* Edit Form */
.edit-form {
  padding: 1rem;
  background: #f9f9f9;
  border-top: 1px solid #eee;
}

/* No Slides Message */
.no-slides {
  text-align: center;
  padding: 2rem;
  background: #f9f9f9;
  border-radius: 8px;
  color: #666;
}

/* Preview Section */
.carousel-preview {
  background-color: rgba(0, 0, 0, 0.7);
  border-radius: 8px;
  padding: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;
}

.preview-content {
  text-align: center;
  color: white;
  max-width: 600px;
}

.preview-title {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.preview-description {
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .admin-container {
    padding: 1rem;
  }

  .slides-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    margin-bottom: 0.5rem;
  }
}
</style>