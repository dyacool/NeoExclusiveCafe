<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION["admin_id"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . "/../admin-includes/database.php";

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['profile_picture'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
    exit();
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit();
}

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/../../../assets/public/admin-profile-images';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'admin_' . $_SESSION["admin_id"] . '_' . time() . '.' . $extension;
$upload_path = $upload_dir . '/' . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    exit();
}

// Database-relative path (root-relative for URLs)
$db_path = '/assets/public/admin-profile-images/' . $filename;

// Update database - check if profile_image column exists
$check_column = "SHOW COLUMNS FROM users LIKE 'profile_image'";
$column_result = $conn->query($check_column);

if ($column_result->num_rows === 0) {
    // Add column if it doesn't exist
    $add_column = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL";
    $conn->query($add_column);
}

// Get old profile image to delete
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["admin_id"]);
$stmt->execute();
$result = $stmt->get_result();
$old_data = $result->fetch_assoc();

// Delete old profile image if exists
if ($old_data && !empty($old_data['profile_image'])) {
    $old_file = __DIR__ . '/../../../' . ltrim($old_data['profile_image'], '/');
    if (file_exists($old_file)) {
        unlink($old_file);
    }
}

// Update database with new image path
$stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
$stmt->bind_param("si", $db_path, $_SESSION["admin_id"]);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'image_url' => $db_path
    ]);
} else {
    // Delete uploaded file if database update fails
    unlink($upload_path);
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}

$stmt->close();
$conn->close();
