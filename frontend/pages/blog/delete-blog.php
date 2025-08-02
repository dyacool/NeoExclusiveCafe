<?php
require_once "../includes/database.php";
session_start();

// Set the content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'message' => '');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please log in to delete posts.';
    echo json_encode($response);
    exit();
}

// Check if post_id is provided
if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    $response['message'] = 'Invalid post ID.';
    echo json_encode($response);
    exit();
}

$post_id = (int)$_POST['post_id'];

// First, get the post details to check ownership and get image path
$check_sql = "SELECT * FROM user_blog_post WHERE id = ? AND user_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $post_id, $_SESSION['user_id']);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($result) === 0) {
    $response['message'] = 'Post not found or you do not have permission to delete it.';
    echo json_encode($response);
    exit();
}

// Get the post data for image deletion
$post = mysqli_fetch_assoc($result);

// Delete the post from database
$delete_sql = "DELETE FROM user_blog_post WHERE id = ? AND user_id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($delete_stmt, "ii", $post_id, $_SESSION['user_id']);

if (mysqli_stmt_execute($delete_stmt)) {
    // If post is deleted successfully, delete the associated image if it exists
    if (!empty($post['image_path']) && file_exists("../../" . $post['image_path'])) {
        unlink("../../" . $post['image_path']);
    }
    
    $response['success'] = true;
    $response['message'] = 'Post deleted successfully.';
} else {
    $response['message'] = 'Error deleting post: ' . mysqli_error($conn);
}

// Make sure there's no extra output
ob_clean();
echo json_encode($response);
exit();
?> 