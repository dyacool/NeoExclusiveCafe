<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Include database configuration
require_once __DIR__ . "/../../../config/database-config.php";

// Get database connection
$conn = getDatabaseConnection();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);

    // Validate inputs
    if (empty($title) || empty($description)) {
        echo "error";
        exit();
    }

    // Try adblog_id first, then fallback to id
    $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, description = ? WHERE adblog_id = ? OR id = ?");
    $stmt->bind_param("ssii", $title, $description, $id, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
} else {
    echo "error";
}

$conn->close();
?> 