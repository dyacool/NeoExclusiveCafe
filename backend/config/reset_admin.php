<?php
require_once "../includes/database.php";

// Generate a new password hash
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete existing admin
    $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id IN (SELECT id FROM users WHERE email = ?)");
    $email = 'annadechavez@hotmail.com';
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Create fresh admin user
    $stmt = $conn->prepare("
        INSERT INTO users (
            firstname, 
            lastname, 
            username, 
            email, 
            password, 
            is_admin, 
            is_verified
        ) VALUES (?, ?, ?, ?, ?, 1, 1)
    ");

    $firstname = "Annalyn";
    $lastname = "De Chavez";
    $username = "admin";
    
    $stmt->bind_param("sssss", $firstname, $lastname, $username, $email, $hash);
    $stmt->execute();
    
    $admin_id = $conn->insert_id;

    // Ensure admin role exists
    $conn->query("
        INSERT INTO admin_roles (name, description)
        SELECT 'admin', 'Full administrative access'
        WHERE NOT EXISTS (SELECT 1 FROM admin_roles WHERE name = 'admin')
    ");

    // Assign admin role
    $stmt = $conn->prepare("
        INSERT INTO user_roles (user_id, role_id)
        SELECT ?, id FROM admin_roles WHERE name = 'admin'
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    // Commit transaction
    mysqli_commit($conn);

    // Verify the setup
    $stmt = $conn->prepare("
        SELECT 
            u.*, 
            GROUP_CONCAT(ar.name) as roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN admin_roles ar ON ur.role_id = ar.id
        WHERE u.email = ?
        GROUP BY u.id
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    echo "<h2>Admin Account Reset Successfully</h2>";
    echo "<p>Username: admin</p>";
    echo "<p>Password: admin123</p>";
    echo "<p>Email: " . $admin['email'] . "</p>";
    echo "<p>Admin Status: " . ($admin['is_admin'] ? "Yes" : "No") . "</p>";
    echo "<p>Verified: " . ($admin['is_verified'] ? "Yes" : "No") . "</p>";
    echo "<p>Roles: " . $admin['roles'] . "</p>";
    
    // Test password verification
    echo "<p>Password Verification Test: " . 
        (password_verify('admin123', $admin['password']) ? "PASS" : "FAIL") . "</p>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 