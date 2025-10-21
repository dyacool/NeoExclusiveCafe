<?php
<<<<<<< HEAD
// Start session
session_start();

// Include database connection
require_once "../../user-includes/database.php";
=======
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../../backend/pages/admin-includes/database.php";

// Database connection is already established in database.php as $conn
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1

// Fetch about content
$sql = "SELECT * FROM about_content WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $about = $result->fetch_assoc();
} else {
    // Default content if none found
    $about = [
        'title' => 'About Neo Exclusive Cafe',
<<<<<<< HEAD
        'about_text' => '<h2>Welcome to Neo Exclusive Cafe</h2>
<p>Our story begins with a passion for quality coffee and exceptional service that has been brewing since our establishment.</p>

<h3>Our Mission</h3>
<p>At Neo Exclusive Cafe, we believe that every cup tells a story. Our mission is to create memorable experiences through carefully crafted beverages, delicious food, and warm hospitality.</p>

<h3>Quality First</h3>
<p>We source our coffee beans from the finest farms around the world, ensuring that every cup meets our high standards of excellence. Our skilled baristas are trained to bring out the best in every blend.</p>

<h3>Community Focus</h3>
<p>More than just a cafe, we are a gathering place for the community. Whether you\'re catching up with friends, working on your next big project, or simply enjoying a quiet moment, Neo Exclusive Cafe is your home away from home.</p>',
        'image_path' => '/backend/assets/images/cafe-default.jpg',
=======
        'about_text' => 'Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality coffee and exceptional service.

Our Mission
At Neo Exclusive Cafe, we believe that every cup tells a story. Our mission is to create memorable experiences through carefully crafted beverages, delicious food, and warm hospitality.

Quality First
We source our coffee beans from the finest farms around the world, ensuring that every cup meets our high standards of excellence. Our skilled baristas are trained to bring out the best in every blend.

Community Focus
More than just a cafe, we are a gathering place for the community. Whether you\'re catching up with friends, working on your next big project, or simply enjoying a quiet moment, Neo Exclusive Cafe is your home away from home.',
        'image_path' => '/images/cafe-default.jpg',
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

$conn->close();
<<<<<<< HEAD
=======

>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($about['title']); ?> - Neo Exclusive Cafe</title>
<<<<<<< HEAD
    <link rel="stylesheet" href="about-page.css">
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
    
    <!-- Include user navigation -->
    <?php include "../../user-includes/user-header.php"; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>

    <div class="about-container">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="../home/user-dashboard.php">Home</a>
                <span class="separator">></span>
                <span class="current">About Us</span>
            </nav>

            <!-- Header -->
            <header class="about-header">
                <h1><?php echo htmlspecialchars($about['title']); ?></h1>
                <?php if (isset($about['last_updated'])): ?>
                    <p class="last-updated">
                        Last updated: <?php echo date('F j, Y', strtotime($about['last_updated'])); ?>
                    </p>
                <?php endif; ?>
            </header>

            <!-- Main Content -->
            <main class="about-content">
                <?php if (!empty($about['image_path'])): ?>
                    <div class="about-image">
                        <img src="<?php echo htmlspecialchars($about['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($about['title']); ?>"
                             loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="content-wrapper">
                    <?php echo $about['about_text']; ?>
                </div>

                <!-- Action Buttons -->
                <div class="about-actions">
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
                    <h3>Visit Us Today</h3>
                    <p>Experience the Neo Exclusive difference for yourself. We look forward to serving you!</p>
                    <ul>
                        <li><strong>Email:</strong> info@neoexclusivecafe.com</li>
                        <li><strong>Phone:</strong> +1 (555) 123-4567</li>
                        <li><strong>Address:</strong> 123 Coffee Street, Café District, CD 12345</li>
                        <li><strong>Hours:</strong> Mon-Sun: 6:00 AM - 10:00 PM</li>
                    </ul>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>

    <script src="about-page.js"></script>
</body>
</html>
=======
    <link rel="stylesheet" href="/frontend/pages/about/about-page.css">
    <link rel="icon" type="image/x-icon" href="../../../assets/images/favicon.ico">
    
    <!-- Include user navigation -->
</head>
<body>
        
    <!-- Navigation -->
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

    <div class="about-container">
        <div class="container">

            <!-- Header -->
            <header class="about-header">
                <h1><?php echo htmlspecialchars($about['title']); ?></h1>
                <?php if (isset($about['last_updated'])): ?>
                    <p class="last-updated">
                        Last updated: <?php echo date('F j, Y', strtotime($about['last_updated'])); ?>
                    </p>
                <?php endif; ?>
            </header>

            <!-- Content -->
            <section class="about-content">
                <div class="content-wrapper">
                    <?php if (!empty($about['image_path'])): ?>
                        <div class="about-image">
                            <?php
                            // Adjust image path for frontend access
                            $image_src = $about['image_path'];
                            // If the path starts with /images/, make it relative to the frontend
                            if (strpos($image_src, '/images/') === 0) {
                                $image_src = '../../../' . ltrim($image_src, '/');
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="About Neo Exclusive Cafe">
                        </div>
                    <?php endif; ?>
                    <div class="about-text">
                        <?php echo $about['about_text']; ?>
                    </div>

                </div>
            </section>
        </div>
    </div>
    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>
>>>>>>> 0f7cc562e1bba1325f82baf13331c7a7469acfd1

    <script src="about-page.js"></script>
</body>
</html>