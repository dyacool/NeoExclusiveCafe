<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    http_response_code(403);
    exit();
}

// Include database configuration
require_once __DIR__ . "/../../../config/database-config.php";

// Get database connection
$conn = getDatabaseConnection();

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    
    // Try to get post data using either column name
    $sql = "SELECT * FROM blog_posts WHERE adblog_id = ? OR id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>