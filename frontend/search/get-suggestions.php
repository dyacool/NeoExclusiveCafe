<?php
// Include database connection
require_once __DIR__ . '/../../backend/pages/admin-includes/database.php';

header('Content-Type: application/json');

// Get the search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

$suggestions = [];
$search_param = "%" . $query . "%";

try {
    // Get product suggestions (limit to 5) - search by name, description, and category
    $product_sql = "SELECT DISTINCT p.id, p.name, 'product' as type 
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   WHERE (p.name LIKE ? 
                          OR p.description LIKE ?
                          OR c.name LIKE ?)
                   AND p.deleted_at IS NULL 
                   AND p.status_id IN (1, 2, 3, 4)
                   ORDER BY 
                       CASE 
                           WHEN p.name LIKE CONCAT('%', ?, '%') THEN 1
                           WHEN c.name LIKE CONCAT('%', ?, '%') THEN 2
                           WHEN p.description LIKE CONCAT('%', ?, '%') THEN 3
                           ELSE 4
                       END,
                       p.name ASC
                   LIMIT 5";
    
    $stmt = $conn->prepare($product_sql);
    if ($stmt) {
        // Extract the query without % for CONCAT function
        $query_clean = str_replace('%', '', $search_param);
        // Bind the search parameter multiple times
        $stmt->bind_param("ssssss", $search_param, $search_param, $search_param, $query_clean, $query_clean, $query_clean);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = [
                'name' => $row['name'],
                'type' => 'product',
                'id' => $row['id']
            ];
        }
        $stmt->close();
    }
    
    // Get blog post suggestions (limit to 5) - search by title and description
    $blog_sql = "SELECT adblog_id, title as name, 'blog' as type 
                FROM blog_posts 
                WHERE (title LIKE ? OR description LIKE ?)
                ORDER BY 
                    CASE 
                        WHEN title LIKE CONCAT('%', ?, '%') THEN 1
                        WHEN description LIKE CONCAT('%', ?, '%') THEN 2
                        ELSE 3
                    END,
                    created_at DESC
                LIMIT 5";
    
    $stmt = $conn->prepare($blog_sql);
    if ($stmt) {
        // Extract the query without % for CONCAT function
        $query_clean = str_replace('%', '', $search_param);
        // Bind search parameter for title and description searches
        $stmt->bind_param("ssss", $search_param, $search_param, $query_clean, $query_clean);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = [
                'name' => $row['name'],
                'type' => 'blog',
                'id' => $row['adblog_id']
            ];
        }
        $stmt->close();
    }
    
    // Get testimonial suggestions (limit to 3) - search by customer name and testimonial text
    $testimonial_sql = "SELECT id, customer_name as name, 'testimonial' as type 
                       FROM customer_testimonials 
                       WHERE (customer_name LIKE ? OR testimonial_text LIKE ?)
                       ORDER BY 
                           CASE 
                               WHEN customer_name LIKE CONCAT('%', ?, '%') THEN 1
                               WHEN testimonial_text LIKE CONCAT('%', ?, '%') THEN 2
                               ELSE 3
                           END,
                           created_at DESC
                       LIMIT 3";
    
    $stmt = $conn->prepare($testimonial_sql);
    if ($stmt) {
        // Extract the query without % for CONCAT function
        $query_clean = str_replace('%', '', $search_param);
        // Bind search parameter for name and text searches
        $stmt->bind_param("ssss", $search_param, $search_param, $query_clean, $query_clean);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = [
                'name' => $row['name'],
                'type' => 'testimonial',
                'id' => $row['id']
            ];
        }
        $stmt->close();
    }
