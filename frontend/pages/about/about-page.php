<?php
// Load database connection first (it starts session)
require_once "../../../backend/pages/admin-includes/database.php";

// Database connection is already established in database.php as $conn

// Fetch about content
$sql = "SELECT * FROM about_content WHERE id = 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $about = $result->fetch_assoc();
} else {
    // Default content if none found
    $about = [
        'title' => 'About Neo Exclusive Cafe',
        'about_text' => 'Welcome to Neo Exclusive Cafe. Our story begins with a passion for quality coffee and exceptional service.

Our Mission
At Neo Exclusive Cafe, we believe that every cup tells a story. Our mission is to create memorable experiences through carefully crafted beverages, delicious food, and warm hospitality.

Quality First
We source our coffee beans from the finest farms around the world, ensuring that every cup meets our high standards of excellence. Our skilled baristas are trained to bring out the best in every blend.

Community Focus
More than just a cafe, we are a gathering place for the community. Whether you\'re catching up with friends, working on your next big project, or simply enjoying a quiet moment, Neo Exclusive Cafe is your home away from home.',
        'image_path' => '/images/cafe-default.jpg',
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

// Don't close the connection here as it may be needed by included components
// The connection will be closed automatically at the end of script execution

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($about['title']); ?> - Neo Exclusive Cafe</title>
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

    <script src="about-page.js"></script>
</body>
</html>