<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: /NeoExclusiveCafe/pages/auth/login-signup.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);

    // Validate inputs
    if (empty($title) || empty($description)) {
        echo "error";
        exit();
    }

    $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $description, $id);

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