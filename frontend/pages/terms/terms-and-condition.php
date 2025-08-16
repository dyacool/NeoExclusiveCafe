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
                    <a href="../home/user-dashboard.php" class="btn-back">
                        <svg width="25" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.42-1.41L7.83 13H20v-2z"/>
                        </svg>
                        Back to Home
                    </a>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>

    <script src="terms-and-condition.js"></script>
</body>
</html>