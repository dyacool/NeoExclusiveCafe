<?php
require_once __DIR__ . "/../../../includes/session-manager.php";
require_once __DIR__ . "/../admin-includes/database.php";

if (!SessionManager::isAdminLoggedIn()) {
    http_response_code(403);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["id"])) {
    $id = intval($_GET["id"]);
    
    // Use the same query pattern as view-blog-admin.php
    $sql = "SELECT * FROM blog_posts WHERE adblog_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        
        // Log the data being returned for debugging
        error_log("Post data for ID $id: " . json_encode($row));
        
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