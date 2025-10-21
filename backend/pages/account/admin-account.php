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
require_once __DIR__ . "/../admin-includes/activity-logger.php";

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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $email = $_POST["email"];

    // Update admin information
    $stmt = $conn->prepare("UPDATE users SET username = ?, firstname = ?, lastname = ?, email = ? WHERE id = ? AND is_admin = TRUE");
    $stmt->bind_param("ssssi", $username, $firstname, $lastname, $email, $_SESSION["admin_id"]);
    
    if ($stmt->execute()) {
        $success_message = "Profile updated successfully!";
        
        // Log the activity
        logAdminActivity($conn, 'UPDATE', "Updated account profile information", 'users', $_SESSION["admin_id"]);
        
        // Refresh admin data
        $stmt = $conn->prepare("SELECT username, firstname, lastname, email, profile_image FROM users WHERE id = ? AND is_admin = TRUE");
        $stmt->bind_param("i", $_SESSION["admin_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        
        // Update profile image variables
        if (isset($admin['profile_image']) && !empty(trim($admin['profile_image']))) {
            $db_path = trim($admin['profile_image']);
            if ($db_path[0] !== '/') {
                $db_path = '/' . $db_path;
            }
            $profile_image_url = $db_path;
            $has_profile_image = true;
        }
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Admin</title>
    <link rel="stylesheet" href="/backend/pages/account/admin-account.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <style>
        /* Breadcrumb Styles */
        .breadcrumb-container {
            margin: 0 auto 1.5rem auto;
            padding: 0 1rem;
        }
        

        
        .breadcrumb-list {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .breadcrumb-link {
            color: var(--green-600);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .breadcrumb-link:hover {
            color: var(--green-700);
            text-decoration: underline;
        }
        
        .breadcrumb-current {
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        .breadcrumb-separator {
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .separator-icon {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>
    <div class="breadcrumb-container">
            <nav class="breadcrumb-nav" aria-label="Breadcrumb navigation">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="admin-profile.php" class="breadcrumb-link">
                            <span class="breadcrumb-text">Profile</span>
                        </a>
                        <span class="breadcrumb-separator" aria-hidden="true">
                            <svg class="separator-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </span>
                    </li>
                    <li class="breadcrumb-item current">
                        <span class="breadcrumb-current" aria-current="page">
                            <span class="breadcrumb-text">Account Settings</span>
                        </span>
                    </li>
                </ol>
            </nav>
        </div>

    <div class="admin-profile-container">
        <!-- Breadcrumb Navigation -->        


        <div class="main-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header">
                    <h1 class="profile-title">Account Settings</h1>
                    
                    <!-- Profile Picture Upload -->
                    <div class="avatar-upload-container" id="avatar-upload-container">
                        <div class="avatar" id="avatar">
                            <?php if ($has_profile_image): ?>
                                <img id="profile-image" src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile picture">
                            <?php else: ?>
                                <span id="initials"><?php echo strtoupper(substr($admin['firstname'], 0, 1) . substr($admin['lastname'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-overlay">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                                <circle cx="12" cy="13" r="3"></circle>
                            </svg>
                        </div>
                    </div>
                    <p class="avatar-hint">Click to change profile picture</p>
                    <input type="file" id="file-input" class="hidden" accept="image/*">
                    <h2 class="user-name"><?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?></h2>
                    <p class="user-username">@<?php echo htmlspecialchars($admin['username']); ?></p>
                </div>
                
                <div class="profile-content">
                    <form id="profile-form" method="POST" action="">
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($admin['username']); ?>" placeholder="Username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" id="firstname" name="firstname" class="form-input" value="<?php echo htmlspecialchars($admin['firstname']); ?>" placeholder="First Name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" id="lastname" name="lastname" class="form-input" value="<?php echo htmlspecialchars($admin['lastname']); ?>" placeholder="Last Name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($admin['email']); ?>" placeholder="Email" required>
                        </div>
                        
                        <button type="submit" class="btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
        </div>
    </div>

    <script>
        // DOM elements
        const avatarUploadContainer = document.getElementById('avatar-upload-container');
        const fileInput = document.getElementById('file-input');
        const avatar = document.getElementById('avatar');
        const profileImage = document.getElementById('profile-image');
        const initialsElement = document.getElementById('initials');
        const profileForm = document.getElementById('profile-form');
        const firstnameInput = document.getElementById('firstname');
        const lastnameInput = document.getElementById('lastname');

        // Update initials based on full name
        function updateInitials() {
            if (!initialsElement) return;
            const firstname = firstnameInput.value;
            const lastname = lastnameInput.value;
            const initials = (firstname.charAt(0) + lastname.charAt(0)).toUpperCase();
            initialsElement.textContent = initials;
        }

        // Handle avatar click to open file picker
        avatarUploadContainer.addEventListener('click', () => {
            fileInput.click();
        });

        // Handle file selection and upload
        fileInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
                alert('Please select a valid image file (JPG, PNG, GIF, or WEBP)');
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            // Show loading state
            avatar.style.opacity = '0.5';
            avatarUploadContainer.style.cursor = 'wait';
            
            // Create FormData and upload
            const formData = new FormData();
            formData.append('profile_picture', file);
            
            try {
                const response = await fetch('upload-profile-picture.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update image display
                    if (profileImage) {
                        profileImage.src = result.image_url + '?t=' + Date.now(); // Cache bust
                        profileImage.style.display = 'block';
                        profileImage.onload = function() {
                            if (initialsElement) initialsElement.style.display = 'none';
                        };
                    } else {
                        // Create image element if it doesn't exist
                        const newImg = document.createElement('img');
                        newImg.id = 'profile-image';
                        newImg.src = result.image_url + '?t=' + Date.now();
                        newImg.alt = 'Profile picture';
                        newImg.style.width = '100%';
                        newImg.style.height = '100%';
                        newImg.style.objectFit = 'cover';
                        newImg.onload = function() {
                            if (initialsElement) initialsElement.style.display = 'none';
                        };
                        avatar.appendChild(newImg);
                    }
                    
                    // Show success message
                    showMessage('Profile picture updated successfully!', 'success');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Failed to upload profile picture. Please try again.');
            } finally {
                avatar.style.opacity = '1';
                avatarUploadContainer.style.cursor = 'pointer';
            }
        });

        // Update initials when name changes
        firstnameInput.addEventListener('input', updateInitials);
        lastnameInput.addEventListener('input', updateInitials);

        // Show message function
        function showMessage(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    ${type === 'success' 
                        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                        : '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'
                    }
                </svg>
                ${message}
            `;
            
            const container = document.querySelector('.main-container');
            const existingAlert = container.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.style.animation = 'fadeOut 0.5s ease';
                setTimeout(() => alertDiv.remove(), 500);
            }, 3000);
        }

        // Initialize
        updateInitials();
    </script>
</body>
</html>
