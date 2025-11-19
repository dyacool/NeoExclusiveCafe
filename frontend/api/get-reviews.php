<?php
/**
 * Get Product Reviews API
 * Fetches reviews for a specific product
 */

require_once __DIR__ . '/../../config/database-config.php';

header('Content-Type: application/json');

$conn = getDatabaseConnection();

// Get product ID from query parameter
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Fetch approved reviews with user information
$reviews_query = "SELECT 
    pr.id,
    pr.rating,
    pr.review_text,
    pr.created_at,
    pr.is_featured,
    u.firstname,
    u.lastname,
    u.id as user_id
FROM product_reviews pr
INNER JOIN users u ON pr.user_id = u.id
WHERE pr.product_id = ? AND pr.is_approved = 1
ORDER BY pr.is_featured DESC, pr.created_at DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("iii", $product_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    // Mask user name for privacy (show only first letter of first name)
    $display_name = substr($row['firstname'], 0, 1) . '. ' . $row['lastname'];
    
    // Fetch media for this review
    $media_query = "SELECT media_type, cloud_url, width, height, duration FROM review_media WHERE review_id = ? ORDER BY display_order ASC";
    $media_stmt = $conn->prepare($media_query);
    $media_stmt->bind_param("i", $row['id']);
    $media_stmt->execute();
    $media_result = $media_stmt->get_result();
    
    $media = [];
    while ($media_row = $media_result->fetch_assoc()) {
        $media[] = [
            'type' => $media_row['media_type'],
            'url' => $media_row['cloud_url'],
            'width' => $media_row['width'],
            'height' => $media_row['height'],
            'duration' => $media_row['duration']
        ];
    }
    $media_stmt->close();
    
    $reviews[] = [
        'id' => $row['id'],
        'rating' => intval($row['rating']),
        'review_text' => $row['review_text'],
        'created_at' => $row['created_at'],
        'is_featured' => (bool)$row['is_featured'],
        'user_name' => $display_name,
        'user_id' => $row['user_id'],
        'media' => $media
    ];
}

// Get review statistics
$stats_query = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
FROM product_reviews
WHERE product_id = ? AND is_approved = 1";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $product_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

$response = [
    'success' => true,
    'reviews' => $reviews,
    'statistics' => [
        'total_reviews' => intval($stats['total_reviews'] ?? 0),
        'average_rating' => round(floatval($stats['average_rating'] ?? 0), 2),
        'rating_distribution' => [
            '5' => intval($stats['five_star'] ?? 0),
            '4' => intval($stats['four_star'] ?? 0),
            '3' => intval($stats['three_star'] ?? 0),
            '2' => intval($stats['two_star'] ?? 0),
            '1' => intval($stats['one_star'] ?? 0)
        ]
    ]
];

echo json_encode($response);

$stmt->close();
$stats_stmt->close();
$conn->close();
?>

