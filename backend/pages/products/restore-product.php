<?php
$conn = new mysqli("localhost", "root", "", "crud");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"])) {
    $id = intval($_POST["id"]);
    
    // Restore by setting deleted_at to NULL
    $sql = "UPDATE products SET deleted_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "Product restored successfully!";
    } else {
        echo "Error restoring product: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>
