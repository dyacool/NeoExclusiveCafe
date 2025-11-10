<?php
$page_title = "Manage Carousel Images";


require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../../../includes/session-manager.php";
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

// Generate CSRF token if not exists (SessionManager handles session start)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in as admin using SessionManager
if (!SessionManager::isAdminLoggedIn()) {
    header("Location: /backend/login/admin/admin-login.php");
    exit();
}

// Get admin data
$adminData = SessionManager::getAdminData();

// Check if the carousel_images table exists, create it if it doesn't
$table_check_query = "SHOW TABLES LIKE 'carousel_images'";
$table_check_result = mysqli_query($conn, $table_check_query);

if (mysqli_num_rows($table_check_result) == 0) {
    // Table doesn't exist, create it with Cloudinary support
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS carousel_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(255) NOT NULL,
        cloud_url VARCHAR(500),
        cloud_public_id VARCHAR(255),
        cloud_provider VARCHAR(50) DEFAULT 'cloudinary',
        title VARCHAR(255) NOT NULL,
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT,
        INDEX idx_display_order (display_order),
        INDEX idx_is_active (is_active)
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<div class='alert alert-success'>Carousel images table created successfully!</div>";
    } else {
        echo "<div class='alert alert-error'>Error creating table: " . mysqli_error($conn) . "</div>";
    }
} else {
    // Table exists, check if Cloudinary columns exist and add them if needed
    $columns_to_add = [
        'cloud_url' => 'VARCHAR(500)',
        'cloud_public_id' => 'VARCHAR(255)',
        'cloud_provider' => 'VARCHAR(50) DEFAULT \'cloudinary\''
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        $check_column = "SHOW COLUMNS FROM carousel_images LIKE '$column'";
        $column_result = mysqli_query($conn, $check_column);
        
        if (mysqli_num_rows($column_result) == 0) {
            $add_column = "ALTER TABLE carousel_images ADD COLUMN $column $definition";
            mysqli_query($conn, $add_column);
        }
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
                $admin_id = $adminData['id'];
                
                // Insert carousel image with Cloudinary metadata
                $insert_query = "INSERT INTO carousel_images 
                                (image_url, cloud_url, cloud_public_id, cloud_provider, 
                                 title, display_order, is_active, created_by) 
                                VALUES (?, ?, ?, 'cloudinary', ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssssiisi", 
                    $image_url, $image_url, $public_id, 
                    $title, $display_order, $is_active, $admin_id);
                
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
            $admin_id = $adminData['id'];
            
            // Update without changing image
            $update_query = "UPDATE carousel_images SET title = ?, display_order = ?, is_active = ?, 
                            updated_by = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "siiii", $title, $display_order, $is_active, $admin_id, $image_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
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
        
        $admin_id = $adminData['id'];
        
        $update_query = "UPDATE carousel_images SET is_active = ?, updated_by = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "iii", $new_status, $admin_id, $image_id);
        
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
        
        // EMERGENCY DEBUG: Log what we're about to delete
        error_log("DELETE REQUEST: Attempting to delete image ID: " . $image_id);
        error_log("POST data: " . print_r($_POST, true));
        
        // Get the image URL and title for logging
        $get_image_query = "SELECT image_url, title FROM carousel_images WHERE id = ?";
        $get_image_stmt = mysqli_prepare($conn, $get_image_query);
        mysqli_stmt_bind_param($get_image_stmt, "i", $image_id);
        mysqli_stmt_execute($get_image_stmt);
        $image_result = mysqli_stmt_get_result($get_image_stmt);
        
        if ($image_data = mysqli_fetch_assoc($image_result)) {
            error_log("FOUND IMAGE TO DELETE: ID=" . $image_id . ", Title=" . $image_data['title']);
            
            // Correct path for deletion
            $image_path = __DIR__ . '/../backend/assets/' . $image_data['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the image file
                error_log("DELETED FILE: " . $image_path);
            }
        } else {
            error_log("WARNING: No image found with ID " . $image_id);
        }
        
        $delete_query = "DELETE FROM carousel_images WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $image_id);
        
        error_log("EXECUTING DELETE QUERY: " . $delete_query . " with ID=" . $image_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
            error_log("DELETE SUCCESS: Affected rows = " . $affected_rows);
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

// Store images in an array to avoid result set consumption issues
$images_array = [];
if ($images_result) {
    while ($image = mysqli_fetch_assoc($images_result)) {
        $images_array[] = $image;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Carousel Images - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="terms-and-condition-management.css">
    <link rel="stylesheet" href="about-settings.css">
    <link rel="stylesheet" href="manage-carousel.css">
    <link rel="stylesheet" href="css/carousel-image-ajax.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Carousel-specific styles extending the base design */
        .carousel-upload-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .carousel-upload-button-area {
            text-align: center;
            max-width: 500px;
        }
        
        .carousel-image-preview-area {
            border: 2px dashed var(--gray-300);
            border-radius: 12px;
            padding: 2rem;
            background-color: var(--gray-50);
            transition: all 0.2s ease;
            text-align: center;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .carousel-image-preview-area.has-image {
            border-color: var(--green-400);
            background-color: var(--green-50);
            padding: 1rem;
        }
        
        .carousel-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--gray-100);
            color: var(--green-500);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            max-width: 200px;
            white-space: nowrap;
        }
        
        .carousel-upload-btn:hover {
            background-color: var(--gray-300);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(119, 119, 119, 0.3);
        }
        
        .image-size-info {
            display: block;
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
            text-align: left;
        }
        
        .carousel-preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            width: 100%;
        }
        
        .carousel-preview-container img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .preview-placeholder {
            color: var(--gray-400);
            font-size: 0.875rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .preview-placeholder svg {
            width: 48px;
            height: 48px;
            opacity: 0.5;
        }
        
        /* Specific input field width controls */
        #display_order,
        input[id*="edit-display-order"] {
            max-width: 120px;
        }
        
        .loading-indicator,
        .success-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 1rem;
        }
        
        .loading-indicator {
            background-color: var(--blue-50);
            color: var(--blue-600);
            border: 1px solid var(--blue-200);
        }
        
        .success-indicator {
            background-color: var(--green-50);
            color: var(--green-600);
            border: 1px solid var(--green-200);
        }
        
        /* Custom checkbox styling */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            position: relative;
            appearance: none;
            -webkit-appearance: none;
            margin: 0;
        }
        
        .checkbox-group input[type="checkbox"]:checked {
            background-color: var(--green-600);
            border-color: var(--green-600);
        }
        
        .checkbox-group input[type="checkbox"]:checked::after {
            content: "✓";
            position: absolute;
            top: -2px;
            left: 1px;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
            color: var(--gray-700);
        }
        
        /* Grid layout for existing images */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .image-card {
            background-color: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .image-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .image-card.inactive {
            opacity: 0.7;
        }
        
        .image-preview-section {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .image-preview-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.2s ease;
        }
        
        .image-card:hover .image-preview-section img {
            transform: scale(1.05);
        }
        
        .image-details {
            padding: 1.5rem;
        }
        
        .image-details h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
        }
        
        .image-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .meta-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .meta-order {
            background-color: var(--gray-100);
            color: var(--gray-700);
        }
        
        .meta-active {
            background-color: var(--green-100);
            color: var(--green-800);
        }
        
        .meta-inactive {
            background-color: var(--red-50);
            color: var(--red-600);
        }
        
        .image-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .image-actions .btn {
            flex: 1;
            min-width: 80px;
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            justify-content: center;
            height: 36px;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .image-actions form {
            flex: 1;
            margin: 0;
        }
        
        .image-actions form .btn {
            width: 100%;
            height: 36px;
        }
        
        .edit-form {
            display: none;
            padding: 1.5rem;
            background-color: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }
        
        .edit-form.active {
            display: block;
        }
        
        .no-images {
            text-align: center;
            padding: 3rem 2rem;
            background-color: var(--gray-50);
            border: 2px dashed var(--gray-300);
            border-radius: 12px;
            color: var(--gray-500);
        }
        
        .no-images p {
            font-size: 1rem;
            margin: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .image-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .image-actions .btn,
            .image-actions form {
                flex: none;
            }
            
            .image-actions form .btn {
                width: 100%;
            }
            
            .carousel-image-preview-area {
                padding: 1.5rem;
            }
            
            .carousel-preview-container img {
                max-height: 200px;
            }
            
            .carousel-upload-button-area {
                max-width: 100%;
            }
            
            .carousel-upload-btn {
                max-width: 250px;
            }
            
            #display_order,
            input[id*="edit-display-order"] {
                max-width: 150px;
            }
        }
        
        @media (max-width: 480px) {
            .carousel-upload-button-area {
                padding: 0.75rem;
                max-width: 100%;
            }
            
            .carousel-upload-btn {
                max-width: 100%;
            }
            
            .carousel-image-preview-area {
                padding: 1rem;
                min-height: 100px;
            }
            
            .preview-placeholder svg {
                width: 36px;
                height: 36px;
            }
            
            .preview-placeholder span {
                font-size: 0.75rem;
            }
            
            #display_order,
            input[id*="edit-display-order"] {
                max-width: 100px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

    <div class="admin-main">
        <div class="admin-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
                    <p class="page-subtitle">Manage carousel images displayed on your website homepage</p>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Add New Image Form -->
            <div class="admin-section">
                <h2>Add New Carousel Image</h2>
                <p class="settings-info">
                    Add high-quality images to your homepage carousel. Recommended size: 1920x1080px for best results.
                </p>

                <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
                    <!-- CSRF Token -->
                    <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Hidden fields for image metadata (populated by AJAX) -->
                    <input type="hidden" id="carousel_image_url" name="carousel_image_url">
                    <input type="hidden" id="carousel_image_public_id" name="carousel_image_public_id">
                    
                    <div class="form-group">
                        <label for="title">Image Title</label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-input"
                               placeholder="Enter a descriptive title for this image"
                               required>
                        <div class="form-help">This title will be used for accessibility and management purposes</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Carousel Image</label>
                        <div class="carousel-upload-section">
                            <!-- Upload Button Area -->

                            <!-- Image Preview Area -->
                            <div class="carousel-image-preview-area" id="carouselPreviewArea">
                                <div id="carouselPreviewContainer" class="carousel-preview-container">
                                    <div class="preview-placeholder">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span>Image preview will appear here</span>
                                    </div>
                                </div>
                                
                                <!-- Loading Indicator -->
                                <div id="carouselLoadingIndicator" class="loading-indicator" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i> Uploading image...
                                </div>
                                
                                <!-- Success Indicator -->
                                <div id="carouselSuccessIndicator" class="success-indicator" style="display: none;">
                                    <i class="fas fa-check-circle"></i> Upload successful!
                                </div>
                            </div>

                            <div class="carousel-upload-button-area">
                                <label for="carouselImageInput" class="carousel-upload-btn">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose Image File
                                </label>
                                <input type="file" 
                                       id="carouselImageInput" 
                                       name="image" 
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       style="display: none;">
                                <span class="image-size-info">Recommended: 1920x1080px | Max: 10MB | Formats: JPEG, PNG, GIF, WebP</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" 
                               id="display_order" 
                               name="display_order" 
                               class="form-input"
                               min="1" 
                               value="<?php echo getNextAvailableOrder($conn); ?>"
                               required>
                        <div class="form-help">Image must have a unique order number.</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <label for="is_active">Active (Show in carousel)</label>
                        </div>
                        <div class="form-help">Uncheck to hide this image from the carousel without deleting it</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_image" class="btn btn-primary">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Carousel Image
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Existing Images -->
            <div class="admin-section">
                <h2>Existing Carousel Images</h2>
                <p class="settings-info">
                    Manage your current carousel images. You can edit, reorder, activate/deactivate, or delete images.
                </p>
                
                <?php if (!empty($images_array)): ?>
                    <div class="images-grid">
                        <?php foreach ($images_array as $image): ?>
                            <!-- Debug: Display the actual ID being used -->
                            <!-- Image ID: <?php echo $image['id']; ?> -->
                            <div class="image-card <?php echo $image['is_active'] ? 'active' : 'inactive'; ?>" id="image-card-<?php echo $image['id']; ?>"
                                 data-image-id="<?php echo $image['id']; ?>" data-title="<?php echo htmlspecialchars($image['title']); ?>">
                                <div class="image-preview-section">
                                    <?php
                                    // Handle both Cloudinary URLs and local paths
                                    $image_url = $image['image_url'];
                                    if (strpos($image_url, 'http://') === 0 || strpos($image_url, 'https://') === 0) {
                                        $image_path = $image_url; // Cloudinary URL
                                    } else {
                                        $image_path = '/assets/' . $image_url; // Local path
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                         alt="<?php echo htmlspecialchars($image['title']); ?>"
                                         loading="lazy">
                                </div>
                                
                                <div class="image-details">
                                    <h3><?php echo htmlspecialchars($image['title']); ?></h3>
                                    
                                    <div class="image-meta">
                                        <span class="meta-badge meta-order">Order: <?php echo $image['display_order']; ?></span>
                                        <span class="meta-badge <?php echo $image['is_active'] ? 'meta-active' : 'meta-inactive'; ?>">
                                            <?php echo $image['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="image-actions">
                                        <button class="btn btn-secondary edit-btn" data-image-id="<?php echo $image['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        
                                        <form action="" method="POST" style="margin: 0;">
                                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $image['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit" name="toggle_status" class="btn <?php echo $image['is_active'] ? 'btn-secondary' : 'btn-primary'; ?>">
                                                <i class="fas <?php echo $image['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                <?php echo $image['is_active'] ? 'Hide' : 'Show'; ?>
                                            </button>
                                        </form>

                                        <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('⚠️ CRITICAL WARNING ⚠️\n\nYou are about to PERMANENTLY DELETE this image:\n\n\"<?php echo htmlspecialchars($image['title']); ?>\"\n\nThis action CANNOT be undone!\n\nType YES in caps if you are absolutely sure:') && prompt('Type YES to confirm deletion:') === 'YES';">
                                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                            <button type="submit" name="delete_image" class="btn" style="background-color: var(--red-600); color: white;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Edit Form (Hidden by default) -->
                                <div id="edit-form-<?php echo $image['id']; ?>" class="edit-form">
                                    <form action="" method="POST" class="admin-form">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label for="edit-title-<?php echo $image['id']; ?>">Image Title</label>
                                            <input type="text" 
                                                   id="edit-title-<?php echo $image['id']; ?>" 
                                                   name="title" 
                                                   class="form-input"
                                                   value="<?php echo htmlspecialchars($image['title']); ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="edit-display-order-<?php echo $image['id']; ?>">Display Order</label>
                                            <input type="number" 
                                                   id="edit-display-order-<?php echo $image['id']; ?>" 
                                                   name="display_order" 
                                                   class="form-input"
                                                   min="1" 
                                                   value="<?php echo $image['display_order']; ?>"
                                                   required>
                                            <div class="form-help">Must be unique among all carousel images</div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="checkbox-group">
                                                <input type="checkbox" 
                                                       id="edit-is-active-<?php echo $image['id']; ?>" 
                                                       name="is_active" 
                                                       <?php echo $image['is_active'] ? 'checked' : ''; ?>>
                                                <label for="edit-is-active-<?php echo $image['id']; ?>">Active (Show in carousel)</label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" name="update_image" class="btn btn-primary">
                                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Update Image
                                            </button>
                                            <button type="button" class="btn btn-secondary cancel-btn" data-image-id="<?php echo $image['id']; ?>">
                                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-images">
                        <svg class="placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px; margin-bottom: 1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p>No carousel images found. Add your first image above to get started.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Guidelines Section -->
            <div class="admin-section">
                <h2>Image Guidelines</h2>
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>High Quality Images</h3>
                        <p>Use high-resolution images (1920x1080px recommended) for crisp display on all devices. Avoid pixelated or blurry photos.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm0 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Proper Ordering</h3>
                        <p>Use display order to control sequence. Lower numbers appear first. Each image must have a unique order number.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3>Relevant Content</h3>
                        <p>Choose images that represent your cafe, menu items, atmosphere, or special events. Keep content fresh and engaging.</p>
                    </div>
                    <div class="guideline-card">
                        <div class="guideline-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Optimize Performance</h3>
                        <p>Images are automatically optimized for web delivery. Keep file sizes reasonable (under 10MB) for faster loading.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/carousel-image-ajax.js"></script>
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure all edit forms are hidden on page load
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
                form.classList.remove('active');
            });
            
            // Debug: List all available edit forms and their IDs
            console.log('Available edit forms on page load:');
            document.querySelectorAll('.edit-form').forEach(form => {
                console.log(' - Form ID:', form.id);
            });
            
            // Debug: List all image cards and their data
            console.log('Available image cards:');
            document.querySelectorAll('.image-card').forEach(card => {
                console.log(' - Card ID:', card.id, 'Data-image-id:', card.dataset.imageId, 'Title:', card.dataset.title);
            });
            
            // Add event delegation for edit and cancel buttons only (not forms)
            document.addEventListener('click', function(event) {
                // Don't interfere with form submissions
                if (event.target.closest('form')) {
                    return;
                }
                
                if (event.target.closest('.edit-btn')) {
                    event.preventDefault(); // Prevent any default action
                    const button = event.target.closest('.edit-btn');
                    const imageId = button.dataset.imageId;
                    console.log('Edit button clicked for image ID:', imageId);
                    toggleEdit(imageId);
                }
                
                if (event.target.closest('.cancel-btn')) {
                    event.preventDefault(); // Prevent any default action
                    const button = event.target.closest('.cancel-btn');
                    const imageId = button.dataset.imageId;
                    console.log('Cancel button clicked for image ID:', imageId);
                    cancelEdit(imageId);
                }
            });
        });

        // Toggle edit form visibility
        function toggleEdit(imageId) {
            // Convert to string to ensure consistency
            imageId = String(imageId);
            
            console.log('toggleEdit called with ID:', imageId);
            console.log('Looking for element: edit-form-' + imageId);
            
            const editForm = document.getElementById('edit-form-' + imageId);
            if (!editForm) {
                console.error('Edit form not found for image ID:', imageId);
                console.log('Available edit forms:', document.querySelectorAll('.edit-form'));
                return;
            }
            
            console.log('Found edit form:', editForm);
            
            const isCurrentlyVisible = editForm.style.display === 'block' || editForm.classList.contains('active');
            console.log('Is currently visible:', isCurrentlyVisible);
            
            // Hide all edit forms first
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
                form.classList.remove('active');
            });
            
            // Show the clicked form only if it wasn't already visible
            if (!isCurrentlyVisible) {
                editForm.style.display = 'block';
                editForm.classList.add('active');
                console.log('Showing edit form for image:', imageId);
                
                // Scroll the edit form into view
                editForm.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest' 
                });
            } else {
                console.log('Edit form was already visible, hiding it');
            }
        }

        // Cancel edit
        function cancelEdit(imageId) {
            // Convert to string to ensure consistency
            imageId = String(imageId);
            
            const editForm = document.getElementById('edit-form-' + imageId);
            if (editForm) {
                editForm.style.display = 'none';
                editForm.classList.remove('active');
            }
        }

        // Close edit forms when clicking outside (but not on edit buttons)
        document.addEventListener('click', function(event) {
            // Check if the click was outside any edit form and not on a button
            if (!event.target.closest('.edit-form') && 
                !event.target.closest('button') && 
                !event.target.closest('.btn') && 
                !event.target.closest('.edit-btn')) {
                document.querySelectorAll('.edit-form').forEach(form => {
                    form.style.display = 'none';
                    form.classList.remove('active');
                });
            }
        });

        // Handle image preview area state
        function updatePreviewArea(hasImage) {
            const previewArea = document.getElementById('carouselPreviewArea');
            const placeholder = previewArea.querySelector('.preview-placeholder');
            
            if (hasImage) {
                previewArea.classList.add('has-image');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            } else {
                previewArea.classList.remove('has-image');
                if (placeholder) {
                    placeholder.style.display = 'flex';
                }
            }
        }

        // Listen for image uploads to update preview area
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('carouselImageInput');
            const previewContainer = document.getElementById('carouselPreviewContainer');
            
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        updatePreviewArea(true);
                    } else {
                        updatePreviewArea(false);
                    }
                });
            }
            
            // Check if there's already an image in the preview
            const existingImage = previewContainer.querySelector('img');
            updatePreviewArea(!!existingImage);
        });
    </script>
</body>
</html>

