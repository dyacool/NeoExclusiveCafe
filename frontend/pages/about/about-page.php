<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../php/includes/database.php";

// Database connection
$conn = new mysqli("localhost", "root", "", "crud");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch about content
$sql = "SELECT * FROM about_content WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $about = $result->fetch_assoc();
} else {
    // Default content if none found
    $about = [
        'title' => 'About Neo Exclusive Cafe',
        'about_text' => 'Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality coffee and exceptional service.',
        'image_path' => '/NeoExclusiveCafe/images/cafe-default.jpg'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<<<<<<< Updated upstream
    <title>About Us - Neo Exclusive Cafe</title>
    <?php include '../../php/includes/customer-navigation.php'; ?>
    <link rel="stylesheet" href="../../php/includes/customer-navigation.css">
    <link rel="stylesheet" href="/NeoExclusiveCafe/css/users/about-page.css">
=======
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($about['title']); ?> - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="about-page.css">
    <!-- Include user navigation -->
    <?php include "../../user-includes/user-header.php"; ?>
>>>>>>> Stashed changes
</head>
<body>

    
    <!-- Main Content -->
    <main>
        <div class="main-content">
            <div class="about-container">
                <section class="about-image">
                    <div class="image-container">
                        <img src="<?php echo htmlspecialchars($about['image_path']); ?>" alt="About Neo Exclusive Cafe">
                    </div>
                    <div class="header">
                        <h1><?php echo htmlspecialchars($about['title']); ?></h1>
                    </div>
                </section>

                <section class="about-content">
                <div class="about-box">
                <?php 
                // Process the about_text to add paragraph spacing
                $paragraphs = explode("\n", $about['about_text']);
                foreach($paragraphs as $paragraph) {
                    if(trim($paragraph) !== '') {
                    echo '<p>' . htmlspecialchars($paragraph) . '</p>';
                    }
                }
                ?>
                </div>
<<<<<<< Updated upstream
                </section>
            </div>
=======

                <!-- Action Buttons -->
                <div class="about-actions">
                    <a href="../home/user-dashboard.php" class="btn-back">
                        <svg width="25" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.42-1.41L7.83 13H20v-2z"/>
                        </svg>
                        Back to Home
                    </a>
            </main>
>>>>>>> Stashed changes
        </div>
    </main>
<?php $conn->close(); ?>
<?php include '../../php/includes/user-footer.php'; ?>

</body>
</html>