<?php
session_start();
require_once "../../../backend/pages/admin-includes/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to save posts']);
    exit();
}

// Check if post_id is provided
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = (int)$_POST['post_id'];
$action = $_POST['action'] ?? 'save'; // 'save' or 'unsave'

// First, check if the post exists and is published
$check_post = mysqli_prepare($conn, "SELECT id FROM user_blog_post WHERE id = ? AND status = 'published'");
mysqli_stmt_bind_param($check_post, "i", $post_id);
mysqli_stmt_execute($check_post);
$post_result = mysqli_stmt_get_result($check_post);

if (mysqli_num_rows($post_result) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit();
}

try {
    if ($action === 'save') {
        // Try to save the post
        $save_query = "INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $save_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Post saved successfully', 'action' => 'saved']);
        } else {
            // If there's a duplicate entry error, the post is already saved
            if (mysqli_errno($conn) === 1062) {
                echo json_encode(['success' => true, 'message' => 'Post is already saved', 'action' => 'saved']);
            } else {
                throw new Exception("Error saving post: " . mysqli_error($conn));
            }
        }
    } else {
        // Unsave the post
        $unsave_query = "DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?";
        $stmt = mysqli_prepare($conn, $unsave_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode(['success' => true, 'message' => 'Post unsaved successfully', 'action' => 'unsaved']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Post was not saved', 'action' => 'unsaved']);
            }
        } else {
            throw new Exception("Error unsaving post: " . mysqli_error($conn));
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 