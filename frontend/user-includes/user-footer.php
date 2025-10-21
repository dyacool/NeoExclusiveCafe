<?php
// Connect to online database
$conn = new mysqli("mysql-neoexclusivecafe.alwaysdata.net", "429123", "NeoCafe123", "neoexclusivecafe_crud");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch footer settings
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
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/frontend/user-includes/footer.css">
</head>
<body>
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

    <!-- Footer Links and Copyright Section (Outside the main footer for better layout) -->
    <div class="footer-bottom">
        <div class="footer-links">
        <a href="/frontend/pages/about/about-page.php">About Us</a>
        <a href="/frontend/pages/terms/terms-and-condition.php">Terms & Conditions</a>
        <a href="/frontend/pages/privacy-policy/privacy-policy.php">Privacy Policy</a>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Neo Cafe. All rights reserved.</p>
        </div>
    </div>
</body>
</html>