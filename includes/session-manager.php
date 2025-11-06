<?php
/**
 * SessionManager - Centralized Session Management
 * 
 * Provides a unified API for handling authentication and session state across the NeoCafe application.
 * This class eliminates inconsistent session checks and provides a single source of truth for
 * authentication logic.
 * 
 * Usage:
 *   require_once __DIR__ . '/path/to/includes/session-manager.php';
 *   
 *   // Check authentication
 *   if (SessionManager::isUserLoggedIn()) {
 *       $userData = SessionManager::getUserData();
 *   }
 *   
 *   // Protect pages
 *   SessionManager::requireUserLogin();
 * 
 * @author NeoCafe Development Team
 * @version 1.0.0
 */
class SessionManager {
    
    /**
     * Ensure session is started before accessing session variables
     * 
     * @return void
     */
    private static function ensureSessionStarted(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if a user (customer) is currently logged in
     * 
     * Validates that the session contains a valid user_id and user_role='user'.
     * This is the correct way to check user authentication in frontend pages.
     * 
     * @return bool True if user is logged in, false otherwise
     * 
     * @example
     * if (SessionManager::isUserLoggedIn()) {
     *     echo "Welcome back!";
     * }
     */
    public static function isUserLoggedIn(): bool {
        self::ensureSessionStarted();
        
        // User must have user_id set AND user_role must equal 'user'
        return isset($_SESSION['user_id']) 
            && isset($_SESSION['user_role']) 
            && $_SESSION['user_role'] === 'user';
    }
    
    /**
     * Check if an admin is currently logged in
     * 
     * Validates that the session contains is_admin=true and admin_role='admin'.
     * Note: This does NOT check $_SESSION['admin_id'] as that was causing bugs.
     * 
     * @return bool True if admin is logged in, false otherwise
     * 
     * @example
     * if (SessionManager::isAdminLoggedIn()) {
     *     // Show admin features
     * }
     */
    public static function isAdminLoggedIn(): bool {
        self::ensureSessionStarted();
        
        // Admin must have is_admin=true AND admin_role='admin'
        return isset($_SESSION['is_admin']) 
            && $_SESSION['is_admin'] === true
            && isset($_SESSION['admin_role']) 
            && $_SESSION['admin_role'] === 'admin';
    }
    
    /**
     * Check if the current visitor is in preview mode (not logged in)
     * 
     * Preview mode means neither a user nor an admin is logged in.
     * Useful for showing limited features to guests.
     * 
     * @return bool True if in preview mode, false otherwise
     * 
     * @example
     * if (SessionManager::isPreviewMode()) {
     *     echo "Sign in to access all features";
     * }
     */
    public static function isPreviewMode(): bool {
        return !self::isUserLoggedIn() && !self::isAdminLoggedIn();
    }
    
    /**
     * Get the current user's ID
     * 
     * Returns the user ID if a valid user session exists, null otherwise.
     * The ID is type-cast to integer for safety.
     * 
     * @return int|null User ID or null if not logged in
     * 
     * @example
     * $userId = SessionManager::getUserId();
     * if ($userId !== null) {
     *     // Fetch user data from database
     * }
     */
    public static function getUserId(): ?int {
        if (!self::isUserLoggedIn()) {
            return null;
        }
        
        return (int)$_SESSION['user_id'];
    }
    
    /**
     * Get the current user's data as an array
     * 
     * Returns a clean array with user information if logged in.
     * Returns null if not logged in.
     * 
     * @return array|null Associative array with keys: id, username, firstname, lastname, role
     * 
     * @example
     * $user = SessionManager::getUserData();
     * if ($user) {
     *     echo "Hello, " . htmlspecialchars($user['firstname']);
     * }
     */
    public static function getUserData(): ?array {
        if (!self::isUserLoggedIn()) {
            return null;
        }
        
        return [
            'id' => (int)$_SESSION['user_id'],
            'username' => $_SESSION['user_username'] ?? '',
            'firstname' => $_SESSION['user_firstname'] ?? '',
            'lastname' => $_SESSION['user_lastname'] ?? '',
            'role' => 'user'
        ];
    }
    
    /**
     * Get the current admin's data as an array
     * 
     * Returns a clean array with admin information if logged in.
     * Returns null if not logged in.
     * 
     * @return array|null Associative array with keys: id, username, firstname, lastname, role
     * 
     * @example
     * $admin = SessionManager::getAdminData();
     * if ($admin) {
     *     echo "Admin: " . htmlspecialchars($admin['username']);
     * }
     */
    public static function getAdminData(): ?array {
        if (!self::isAdminLoggedIn()) {
            return null;
        }
        
        return [
            'id' => (int)($_SESSION['admin_id'] ?? 0),
            'username' => $_SESSION['admin_username'] ?? '',
            'firstname' => $_SESSION['admin_firstname'] ?? '',
            'lastname' => $_SESSION['admin_lastname'] ?? '',
            'role' => 'admin'
        ];
    }
    
    /**
     * Get the current session role
     * 
     * Returns 'user' if user is logged in, 'admin' if admin is logged in,
     * or 'guest' if neither is logged in.
     * 
     * @return string One of: 'user', 'admin', 'guest'
     * 
     * @example
     * $role = SessionManager::getRole();
     * switch ($role) {
     *     case 'user':
     *         // Show user features
     *         break;
     *     case 'admin':
     *         // Show admin features
     *         break;
     *     case 'guest':
     *         // Show limited features
     *         break;
     * }
     */
    public static function getRole(): string {
        if (self::isUserLoggedIn()) {
            return 'user';
        }
        
        if (self::isAdminLoggedIn()) {
            return 'admin';
        }
        
        return 'guest';
    }
    
    /**
     * Require user login - redirect if not logged in
     * 
     * Checks if a user is logged in. If not, redirects to the login page and exits.
     * Use this at the top of protected pages that require user authentication.
     * 
     * @param string $redirectUrl URL to redirect to if not logged in
     * @return void
     * 
     * @example
     * // At the top of a protected page
     * SessionManager::requireUserLogin();
     * // Rest of the page code...
     */
    public static function requireUserLogin(string $redirectUrl = '/frontend/login/user/login-signup.php'): void {
        if (!self::isUserLoggedIn()) {
            header("Location: $redirectUrl");
            exit();
        }
    }
    
    /**
     * Require admin login - redirect if not logged in
     * 
     * Checks if an admin is logged in. If not, redirects to the admin login page and exits.
     * Use this at the top of protected admin pages.
     * 
     * @param string $redirectUrl URL to redirect to if not logged in
     * @return void
     * 
     * @example
     * // At the top of an admin page
     * SessionManager::requireAdminLogin();
     * // Rest of the page code...
     */
    public static function requireAdminLogin(string $redirectUrl = '/backend/login/admin/admin-login.php'): void {
        if (!self::isAdminLoggedIn()) {
            header("Location: $redirectUrl");
            exit();
        }
    }
    
    /**
     * Destroy the current session
     * 
     * Clears all session variables and destroys the session.
     * Useful for logout functionality.
     * 
     * @return void
     * 
     * @example
     * // In logout.php
     * SessionManager::destroySession();
     * header("Location: /");
     * exit();
     */
    public static function destroySession(): void {
        self::ensureSessionStarted();
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }
}
