<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin using new session keys
if (!isset($_SESSION["admin_id"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true || $_SESSION["admin_role"] !== "admin") {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Include database and navbar
require_once __DIR__ . "/../admin-includes/database.php";
require_once __DIR__ . "/../admin-includes/navbar/navbar.php";

// Fetch admin information including profile_image
$stmt = $conn->prepare("SELECT username, firstname, lastname, email, profile_image FROM users WHERE id = ? AND is_admin = TRUE");
$stmt->bind_param("i", $_SESSION["admin_id"]);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    header("Location: /login/admin/admin-login.php");
    exit();
}

// Determine profile image url
$profile_default_image_path = '';
$profile_image_url = $profile_default_image_path;
$has_profile_image = false;

if (isset($admin['profile_image']) && !empty(trim($admin['profile_image']))) {
    $db_path = trim($admin['profile_image']);
    if ($db_path[0] !== '/') {
        $db_path = '/' . $db_path;
    }
    $profile_image_url = $db_path;
    $has_profile_image = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="/backend/pages/account/admin-profile.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
</head>
<body>
    <!-- FIXED: Wrap content in container with proper class -->
    <div class="admin-profile-container">
        <div class="main-container">
            <div class="profile-card">
                <div class="profile-header">
                    <h1 class="profile-title">Profile Information</h1>
                    <div class="avatar">
                        <?php if ($has_profile_image): ?>
                            <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile Image">
                        <?php else: ?>
                            <?php echo strtoupper(substr($admin['firstname'], 0, 1) . substr($admin['lastname'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <h2 class="user-name"><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></h2>
                    <p class="user-username">@<?php echo htmlspecialchars($admin['username']); ?></p>
                </div>
                
                <div class="profile-content">
                    <div class="info-group">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin['username']); ?></div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></div>
                    </div>
                    
                    <div class="info-group">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($admin['email']); ?></div>
                    </div>
                    
                    <a href="admin-account.php" class="btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit Profile
                    </a>
                    
                    <!-- Quick Links Section -->
                    <div class="quick-links">
                        <h3 class="quick-links-title">Quick Links</h3>
                        <div class="links-grid">

                            
                            <div class="link-card">
                                <a href="reset-password.php">
                                    <div class="link-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                    </div>
                                    <span class="link-title">Reset Password</span>
                                </a>
                            </div>

                            <div class="link-card">
                                <a href="activity-logs.php">
                                    <div class="link-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg>
                                    </div>
                                    <span class="link-title">Activity Logs</span>
                                </a>
                            </div>

                            <div class="link-card">
                                <a href="../archives/archive.php">
                                    <div class="link-icon">
                                        <svg  width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="3" width="20" height="5" rx="1"></rect>
                                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path>
                                            <path d="M10 12h4"></path>
                                        </svg>
                                    </div>

                                    <span class="link-title">Archive</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
        </div>
    </div>
</body>
</html>
