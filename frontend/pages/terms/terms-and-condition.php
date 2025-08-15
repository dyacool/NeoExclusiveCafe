<?php
// Start session
session_start();

// Include database connection
require_once "../../user-includes/database.php";

// Fetch terms and conditions
$sql = "SELECT * FROM terms_conditions WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $terms = $result->fetch_assoc();
} else {
    // Default content if none found
    $terms = [
        'title' => 'Terms and Conditions',
        'content' => '<h2>Welcome to Neo Exclusive Cafe</h2>
<p>These terms and conditions outline the rules and regulations for the use of Neo Exclusive Cafe\'s services.</p>

<h3>1. Introduction</h3>
<p>By accessing and using our services, you accept and agree to be bound by the terms and provision of this agreement.</p>

<h3>2. Use License</h3>
<p>Permission is granted to temporarily download one copy of the materials on Neo Exclusive Cafe\'s website for personal, non-commercial transitory viewing only.</p>

<h3>3. Disclaimer</h3>
<p>The materials on Neo Exclusive Cafe\'s website are provided on an \'as is\' basis. Neo Exclusive Cafe makes no warranties, expressed or implied.</p>

<h3>4. Limitations</h3>
<p>In no event shall Neo Exclusive Cafe or its suppliers be liable for any damages arising out of the use or inability to use the materials on our website.</p>

<h3>5. Privacy Policy</h3>
<p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of our services.</p>

<h3>6. Contact Information</h3>
<p>If you have any questions about these Terms and Conditions, please contact us at info@neoexclusivecafe.com</p>',
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($terms['title']); ?> - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="terms-and-condition.css">
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
    
    <!-- Include user navigation -->
    <?php include "../../user-includes/user-header.php"; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>

    <div class="terms-container">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="../home/user-dashboard.php">Home</a>
                <span class="separator">></span>
                <span class="current">Terms and Conditions</span>
            </nav>

            <!-- Header -->
            <header class="terms-header">
                <h1><?php echo htmlspecialchars($terms['title']); ?></h1>
                <?php if (isset($terms['last_updated'])): ?>
                    <p class="last-updated">
                        Last updated: <?php echo date('F j, Y', strtotime($terms['last_updated'])); ?>
                    </p>
                <?php endif; ?>
            </header>

            <!-- Content -->
            <main class="terms-content">
                <div class="content-wrapper">
                    <?php echo $terms['content']; ?>
                </div>

                <!-- Action Buttons -->
                <div class="terms-actions">
                    <button onclick="window.print()" class="btn-print">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                        </svg>
                        Print
                    </button>
                    <button onclick="copyToClipboard()" class="btn-copy">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                        </svg>
                        Copy Link
                    </button>
                    <a href="../home/user-dashboard.php" class="btn-back">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.42-1.41L7.83 13H20v-2z"/>
                        </svg>
                        Back to Home
                    </a>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <h3>Need Help?</h3>
                    <p>If you have any questions about these terms and conditions, please contact us:</p>
                    <ul>
                        <li><strong>Email:</strong> info@neoexclusivecafe.com</li>
                        <li><strong>Phone:</strong> +1 (555) 123-4567</li>
                        <li><strong>Address:</strong> 123 Coffee Street, Café District, CD 12345</li>
                    </ul>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>

    <script src="terms-and-condition.js"></script>
</body>
</html>