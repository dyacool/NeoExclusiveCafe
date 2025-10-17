<?php
// Start session
session_start();

// Include database connection
require_once "../../../backend/pages/admin-includes/database.php";

// Fetch privacy policy
$sql = "SELECT * FROM privacy_policy WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $privacy = $result->fetch_assoc();
} else {
    // Default content if none found
    $privacy = [
        'title' => 'Privacy Policy',
        'content' => '<h2>Privacy Policy for Neo Exclusive Cafe</h2>
<p>At Neo Exclusive Cafe, we are committed to protecting your privacy and ensuring the security of your personal information.</p>

<h3>1. Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, make a reservation, or contact us.</p>
<ul>
<li>Personal identification information (Name, email address, phone number)</li>
<li>Payment information (processed securely through third-party providers)</li>
<li>Preferences and dietary requirements</li>
</ul>

<h3>2. How We Use Your Information</h3>
<p>We use the information we collect to:</p>
<ul>
<li>Process your orders and reservations</li>
<li>Communicate with you about your account or transactions</li>
<li>Improve our services and customer experience</li>
<li>Send you promotional communications (with your consent)</li>
</ul>

<h3>3. Information Sharing</h3>
<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

<h3>4. Data Security</h3>
<p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h3>5. Your Rights</h3>
<p>You have the right to:</p>
<ul>
<li>Access your personal information</li>
<li>Correct inaccurate information</li>
<li>Request deletion of your information</li>
<li>Opt-out of marketing communications</li>
</ul>

<h3>6. Contact Us</h3>
<p>If you have any questions about this Privacy Policy, please contact us at privacy@neoexclusivecafe.com</p>',
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
    <title><?php echo htmlspecialchars($privacy['title']); ?> - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="privacy-policy.css">
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>

    <div class="privacy-container">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="../home/user-dashboard.php">Home</a>
                <span class="separator">></span>
                <span class="current">Privacy Policy</span>
            </nav>

            <!-- Header -->
            <header class="privacy-header">
                <h1><?php echo htmlspecialchars($privacy['title']); ?></h1>
                <?php if (isset($privacy['last_updated'])): ?>
                    <p class="last-updated">
                        Last updated: <?php echo date('F j, Y', strtotime($privacy['last_updated'])); ?>
                    </p>
                <?php endif; ?>
            </header>

            <!-- Content -->
            <main class="privacy-content">
                <div class="content-wrapper">
                    <?php echo $privacy['content']; ?>
                </div>

                <!-- Action Buttons -->
                <div class="privacy-actions">
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

    <script src="privacy-policy.js"></script>
</body>
</html>
