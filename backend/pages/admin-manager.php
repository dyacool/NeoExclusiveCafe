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
        $stmt = $this->conn->prepare("
            SELECT r.* 
            FROM admin_roles r
            JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Check if user has a specific permission
    public function hasPermission($userId, $permissionName) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN admin_role_permissions rp ON ur.role_id = rp.role_id
            JOIN admin_permissions p ON rp.permission_id = p.id
            WHERE u.id = ? AND p.name = ?
        ");
        $stmt->bind_param("is", $userId, $permissionName);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
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

            // Assign role to user
            $stmt = $this->conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $role['id']);
            $stmt->execute();

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

            // Remove role assignment
            $stmt = $this->conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

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
require_once "../includes/database.php";
$adminManager = new AdminManager($conn);

// Make a user an admin
$adminManager->makeAdmin($userId);

// Check if user has permission
if ($adminManager->hasPermission($userId, 'manage_products')) {
    // Allow product management
}
*/
?> 