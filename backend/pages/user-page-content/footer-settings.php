<?php
// Use admin-auth for authentication
require_once __DIR__ . '/../../login/admin/admin-auth.php';

// Include the navbar
include __DIR__ . "/../admin-includes/navbar/navbar.php";

// Database connection
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/activity-logger.php";

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
        
        // Log the activity
        logAdminActivity($conn, 'UPDATE', "Updated footer settings", 'footer_settings', 1);
        
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
    <link rel="stylesheet" href="footer-settings.css">
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/breadcrumbs/admin-breadcrumb.php"; ?>

    <div class="admin-main">
        <div class="admin-container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-content">
                    <div class="page-title-section">
                        <p class="page-subtitle">Manage your website's footer content, contact information, and social media links</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Contact Information Section -->
            <div class="admin-section">
                <h2>Contact Information</h2>
                <p class="settings-info">Configure the contact details that will be displayed in your website's footer.</p>
                
                <form method="post" class="admin-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-textarea" rows="3" required><?php echo htmlspecialchars($settings['address']); ?></textarea>
                        <div class="form-help">Enter your business address as it should appear on the website</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($settings['phone']); ?>" required>
                            <div class="form-help">Format: +63 123-456-7890</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($settings['email']); ?>" required>
                            <div class="form-help">Contact email for customers</div>
                        </div>
                    </div>
            </div>

            <!-- Social Media Section -->
            <div class="admin-section">
                <h2>Social Media Links</h2>
                <p class="settings-info">Add your social media profiles to be displayed in the footer.</p>
                
                    <div class="form-group">
                        <label for="facebook_link">Facebook Page URL</label>
                        <input type="url" id="facebook_link" name="facebook_link" class="form-input" value="<?php echo htmlspecialchars($settings['facebook_link']); ?>" placeholder="https://www.facebook.com/yourpage">
                        <div class="form-help">Link to your Facebook business page</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="instagram_link">Instagram Profile URL</label>
                        <input type="url" id="instagram_link" name="instagram_link" class="form-input" value="<?php echo htmlspecialchars($settings['instagram_link']); ?>" placeholder="https://www.instagram.com/yourprofile">
                        <div class="form-help">Link to your Instagram account</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_link">Email Contact Link</label>
                        <input type="text" id="email_link" name="email_link" class="form-input" value="<?php echo htmlspecialchars($settings['email_link']); ?>" placeholder="mailto:contact@yoursite.com">
                        <div class="form-help">Email link for direct contact (mailto: format)</div>
                    </div>
            </div>

            <!-- Map Settings Section -->
            <div class="admin-section">
                <h2>Location Map</h2>
                <p class="settings-info">Configure the Google Maps embed to show your business location.</p>
                
                    <div class="form-group">
                        <label for="map_iframe_src">Google Maps Embed URL</label>
                        <textarea id="map_iframe_src" name="map_iframe_src" class="form-textarea" rows="4" required><?php echo htmlspecialchars($settings['map_iframe_src']); ?></textarea>
                        <div class="form-help">
                            <strong>How to get the embed URL:</strong><br>
                            1. Go to Google Maps and search for your location<br>
                            2. Click "Share" → "Embed a map"<br>
                            3. Copy the src URL from the iframe code (starting with https://www.google.com/maps/embed...)
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Preview Section -->
            <div class="admin-section">
                <h2>Footer Preview</h2>
                <p class="settings-info">Live preview of how your footer will appear on the website.</p>
                
                <div class="preview-container">
                    <div id="footer-preview-content">
                        <!-- Initial preview will load here -->
                        <div class="footer-preview-container">
                            <footer>
                                <div class="contact-info">
                                    <h2>Contact Us</h2>
                                    <div class="contact-info-container">
                                        <div class="contact-info-item">
                                            <h3>Address</h3>
                                            <p><?php echo htmlspecialchars($settings['address']); ?></p>
                                        </div>
                                        <div class="contact-info-item">
                                            <h3>Phone</h3>
                                            <p><?php echo htmlspecialchars($settings['phone']); ?></p>
                                        </div>
                                        <div class="contact-info-item">
                                            <h3>Email</h3>
                                            <p><?php echo htmlspecialchars($settings['email']); ?></p>
                                        </div>
                                        
                                        <div class="social-links">
                                            <a href="<?php echo htmlspecialchars($settings['facebook_link']); ?>" target="_blank" class="social-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                                </svg>
                                            </a>
                                            <a href="<?php echo htmlspecialchars($settings['instagram_link']); ?>" target="_blank" class="social-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                                </svg>
                                            </a>
                                            <a href="<?php echo htmlspecialchars($settings['email_link']); ?>" class="social-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                                    <path d="M.5 3.5v17h23v-17H.5zm22 1v.3L12 12.25 1.5 4.8V4.5h21zM1.5 19.5v-13L12 14l10.5-7.5v13h-21z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="address-map">
                                    <h2>Find Us</h2>
                                    <div class="map-container">
                                        <iframe
                                            frameborder="0"
                                            scrolling="no"
                                            marginheight="0"
                                            marginwidth="0"
                                            src="<?php echo htmlspecialchars($settings['map_iframe_src']); ?>"
                                            allowfullscreen=""
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade">
                                        </iframe>
                                    </div>
                                </div>
                            </footer>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>