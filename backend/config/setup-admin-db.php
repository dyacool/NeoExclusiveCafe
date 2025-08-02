<?php
require_once "../includes/database.php";

// First, delete all existing users except admin
$stmt = $conn->prepare("DELETE FROM users WHERE email != 'annadechavez@hotmail.com'");
$stmt->execute();

// Read and execute the SQL file
$sql = file_get_contents(__DIR__ . "/../../sql/admin_setup.sql");

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
    echo "Database setup completed successfully!";
} else {
    echo "Errors occurred during setup:<br>";
    foreach ($errors as $error) {
        echo "- " . htmlspecialchars($error) . "<br>";
    }
}

// Set up admin user
$firstname = "Annalyn";
$lastname = "De Chavez";
$username = "admin";
$adminEmail = "annadechavez@hotmail.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create new admin user if doesn't exist
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, username, email, password, is_admin, is_verified, verification_token, verification_token_expires_at) VALUES (?, ?, ?, ?, ?, TRUE, TRUE, NULL, NULL)");
    $stmt->bind_param("sssss", $firstname, $lastname, $username, $adminEmail, $password);
} else {
    // Update existing admin user
    $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, username = ?, password = ?, is_admin = TRUE, is_verified = TRUE, verification_token = NULL, verification_token_expires_at = NULL WHERE email = ?");
    $stmt->bind_param("sssss", $firstname, $lastname, $username, $password, $adminEmail);
}

if ($stmt->execute()) {
    $adminId = $result->num_rows === 0 ? $conn->insert_id : $result->fetch_assoc()['id'];
    
    // Assign admin role
    $stmt = $conn->prepare("
        INSERT INTO user_roles (user_id, role_id)
        SELECT ?, id FROM admin_roles WHERE name = 'admin'
        ON DUPLICATE KEY UPDATE user_id = user_id
    ");
    $stmt->bind_param("i", $adminId);
    
    if ($stmt->execute()) {
        echo "<br>Database has been reset and all users have been deleted.";
        echo "<br>Admin user setup completed successfully!";
        echo "<br>Admin Details:";
        echo "<br>- Name: $firstname $lastname";
        echo "<br>- Username: $username";
        echo "<br>- Email: $adminEmail";
        echo "<br>- Password: admin123";
        echo "<br>- Status: Verified";
    } else {
        echo "<br>Error assigning admin role: " . $conn->error;
    }
} else {
    echo "<br>Error setting up admin user: " . $conn->error;
}

$conn->close();
?> 