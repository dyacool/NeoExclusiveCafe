<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../../backend/pages/admin-includes/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/pages/home/user-dashboard.php");
    exit();
}

// Get the values directly from the session and database
$user_id = $_SESSION['user_id'];

// Check if username exists in session, otherwise get it from database
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get user details from database including Cloudinary fields
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Debug user data
echo "<!-- Debug: User data from DB = " . print_r($row, true) . " -->";

$email = $row['email'];
$firstname = $row['firstname'];
$lastname = $row['lastname'];

// Get username from database if not in session
if (empty($username) && isset($row['username'])) {
    $username = $row['username'];
}

// Determine profile image url - prioritize Cloudinary
$profile_image_url = '';
$profile_public_id = '';
$has_profile_image = false;

if (isset($row['cloud_url']) && !empty(trim($row['cloud_url']))) {
    $profile_image_url = trim($row['cloud_url']);
    $profile_public_id = $row['cloud_public_id'] ?? '';
    $has_profile_image = true;
} elseif (isset($row['profile_image']) && !empty(trim($row['profile_image']))) {
    $db_path = trim($row['profile_image']);
    if ($db_path[0] !== '/') {
        $db_path = '/' . $db_path;
    }
    $profile_image_url = $db_path;
    $has_profile_image = true;
}

$message = "";
$error = "";

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password === $confirm_password) {
        // Verify current password
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Password updated successfully!";
            } else {
                $error = "Error updating password.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        $error = "New passwords do not match.";
    }
}

// Get message from session if exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after using it
}

// Debug current profile image
echo "<!-- Current profile image path: " . ($row['profile_image'] ?? 'null') . " -->";
echo "<!-- Current cloud URL: " . ($row['cloud_url'] ?? 'null') . " -->";
echo "<!-- Current cloud public ID: " . ($row['cloud_public_id'] ?? 'null') . " -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="account-settings.css">
    <link rel="stylesheet" href="../account/css/profile-picture-ajax.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Confirmation Popup - Success/Error Notification */
        .confirmation-popup {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: white;
            color: #333;
            padding: 16px 24px;
            border-radius: 12px;
            z-index: 10000;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-weight: 600;
            min-width: 300px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 2px solid transparent;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Success State - Green Theme */
        .confirmation-popup.success {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #2e7d32;
            border-color: #4caf50;
            box-shadow: 0 10px 40px rgba(76, 175, 80, 0.3);
        }

        /* Error State - Red Theme */
        .confirmation-popup.error {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            border-color: #f44336;
            box-shadow: 0 10px 40px rgba(244, 67, 54, 0.3);
        }

        /* Show Animation */
        .confirmation-popup.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Hide Animation */
        .confirmation-popup.hide {
            opacity: 0;
            transform: translateX(-50%) translateY(-100px);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .confirmation-popup {
                top: 70px;
                min-width: 280px;
                max-width: 90%;
                padding: 14px 20px;
                font-size: 14px;
            }
            
            .confirmation-popup.show {
                transform: translateX(-50%) translateY(0);
            }
            
            .confirmation-popup.hide {
                transform: translateX(-50%) translateY(-100px);
            }
        }

        /* Button Loader Styles */
        .btn-loader {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

    
    <div class="container">
        <h1> Account Settings</h1>
        
        <div class="profile-section fade-in">
            
            <!-- Profile Picture Section -->
            <div class="profile-picture-section">
                <div class="avatar-upload-container" id="avatar-upload-container">
                    <div class="avatar" id="avatar">
                        <?php if ($has_profile_image): ?>
                            <img id="profile-image" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: 
                            // Show initials with randomized green color
                            $initials = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
                            
                            // Generate consistent random green-toned color based on user's name
                            $seed = crc32($firstname . $lastname);
                            mt_srand($seed);
                            
                            // Green color ranges: hue 80-160 (yellow-green to blue-green)
                            $hue = mt_rand(80, 160);
                            $saturation = mt_rand(40, 70); // Medium saturation
                            $lightness = mt_rand(35, 50); // Medium-dark for good contrast
                            
                            $color1 = "hsl($hue, {$saturation}%, $lightness%)";
                            
                            // Second color slightly different
                            $hue2 = $hue + mt_rand(-15, 15);
                            $lightness2 = $lightness + mt_rand(-5, 10);
                            $color2 = "hsl($hue2, {$saturation}%, $lightness2%)";
                            
                            $gradient = "linear-gradient(135deg, $color1 0%, $color2 100%)";
                        ?>
                            <span id="initials" style="background: <?php echo $gradient ?>;"><?php echo $initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-overlay">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                            <circle cx="12" cy="13" r="3"></circle>
                        </svg>
                    </div>
                    <?php if ($has_profile_image && !empty($profile_public_id)): ?>
                        <button type="button" class="remove-avatar-btn" id="remove-avatar-btn" data-public-id="<?php echo htmlspecialchars($profile_public_id); ?>" onclick="handleRemoveProfilePicture('<?php echo htmlspecialchars($profile_public_id); ?>')">
                        </button>
                    <?php endif; ?>
                </div>
                <p class="avatar-hint">Click to change profile picture</p>
                <input type="file" id="file-input" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp">
                
                <!-- Hidden fields for CSRF token and user ID -->
                <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                
                <!-- Loading and success indicators -->
                <div id="profileLoadingIndicator" class="loading-indicator" style="display: none;">
                    Uploading...
                </div>
                <div id="profileSuccessIndicator" class="success-indicator" style="display: none;">
                    Upload successful!
                </div>
            </div>

            <div class="form-group">
                <label>Username:</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Password</label>
                <button type="button" class="btn changepw-btn" onclick="openPasswordModal()">Change Password</button>
            </div>     
        </div> 

        <div id="passwordModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
            <div class="modal-content">
                <span class="close" onclick="closePasswordModal()">&times;</span>
                <h2>Change Password</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="button" class="btn cancel-btn" onclick="closePasswordModal()">Cancel</button>
                        <button type="submit" name="change_password" class="btn update-btn" id="updatePasswordBtn">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Popup -->
    <div id="confirmationPopup"></div>
    
    <script>
        // Show confirmation on page load if there's a message
        <?php if ($message): ?>
            window.addEventListener('DOMContentLoaded', function() {
                showConfirmation('<?php echo addslashes($message); ?>', 'success');
            });
        <?php endif; ?>
        
        <?php if ($error): ?>
            window.addEventListener('DOMContentLoaded', function() {
                showConfirmation('<?php echo addslashes($error); ?>', 'error');
            });
        <?php endif; ?>

        // Confirmation popup function
        function showConfirmation(message, type = 'success') {
            const popup = document.getElementById('confirmationPopup');
            
            const icon = type === 'success' ? '✓' : '✕';
            
            popup.innerHTML = `${icon} ${message}`;
            popup.className = `confirmation-popup ${type}`;
            
            // Trigger show animation
            setTimeout(() => {
                popup.classList.add('show');
            }, 10);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                popup.classList.remove('show');
                popup.classList.add('hide');
                setTimeout(() => {
                    popup.className = '';
                    popup.innerHTML = '';
                }, 400);
            }, 3000);
        }

        // Handle password form submission
        document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
            const btn = document.getElementById('updatePasswordBtn');
            
            // Add loader to button
            if (!btn.querySelector('.btn-loader')) {
                const loader = document.createElement('span');
                loader.className = 'btn-loader';
                btn.insertBefore(loader, btn.firstChild);
                btn.classList.add('loading');
            }
        });

        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            // Reset form
            document.querySelector('form[method="POST"]').reset();
            // Remove loader if exists
            const btn = document.getElementById('updatePasswordBtn');
            const loader = btn.querySelector('.btn-loader');
            if (loader) {
                loader.remove();
                btn.classList.remove('loading');
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Handle ESC key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePasswordModal();
            }
        });
    </script>
    
    <!-- Include AJAX JavaScript -->
    <script src="../account/js/profile-picture-ajax.js"></script>
</body>
</html>