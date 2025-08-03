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

// Fetch admin information
$stmt = $conn->prepare("SELECT username, firstname, lastname, email FROM users WHERE id = ? AND is_admin = TRUE");
$stmt->bind_param("i", $_SESSION["admin_id"]);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    header("Location: /login/admin/admin-login.php");
    exit();
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
        // Refresh admin data
        $stmt = $conn->prepare("SELECT username, firstname, lastname, email FROM users WHERE id = ? AND is_admin = TRUE");
        $stmt->bind_param("i", $_SESSION["admin_id"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
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
    <title>Admin Account</title>
    <link rel="stylesheet" href="/backend/pages/account/admin-account.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
</head>
<body>
    <div class="wrapper">
        <button class="cta" onclick="window.location.href='/backend/pages/account/admin-profile.php'">
            <svg
                id="arrow-horizontal"
                xmlns="http://www.w3.org/2000/svg"
                width="30"
                height="10"
                viewBox="0 0 46 16"
            >
                <path
                id="Path_10"
                data-name="Path 10"
                d="M38,0,39.455,1.455,33.949,6.961H76V9.039H33.949l5.506,5.506L38,16l-8-8Z"
                transform="translate(-25)"
                ></path>
            </svg>
            <span class="hover-underline-animation"> Go Back </span>
        </button>

        <div class="main-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">Profile Information</h1>
                    <div class="avatar-container" id="avatar-container">
                        <div class="avatar" id="avatar">
                            <span id="initials"><?php echo strtoupper(substr($admin['firstname'], 0, 1) . substr($admin['lastname'], 0, 1)); ?></span>
                            <img id="profile-image" src="/placeholder.svg" alt="Profile picture" style="display: none;">
                        </div>
                        <div class="avatar-overlay">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"></path>
                                <circle cx="12" cy="13" r="3"></circle>
                            </svg>
                        </div>
                    </div>
                    <p class="avatar-hint">Click to change profile picture</p>
                    <input type="file" id="file-input" class="hidden" accept="image/*">
                </div>
                
                <div class="card-content">
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
                        <button type="submit" class="btn">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>

    <script>
        // DOM elements
        const avatarContainer = document.getElementById('avatar-container');
        const fileInput = document.getElementById('file-input');
        const profileImage = document.getElementById('profile-image');
        const initialsElement = document.getElementById('initials');
        const profileForm = document.getElementById('profile-form');
        const firstnameInput = document.getElementById('firstname');
        const lastnameInput = document.getElementById('lastname');

        // Update initials based on full name
        function updateInitials() {
            const firstname = firstnameInput.value;
            const lastname = lastnameInput.value;
            const initials = (firstname.charAt(0) + lastname.charAt(0)).toUpperCase();
            initialsElement.textContent = initials;
        }

        // Handle avatar click to open file picker
        avatarContainer.addEventListener('click', () => {
            fileInput.click();
        });

        // Handle file selection
        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    profileImage.src = e.target.result;
                    profileImage.style.display = 'block';
                    initialsElement.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Update initials when name changes
        firstnameInput.addEventListener('input', updateInitials);
        lastnameInput.addEventListener('input', updateInitials);

        // Initialize initials
        updateInitials();
    </script>
</body>
</html>
