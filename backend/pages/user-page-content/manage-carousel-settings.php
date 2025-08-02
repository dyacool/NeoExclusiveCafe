<?php
$page_title = "Carousel Settings";
$additional_css = [
    "/backend/pages/user-page-content/manage-carousel.css"
];

require_once __DIR__ . "/../admin-includes/config.php";
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";

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
        mysqli_stmt_bind_param($default_stmt, "s", $_SESSION['user_id']);
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
        mysqli_stmt_bind_param($update_stmt, "sssss", $title, $description, $button_text, $button_link, $_SESSION['user_id']);
    } else {
        // Insert new settings
        $insert_query = "INSERT INTO carousel_settings (title, description, button_text, button_link, created_by) 
                        VALUES (?, ?, ?, ?, ?)";
        $update_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($update_stmt, "sssss", $title, $description, $button_text, $button_link, $_SESSION['user_id']);
    }
    
    if (mysqli_stmt_execute($update_stmt)) {
        $success_message = "Carousel settings updated successfully!";
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

<main class="admin-main">
    <div class="admin-container">
        <h1 class="admin-title">Carousel Settings</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Carousel Settings Form -->
        <section class="admin-section">
            <h2>Edit Carousel Text & Button</h2>
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

input[type="text"]:focus,
input[type="number"]:focus,
textarea:focus {
  border-color: #2e5a39;
  outline: none;
  box-shadow: 0 0 0 2px rgba(46, 90, 57, 0.1);
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

/* Button Styles */
.btn {
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background-color: #f0f0f0;
  color: #333;
}

.btn-primary:hover {
  background-color: #e0e0e0;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;

  background-color: #2e5a39;
  color: #fff;
}

.btn-secondary:hover {
  background-color: #1d3b25;
}

.btn-save:hover {
  background-color: #1d3b25;
}

.btn-save {
  padding: 0.75rem 1.5rem;

  background-color: #2e5a39;
  color: #fff;
}

.btn-tertiary {
  padding: 0.75rem 1.5rem;
  background-color: #f0f0f0;
  color: #333;
}

.btn-tertiary:hover {
  background-color: #e0e0e0;
}

.btn-edit {
  padding: 0.75rem 1.5rem;

  background-color: #4a90e2;
  color: white;
}

.btn-edit:hover {
  background-color: #3a7bc8;
}

.btn-delete {
  padding: 0.75rem 1.5rem;

  background-color: #e74c3c;
  color: white;
}

.btn-delete:hover {
  background-color: #c0392b;
}

.btn-activate {
  padding: 0.75rem 1.5rem;
  background-color: #27ae60;
  color: white;
}

.btn-activate:hover {
  background-color: #219653;
}

.btn-deactivate {
  padding: 0.75rem 0.95rem;
  background-color: #f39c12;
  color: white;
}

.btn-deactivate:hover {
  background-color: #d35400;
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
  position: relative;
}

.slide-card.inactive::before {
  content: "Hidden";
  position: absolute;
  top: 10px;
  right: 10px;
  background-color: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  z-index: 2;
}

.slide-card.inactive .slide-image {
  filter: grayscale(0.7);
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

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.toggle-form {
  margin: 0;
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

.preview-button {
  display: inline-block;
  background-color: #1d3b25;
  color: white;
  padding: 0.75rem 2rem;
  font-size: 1.1rem;
  font-weight: 600;
  border-radius: 4px;
  text-decoration: none;
}

.preview-button:hover {
  background-color: #2e5a39;
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

  .action-buttons {
    flex-direction: column;
    width: 100%;
  }
}

/* Status badges */
.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
}

.status-active {
  background-color: #d4edda;
  color: #155724;
}

.status-inactive {
  padding: 0.25rem 0.5rem;
  background-color: #f8d7da;
  color: #721c24;
}

</style>
