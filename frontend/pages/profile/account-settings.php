<link rel="stylesheet" href="../../css/users/account-settings.css"><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../../backend/pages/admin-includes/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get the values directly from the session and database
$user_id = $_SESSION['user_id'];

// Check if username exists in session, otherwise get it from database
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Get user details from database
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

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    // Save to project root assets folder: C:\xampp\htdocs\NeoCafe\assets\public\profile-images
    $upload_dir = __DIR__ . '/../../../assets/public/profile-images/';
    
    // Debug information
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    echo "<!-- Debug: Upload directory = " . $upload_dir . " -->";
    echo "<!-- Debug: File data = " . print_r($_FILES['profile_image'], true) . " -->";
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        $mkdir_result = mkdir($upload_dir, 0777, true);
        if (!$mkdir_result) {
            $error = "Failed to create upload directory. Please check permissions.";
            echo "<!-- Debug: Failed to create directory -->";
        }
    }
    
    $file = $_FILES['profile_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Generate a secure random filename
            try {
                $random_bytes = random_bytes(16);
                $random_string = bin2hex($random_bytes);
            } catch (Exception $e) {
                $random_string = bin2hex(openssl_random_pseudo_bytes(16));
            }
            $new_filename = 'profile_' . $random_string . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            echo "<!-- Debug: Attempting to upload to = " . $upload_path . " -->";
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Store the relative path in database
                $image_path = '/assets/public/profile-images/' . $new_filename;
                $update_query = "UPDATE users SET profile_image = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "si", $image_path, $user_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['message'] = "Profile picture updated successfully!";
                    // Update session so navbar/profile can fetch immediately
                    $_SESSION['user_profile_image'] = $image_path;
                    echo "<!-- Debug: Database updated with path = " . $image_path . " -->";
                    // Redirect to prevent form resubmission
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Error updating profile picture in database: " . mysqli_error($conn);
                    echo "<!-- Debug: Database error = " . mysqli_error($conn) . " -->";
                }
            } else {
                $error = "Error moving uploaded file. Upload path: " . $upload_path;
                echo "<!-- Debug: Failed to move uploaded file -->";
            }
        } else {
            $error = "Invalid file type. Please upload a JPG, JPEG, PNG or GIF file.";
        }
    } else {
        $error = "Error uploading file. Error code: " . $file['error'];
    }
}

// Get user details from database
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Get message from session if exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after using it
}

// Debug current profile image
echo "<!-- Current profile image path: " . ($row['profile_image'] ?? 'null') . " -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Neo Exclusive Cafe</title>
    <link rel="stylesheet" href="account-settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include "../../user-includes/navbar/customer-navigation.php"; ?>
    
    <div class="container">
        <h1> Account Settings</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-section fade-in">
            
            <!-- Profile Picture Section -->
            <div class="profile-picture-section">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="profile-image-form">
                    <div class="current-profile-picture" onclick="document.getElementById('profile_image').click()">
                        <?php if (!empty($row['profile_image'])): ?>
                            <?php $display_path = trim($row['profile_image']); if ($display_path !== '' && $display_path[0] !== '/') { $display_path = '/' . $display_path; } ?>
                            <img src="<?php echo htmlspecialchars($display_path); ?>" alt="Profile Image">
                        <?php else: ?>
                            <img src="/assets/images/profile.svg" alt="Default Profile Image">
                        <?php endif; ?>
                    </div>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="profile-picture-input" onchange="this.form.submit()">
                    <small class="file-info">Click on the image to change your profile picture<br>Supported formats: JPG, JPEG, PNG, GIF</small>
                </form>
            </div>

            <div class="form-group">
                <label>Username:</label>
                <input type="text" value="<?php echo htmlspecialchars($username); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" value="<?php echo htmlspecialchars($firstname); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" value="<?php echo htmlspecialchars($lastname); ?>" readonly>
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
                        <button type="submit" name="change_password" class="btn update-btn">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
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
</body>
</html>