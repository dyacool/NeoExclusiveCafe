<?php
// Start session for potential authentication
session_start();

// Check if user is logged in as admin (implement your authentication logic)
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Include the navbar
include __DIR__ . "/../admin-includes/navbar/navbar.php";

// Database connection - Updated with correct database name and likely XAMPP default credentials
require_once __DIR__ . "/../admin-includes/database.php";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$success_message = '';
$error_message = '';

// Fetch current settings
$sql = "SELECT * FROM footer_settings WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    // Default settings if none found
    $settings = [
        'address' => '1234 Neo Cafe Street, Neo City',
        'phone' => '+63 123-456-7890',
        'email' => 'contact@neocafe.com',
        'facebook_link' => 'https://www.facebook.com/neocafePH',
        'instagram_link' => 'https://www.instagram.com/neocafeph/',
        'email_link' => 'mailto:hannahzepeda@outlook.com',
        'map_iframe_src' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d23948.00681163127!2d121.08426539847078!3d14.284901776776811!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d9c053d854a7%3A0xfb047daf43ff3a0c!2sNeo%20Cafe!5e1!3m2!1sen!2sph!4v1742737204716!5m2!1sen!2sph'
    ];
    
    // Insert default settings
    $insert_sql = "INSERT INTO footer_settings (id, address, phone, email, facebook_link, instagram_link, email_link, map_iframe_src) 
                  VALUES (1, '{$settings['address']}', '{$settings['phone']}', '{$settings['email']}', 
                  '{$settings['facebook_link']}', '{$settings['instagram_link']}', '{$settings['email_link']}', 
                  '{$settings['map_iframe_src']}')";
    
    if ($conn->query($insert_sql) !== TRUE) {
        $error_message = "Error creating default settings: " . $conn->error;
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $facebook_link = $conn->real_escape_string($_POST['facebook_link']);
    $instagram_link = $conn->real_escape_string($_POST['instagram_link']);
    $email_link = $conn->real_escape_string($_POST['email_link']);
    $map_iframe_src = $conn->real_escape_string($_POST['map_iframe_src']);
    
    // Update settings in database
    $update_sql = "UPDATE footer_settings SET 
                  address = '$address',
                  phone = '$phone',
                  email = '$email',
                  facebook_link = '$facebook_link',
                  instagram_link = '$instagram_link',
                  email_link = '$email_link',
                  map_iframe_src = '$map_iframe_src'
                  WHERE id = 1";
    
    if ($conn->query($update_sql) === TRUE) {
        $success_message = "Footer settings updated successfully!";
        
        // Update local settings variable to reflect changes
        $settings['address'] = $address;
        $settings['phone'] = $phone;
        $settings['email'] = $email;
        $settings['facebook_link'] = $facebook_link;
        $settings['instagram_link'] = $instagram_link;
        $settings['email_link'] = $email_link;
        $settings['map_iframe_src'] = $map_iframe_src;
    } else {
        $error_message = "Error updating settings: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Settings - Neo Cafe Admin</title>
    <link rel="stylesheet" href="/backend/pages/user-page-content/footer-settings.css">
</head>
<body>
    <div class="admin-container">
        <header>
            <h1>Footer Settings</h1>
        </header>

        <main>
            <?php if (!empty($success_message)): ?>
                <div class="alert success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="admin-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-section">
                    <h2>Contact Information</h2>
                    
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" required><?php echo htmlspecialchars($settings['address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($settings['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Social Media Links</h2>
                    
                    <div class="form-group">
                        <label for="facebook_link">Facebook Link:</label>
                        <input type="url" id="facebook_link" name="facebook_link" value="<?php echo htmlspecialchars($settings['facebook_link']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="instagram_link">Instagram Link:</label>
                        <input type="url" id="instagram_link" name="instagram_link" value="<?php echo htmlspecialchars($settings['instagram_link']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email_link">Email Link:</label>
                        <input type="text" id="email_link" name="email_link" value="<?php echo htmlspecialchars($settings['email_link']); ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Map Settings</h2>
                    
                    <div class="form-group">
                        <label for="map_iframe_src">Map Iframe Source:</label>
                        <textarea id="map_iframe_src" name="map_iframe_src" required><?php echo htmlspecialchars($settings['map_iframe_src']); ?></textarea>
                        <p class="help-text">Paste the full iframe src URL from Google Maps embed code.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
            
            <div class="preview-section">
                <h2>Footer Preview</h2>
                <div class="preview-container">
                    <iframe src="/backend/pages/user-page-content/footer-preview.php" frameborder="0" width="100%" height="400"></iframe>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
</body>
</html>