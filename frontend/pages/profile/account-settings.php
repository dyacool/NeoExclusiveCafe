<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once "../../../backend/pages/admin-includes/database.php";
}
require_once "../../../includes/session-manager.php";

// Require user login - redirect if not authenticated
SessionManager::requireUserLogin('/frontend/pages/home/user-dashboard.php');

// Get the values directly from the session and database
$user_id = SessionManager::getUserId();

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

// Determine profile image url - prioritize Cloudinary from session first, then database
$profile_image_url = '';
$profile_public_id = '';
$has_profile_image = false;

// Check session first for profile image (set during login)
if (isset($_SESSION['user_profile_image']) && !empty(trim($_SESSION['user_profile_image']))) {
    $profile_image_url = trim($_SESSION['user_profile_image']);
    $profile_public_id = $_SESSION['user_profile_public_id'] ?? '';
    $has_profile_image = true;
} elseif (isset($row['cloud_url']) && !empty(trim($row['cloud_url']))) {
    // Fallback to database
    $profile_image_url = trim($row['cloud_url']);
    $profile_public_id = $row['cloud_public_id'] ?? '';
    $has_profile_image = true;
    
    // Also update session for consistency
    $_SESSION['user_profile_image'] = $profile_image_url;
    $_SESSION['user_profile_public_id'] = $profile_public_id;
} elseif (isset($row['profile_image']) && !empty(trim($row['profile_image']))) {
    // Fallback to old profile_image field
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
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
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
</head>
<body>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    <?php include __DIR__ . "/../../user-includes/bread-crumb/bread-crumb.php"; ?>

    
    <div class="container">
        
        <div class="profile-section fade-in">
        <h1> Account Settings</h1>

            
            <!-- Profile Picture Section -->
            <div class="profile-picture-section">
                <div class="avatar-upload-container" id="avatar-upload-container">
                    <div class="avatar" id="avatar">
                        <?php if ($has_profile_image): ?>
                            <img id="profile-image" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        <?php else: 
                            // Show initials with theme color
                            $initials = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
                        ?>
                            <span id="initials" ><?php echo $initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-overlay">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                            <circle cx="12" cy="13" r="3"></circle>
                        </svg>
                    </div>
                    <?php if ($has_profile_image && !empty($profile_public_id)): ?>
                        <button type="button" class="remove-avatar-btn" id="remove-avatar-btn" data-public-id="<?php echo htmlspecialchars($profile_public_id); ?>" onclick="openRemovePictureModal('<?php echo htmlspecialchars($profile_public_id); ?>')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
                <p class="avatar-hint">Click to change profile picture</p>
                <input type="file" id="file-input" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp">
                
                <!-- Hidden fields for CSRF token and user ID -->
                <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']; ?>">
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

        <div id="passwordModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closePasswordModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </span>
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

        <!-- Remove Picture Confirmation Modal -->
        <div id="removePictureModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeRemovePictureModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </span>
                <h2>Remove Profile Picture</h2>
                <p style="text-align: center; margin-bottom: 2rem; color: var(--text-muted);">
                    Are you sure you want to remove your profile picture? This action cannot be undone.
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="button" class="btn cancel-btn" onclick="closeRemovePictureModal()">Cancel</button>
                    <button type="button" class="btn update-btn" id="confirmRemoveBtn" onclick="confirmRemoveProfilePicture()" style="background: var(--error);">Remove Picture</button>
                </div>
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
            document.getElementById('passwordModal').classList.add('show');
        }

        function closePasswordModal() {
            console.log('Close modal function called'); // Debug line
            const modal = document.getElementById('passwordModal');
            console.log('Modal element:', modal); // Debug line
            modal.classList.remove('show');
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

        // Remove Picture Modal Functions
        let currentPublicId = '';

        function openRemovePictureModal(publicId) {
            console.log('Opening remove modal with publicId:', publicId); // Debug
            console.log('Type of publicId:', typeof publicId); // Debug
            
            if (!publicId || publicId.trim() === '') {
                console.error('No valid public ID provided to modal');
                showConfirmation('Error: No image ID found', 'error');
                return;
            }
            
            currentPublicId = publicId.trim();
            console.log('Stored currentPublicId:', currentPublicId); // Debug
            document.getElementById('removePictureModal').classList.add('show');
        }

        function closeRemovePictureModal() {
            document.getElementById('removePictureModal').classList.remove('show');
            currentPublicId = '';
            
            // Reset the confirm button state
            const confirmBtn = document.getElementById('confirmRemoveBtn');
            confirmBtn.innerHTML = 'Remove Picture';
            confirmBtn.disabled = false;
            confirmBtn.style.background = 'var(--error)';
        }

        function confirmRemoveProfilePicture() {
            console.log('Confirm remove called with publicId:', currentPublicId); // Debug
            
            if (!currentPublicId) {
                console.error('No public ID provided');
                showConfirmation('Error: No image ID found', 'error');
                closeRemovePictureModal();
                return;
            }
            
            // Add loading state to confirm button
            const confirmBtn = document.getElementById('confirmRemoveBtn');
            confirmBtn.innerHTML = '<span class="btn-loader"></span> Removing...';
            confirmBtn.disabled = true;
            
            // Call the direct removal function
            removeProfilePictureDirectly(currentPublicId);
        }

        // Direct removal function to bypass external confirmations
        function removeProfilePictureDirectly(publicId) {
            console.log('Direct removal called with publicId:', publicId); // Debug
            
            // Call the remove function from the external JS file but tell it to skip confirmation
            if (typeof handleRemoveProfilePicture === 'function') {
                // Temporarily override any confirm dialogs
                const originalConfirm = window.confirm;
                window.confirm = function() { return true; }; // Always return true to skip confirmation
                
                try {
                    // Call the remove function
                    handleRemoveProfilePicture(publicId);
                    
                    // Close modal after successful call
                    setTimeout(() => {
                        closeRemovePictureModal();
                    }, 500);
                    
                } catch (error) {
                    console.error('Error removing profile picture:', error);
                    showConfirmation('Error removing profile picture', 'error');
                    closeRemovePictureModal();
                }
                
                // Restore original confirm function after a short delay
                setTimeout(() => {
                    window.confirm = originalConfirm;
                }, 1000);
            } else {
                console.error('handleRemoveProfilePicture function not found');
                showConfirmation('Error: Remove function not available', 'error');
                closeRemovePictureModal();
            }
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var passwordModal = document.getElementById('passwordModal');
            var removePictureModal = document.getElementById('removePictureModal');
            
            if (event.target == passwordModal) {
                passwordModal.classList.remove('show');
            }
            if (event.target == removePictureModal) {
                removePictureModal.classList.remove('show');
            }
        }

        // Handle ESC key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closePasswordModal();
                closeRemovePictureModal();
            }
        });

        // Add event listener for close button as backup
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('.close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Close button clicked via event listener');
                    closePasswordModal();
                });
            }
        });
    </script>
    
    <!-- Include AJAX JavaScript -->
    <script src="../account/js/profile-picture-ajax.js"></script>
</body>
</html>