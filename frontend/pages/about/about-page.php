<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$conn->close();

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

    <!-- Hero Section -->
    <section class="hero">
        <h1>Our Story</h1>
        <p>Where passion for quality bread meets exceptional service, creating moments of comfort and joy in every bite</p>
    </section>

    <!-- Content Section -->
    <div class="content">
        <div class="card-container">
            <div class="image-card">
                <?php
                // Adjust image path for frontend access
                $image_src = !empty($about['image_path']) ? $about['image_path'] : 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800';
                if (strpos($image_src, '/images/') === 0) {
                    $image_src = '../../../' . ltrim($image_src, '/');
                }
                ?>
                <img src="<?php echo htmlspecialchars($image_src); ?>" alt="Neo Exclusive Cafe">
            </div>
            <div class="image-card">
                <img src="https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=800" alt="Artisan Bread">
            </div>
        </div>

        <div class="story-section">
            <h2>Welcome to Neo Exclusive Cafe</h2>
            <div class="about-text-content">
                <?php 
                // Convert the about text to HTML paragraphs
                $paragraphs = explode("\n\n", $about['about_text']);
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph)) {
                        echo '<p>' . nl2br(htmlspecialchars(trim($paragraph))) . '</p>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Values Section -->
        <div class="values">
            <div class="value-card">
                <div class="value-icon">🍞</div>
                <h3>Quality First</h3>
                <p>We source only the finest ingredients and use traditional methods to ensure every product meets our exacting standards.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">❤️</div>
                <h3>Made with Love</h3>
                <p>Each item is crafted with passion and dedication, bringing warmth and comfort to your table.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🌱</div>
                <h3>Sustainable</h3>
                <p>We're committed to sustainable practices and supporting local suppliers whenever possible.</p>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta">
            <h2>Experience the Difference</h2>
            <p>Join us and discover why Neo Exclusive Cafe has become a beloved destination for quality baked goods</p>
            <a href="/frontend/pages/products/products-categories.php" class="cta-button">Explore Our Products</a>
        </div>
    </div>

    <!-- Footer -->
    <?php include "../../user-includes/user-footer.php"; ?>

    <script src="about-page.js"></script>
</body>
</html>