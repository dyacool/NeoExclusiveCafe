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
    <link rel="stylesheet" href="/backend/pages/user-page-content/user-content-settings.css">
    <title>User Content Settings</title>
</head>
<body>
    <?php include __DIR__ . "/../admin-includes/navbar/navbar.php"; ?>
    
    <!-- FIXED: Wrap content in container with proper class -->
    <div class="user-content-settings-container">
        <div class="main-container">
            <!-- Page Header -->
            <div class="page-header">
                <p class="page-subtitle">Manage and customize the content displayed on user-facing pages of your website</p>
            </div>

            <!-- Website Content Category -->
            <div class="settings-category">
                <h2 class="category-title">
                    <div class="category-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <rect x="9" y="9" width="6" height="6"></rect>
                            <path d="M9 1v6"></path>
                            <path d="M15 1v6"></path>
                            <path d="M9 17v6"></path>
                            <path d="M15 17v6"></path>
                            <path d="M1 9h6"></path>
                            <path d="M1 15h6"></path>
                            <path d="M17 9h6"></path>
                            <path d="M17 15h6"></path>
                        </svg>
                    </div>
                    Website Content
                </h2>
                
                <div class="settings-grid">
                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/manage-carousel-images.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon dashboard">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="9"></rect>
                                        <rect x="14" y="3" width="7" height="5"></rect>
                                        <rect x="14" y="12" width="7" height="9"></rect>
                                        <rect x="3" y="16" width="7" height="5"></rect>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Dashboard Images</h3>
                                <p class="setting-description">Manage carousel images and hero banners displayed on the main dashboard</p>
                            </div>
                        </a>
                    </div>

                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/manage-carousel-settings.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon content">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Dashboard Content</h3>
                                <p class="setting-description">Edit text content, headlines, and descriptions shown on the dashboard</p>
                            </div>
                        </a>
                    </div>

                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/admin-service-edit.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon service">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Services</h3>
                                <p class="setting-description">Manage services offered and their descriptions on the dashboard</p>
                            </div>
                        </a>
                    </div>

                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/promotions-settings.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon promotions">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        <path d="M13 8H7"></path>
                                        <path d="M17 12H7"></path>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Promotions</h3>
                                <p class="setting-description">Manage promotional content, offers, and marketing messages</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Site Policies Category -->
            <div class="settings-category">
                <h2 class="category-title">
                    <div class="category-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    Site Policies & Legal
                </h2>
                
                <div class="settings-grid">
                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/about-settings.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon about">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <h3 class="setting-title">About Page</h3>
                                <p class="setting-description">Update company information, mission, and story on the about page</p>
                            </div>
                        </a>
                    </div>

                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/terms-conditions-settings.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon terms">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Terms & Conditions</h3>
                                <p class="setting-description">Update terms of service and conditions for website usage</p>
                            </div>
                        </a>
                    </div>

                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/privacy-policy-settings.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon footer">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Privacy Policy</h3>
                                <p class="setting-description">Update privacy policy, data handling, and cookie policies</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Site Structure Category -->
            <div class="settings-category">
                <h2 class="category-title">
                    <div class="category-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    Site Structure
                </h2>
                
                <div class="settings-grid">
                    <div class="setting-card">
                        <a href="/backend/pages/user-page-content/footer-settings.php" class="setting-card-link">
                            <div class="setting-card-content">
                                <div class="setting-icon footer">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <rect x="3" y="15" width="18" height="6" rx="1"></rect>
                                    </svg>
                                </div>
                                <h3 class="setting-title">Footer Settings</h3>
                                <p class="setting-description">Customize footer content, links, and social media connections</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . "/../admin-includes/footer/admin-footer.php"; ?>
</body>
</html>
