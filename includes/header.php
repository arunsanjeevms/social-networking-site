<?php
/**
 * Header Include File
 * 
 * This file contains the navigation bar and is included
 * on all pages (except login/signup) for consistent navigation.
 * 
 * Features:
 * - Responsive navigation
 * - Dark theme toggle
 * - User session check
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Social Network</title>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/social/assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <div class="logo">
                <i class="fas fa-users"></i>
                <span>SocialNet</span>
            </div>
            
            <!-- Navigation Links -->
            <?php if ($is_logged_in): ?>
            <ul class="nav-links">
                <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="stories.php"><i class="fas fa-circle-notch"></i> Stories</a></li>
                <li><a href="discover.php"><i class="fas fa-user-friends"></i> Discover</a></li>
                <li><a href="bookmarks.php"><i class="fas fa-bookmark"></i> Saved</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="create_post.php"><i class="fas fa-plus-circle"></i> Create</a></li>
                <li>
                    <!-- Notifications -->
                    <div style="position: relative;">
                        <button class="notification-bell" onclick="toggleNotifications()" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" style="display: none;">0</span>
                        </button>
                        <div id="notificationDropdown" class="notification-dropdown">
                            <div style="padding: 14px; border-bottom: 1px solid var(--border-color); font-weight: 700;">
                                <i class="fas fa-bell"></i> Notifications
                            </div>
                            <div id="notificationsList"></div>
                        </div>
                    </div>
                </li>
                <li>
                    <!-- Theme Toggle Button -->
                    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main>
