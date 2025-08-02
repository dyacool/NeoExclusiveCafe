<?php
require_once __DIR__ . "/../includes/database.php";

// Read and execute the SQL file
$sql = file_get_contents(__DIR__ . "/../../sql/reset_admin.sql");

// Split SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
$errors = [];

foreach ($statements as $statement) {
    if (!empty($statement)) {
        try {
            if (!$conn->query($statement)) {
                $success = false;
                $errors[] = "Error executing statement: " . $conn->error;
            }
        } catch (Exception $e) {
            $success = false;
            $errors[] = "Exception: " . $e->getMessage();
        }
    }
}

if ($success) {
    echo "<h2>Admin Account Reset Successfully</h2>";
    echo "<p>Username: admin</p>";
    echo "<p>Password: admin123</p>";
    echo "<p>Email: annadechavez@hotmail.com</p>";
} else {
    echo "<h2>Errors occurred during reset:</h2>";
    foreach ($errors as $error) {
        echo "<p style='color: red;'>" . htmlspecialchars($error) . "</p>";
    }
}

$conn->close();
?> 