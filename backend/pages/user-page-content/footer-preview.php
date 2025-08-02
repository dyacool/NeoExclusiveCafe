<?php
// Include database connection
require_once __DIR__ . "/../admin-includes/database.php";

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
    <title>Footer Preview</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap");

        * {
          font-family: "Spectral", serif;
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          background-color: #fff;
        }

        footer {
          display: flex;
          background-color: #2e5a39;
          color: #f8f5f0;
          padding: 40px 60px;
          font-size: 0.95em;
          height: auto;
          justify-content: center;
        }

        footer h2 {
          color: #e9d5b3;
          margin-bottom: 20px;
          font-size: 1.5em;
          font-weight: 600;
          position: relative;
          text-align: center;
        }

        footer h2:after {
          content: "";
          position: absolute;
          width: 50px;
          height: 2px;
          background-color: #e9d5b3;
          bottom: -8px;
          left: 50%;
          transform: translateX(-50%);
        }

        .contact-info {
          display: flex;
          flex-direction: column;
          flex: 1;
          padding-right: 40px;
          max-width: 400px;
        }

        .contact-info-container {
          display: flex;
          flex-direction: column;
        }

        .contact-info-item h3 {
          color: #e9d5b3;
          margin-bottom: 5px;
          font-size: 1.1em;
          font-weight: 500;
        }

        .contact-info-item p {
          text-align: left;
          color: #f8f5f0;
          line-height: 1.2;
          margin-bottom: 15px;
        }

        .address-map {
          flex: 1;
          padding-left: 8em;
          border-left: 1px solid rgba(233, 213, 179, 0.3);
          max-width: 450px;
          display: flex;
          flex-direction: column;
          justify-content: center;
          text-align: center;
          vertical-align: middle;
        }

        .map-container {
          margin-top: 10px;
          position: relative;
          width: 100%;
          height: 250px;
          overflow: hidden;
          border-radius: 8px;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .map-container iframe {
          position: absolute;
          top: 0;
          left: 0;
          width: 100% !important;
          height: 100% !important;
          border-radius: 8px;
        }

        .social-links {
          display: flex;
          gap: 15px;
          margin-top: 20px;
        }

        .social-icon {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 36px;
          height: 36px;
          background-color: #e9d5b3;
          border-radius: 50%;
          transition: all 0.3s ease;
        }

        .social-icon:hover {
          background-color: #f8f5f0;
          transform: translateY(-3px);
        }

        .social-icon svg {
          width: 20px;
          height: 20px;
          fill: #2e5a39;
          transition: fill 0.3s ease;
        }

        .social-icon:hover svg {
          fill: #2e5a39;
        }

        /* Footer bottom section with links and copyright */
        .footer-bottom {
          background-color: #fbfbfb;
          padding: 0 60px 20px;
          border-top: 1px solid rgba(233, 213, 179, 0.3);
        }

        /* Footer links section */
        .footer-links {
          display: flex;
          justify-content: center;
          flex-wrap: wrap;
          gap: 30px;
          padding: 15px 0;
        }

        .footer-links a {
          color: #2e5a39;
          text-decoration: none;
          font-size: 0.95em;
          transition: color 0.3s ease;
          position: relative;
        }

        .footer-links a:hover {
          color: #e9d5b3;
        }

        /* Copyright section */
        .copyright {
          text-align: center;
          padding-top: 10px;
          font-size: 0.9em;
          color: #2e5a39;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
          footer {
            flex-direction: column;
            padding: 30px 20px;
          }

          .contact-info {
            padding-right: 0;
            max-width: 100%;
            margin-bottom: 40px;
          }

          .address-map {
            padding-left: 0;
            border-left: none;
            border-top: 1px solid rgba(233, 213, 179, 0.3);
            padding-top: 40px;
            max-width: 100%;
          }

          .footer-bottom {
            padding: 0 20px 20px;
          }

          .footer-links {
            flex-direction: column;
            align-items: center;
            gap: 15px;
          }
        }
    </style>
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
            <a href="about.html">About Us</a>
            <a href="terms.html">Terms & Conditions</a>
            <a href="privacy.html">Privacy Policy</a>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Neo Cafe. All rights reserved.</p>
        </div>
    </div>
</body>
</html>