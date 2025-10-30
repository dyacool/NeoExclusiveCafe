<?php
    session_start();
    if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
        header("Location: /login/admin/admin-login.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/NeoExclusiveCafe/css/admin/admin-profile.css">
    <title>Admin Profile</title>
</head>
<body>
    <?php
        include $_SERVER['DOCUMENT_ROOT'] . "/NeoExclusiveCafe/php/includes/navbar.php";
    ?>
    
    <div class="wrapper">   <!-- parang ito ay ung body -->
        <div class="content">
            <div class="profile-container">
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title">Profile Information</h1>
                        <div class="avatar">
                            <!-- If you have a profile image, uncomment this line and add the image URL -->
                            <!-- <img src="profile-image.jpg" alt="John Doe"> -->  
                            <!-- If no profile image, show initials -->
                            <span>EX</span>
                        </div>
                        <p class="user-username">@username</p>
                    </div>
                    <div class="card-content">
                        <div class="info-group">
                            <div class="info-label">Username</div>
                            <div class="info-value">username</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Full Name</div>
                            <div class="info-value">Example Example</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Email</div>
                            <div class="info-value">email@example.com</div>
                        </div>
                        
                        <a href="admin-account.php" class="btn">Edit Profile</a>
                        
                        <!-- Added View Info Section -->
                        <div class="view-info">
                            <ul>
                                <li>
                                    <a href="archive.php">
                                        <span class="title">Archive</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="reset-password.php">
                                        <span class="title">Reset Password</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
