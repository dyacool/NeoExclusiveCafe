<?php
$page_title = "Carousel Settings";


require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Check if the carousel_settings table exists, create it if it doesn't
$table_check_query = "SHOW TABLES LIKE 'carousel_settings'";
$table_check_result = mysqli_query($conn, $table_check_query);

if (mysqli_num_rows($table_check_result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS carousel_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        button_text VARCHAR(100) NOT NULL,
        button_link VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT,
        updated_by INT
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<div class='alert alert-success'>Carousel settings table created successfully!</div>";
        
        // Insert default settings
        $default_insert = "INSERT INTO carousel_settings (title, description, button_text, button_link, created_by) 
                          VALUES ('Welcome to Neo Exclusive Cafe', 'Discover our premium coffee selection and delicious pastries', 
                          'Explore Menu', '/frontend/pages/products/product-dashboard.php', ?)";
        $default_stmt = mysqli_prepare($conn, $default_insert);
        mysqli_stmt_bind_param($default_stmt, "s", $_SESSION['admin_id']);
        mysqli_stmt_execute($default_stmt);
    } else {
        echo "<div class='alert alert-danger'>Error creating table: " . mysqli_error($conn) . "</div>";
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $button_text = mysqli_real_escape_string($conn, $_POST['button_text']);
    $button_link = mysqli_real_escape_string($conn, $_POST['button_link']);
    
    // Check if settings exist
    $check_query = "SELECT COUNT(*) as count FROM carousel_settings";
    $check_result = mysqli_query($conn, $check_query);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['count'] > 0) {
        // Update existing settings
        $update_query = "UPDATE carousel_settings SET title = ?, description = ?, button_text = ?, 
                        button_link = ?, updated_by = ? WHERE id = 1";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssss", $title, $description, $button_text, $button_link, $_SESSION['admin_id']);
    } else {
        // Insert new settings
        $insert_query = "INSERT INTO carousel_settings (title, description, button_text, button_link, created_by) 
                        VALUES (?, ?, ?, ?, ?)";
        $update_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($update_stmt, "sssss", $title, $description, $button_text, $button_link, $_SESSION['admin_id']);
    }
    
    if (mysqli_stmt_execute($update_stmt)) {
        $success_message = "Carousel settings updated successfully!";
        logAdminActivity($conn, 'UPDATE', "Updated carousel settings", 'carousel_settings', 1);
    } else {
        $error_message = "Error updating settings: " . mysqli_error($conn);
    }
}

// Get current settings
$settings_query = "SELECT * FROM carousel_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// If no settings exist, create default values
if (!$settings) {
    $settings = [
        'title' => 'Welcome to Neo Exclusive Cafe',
        'description' => 'Discover our premium coffee selection and delicious pastries',
        'button_text' => 'Explore Menu',
        'button_link' => '/frontend/pages/products/product-dashboard.php'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Setting - Neo Cafe Admin</title>
    <link rel="stylesheet" href="manage-carousel.css">
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
        
        <!-- Carousel Settings Form -->
        <section class="admin-section">
            <p class="settings-info">These settings apply to all carousel slides.</p>
            
            <form action="" method="POST" class="admin-form">
                <div class="form-group">
                    <label for="title">Carousel Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($settings['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($settings['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="button_text">Button Text</label>
                    <input type="text" id="button_text" name="button_text" value="<?php echo htmlspecialchars($settings['button_text']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="button_link">Button Link</label>
                    <input type="text" id="button_link" name="button_link" value="<?php echo htmlspecialchars($settings['button_link']); ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_settings" class="btn btn-save">Save Settings</button>
                </div>
            </form>
        </section>
        
        <!-- Preview Section -->
        <section class="admin-section">
            <h2>Preview</h2>
            <div class="carousel-preview">
                <div class="preview-content">
                    <h3 class="preview-title"><?php echo htmlspecialchars($settings['title']); ?></h3>
                    <p class="preview-description"><?php echo htmlspecialchars($settings['description']); ?></p>
                    <a href="<?php echo htmlspecialchars($settings['button_link']); ?>" class="preview-button">
                        <?php echo htmlspecialchars($settings['button_text']); ?>
                    </a>
                </div>
            </div>
        </section>
    </div>
    <?php
        require_once __DIR__ . "/../admin-includes/footer/admin-footer.php";
    ?>
</main>