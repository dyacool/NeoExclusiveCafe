<?php
class AdminManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Check if a user is an admin
    public function isAdmin($userId) {
        $stmt = $this->conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        return $user && $user['is_admin'];
    }

    // Get user's role
    public function getUserRole($userId) {
        // Simplified to just check is_admin flag
        $stmt = $this->conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? ['name' => $result['is_admin'] ? 'admin' : 'user'] : null;
    }

    // Check if user has a specific permission
    public function hasPermission($userId, $permissionName) {
        // Simplified to just check if user is admin
        return $this->isAdmin($userId);
    }

    // Make a user an admin
    public function makeAdmin($userId) {
        try {
            $this->conn->begin_transaction();

            // Set is_admin flag
            $stmt = $this->conn->prepare("UPDATE users SET is_admin = TRUE WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Get admin role ID
            $stmt = $this->conn->prepare("SELECT id FROM admin_roles WHERE name = 'admin'");
            $stmt->execute();
            $role = $stmt->get_result()->fetch_assoc();

            if (!$role) {
                throw new Exception("Admin role not found");
            }

            // Role assignment removed - using is_admin flag only

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Remove admin privileges from a user
    public function removeAdmin($userId) {
        try {
            $this->conn->begin_transaction();

            // Remove is_admin flag
            $stmt = $this->conn->prepare("UPDATE users SET is_admin = FALSE WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Role removal removed - using is_admin flag only

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    // Get all admin users
    public function getAllAdmins() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT u.id, u.username, u.email, u.firstname, u.lastname
            FROM users u
            WHERE u.is_admin = TRUE
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Example usage:
/*
require_once "../admin-includes/database.php";
$adminManager = new AdminManager($conn);

// Make a user an admin
$adminManager->makeAdmin($userId);

// Check if user has permission
if ($adminManager->hasPermission($userId, 'manage_products')) {
    // Allow product management
}
*/
?> 