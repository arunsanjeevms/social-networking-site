<?php
/**
 * Header Include File - Modern Dark Theme
 * 
 * This file contains the navigation bar and is included
 * on all pages (except login/signup) for consistent navigation.
 * 
 * Features:
 * - Modern dark glass-morphism navbar
 * - Responsive navigation
 * - NFC scanner link
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
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>SocialNet</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dark Theme CSS -->
    <link rel="stylesheet" href="/social/assets/css/dark.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <a href="home.php" class="logo">
                <i class="fas fa-users"></i>
                <span>SocialNet</span>
            </a>
            
            <!-- Navigation Links -->
            <?php if ($is_logged_in): ?>
            <ul class="nav-links">
                <li><a href="home.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> <span>Home</span></a></li>
                <li><a href="stories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stories.php' ? 'active' : ''; ?>"><i class="fas fa-circle-notch"></i> <span>Stories</span></a></li>
                <li><a href="discover.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'discover.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> <span>Discover</span></a></li>
                <li><a href="bookmarks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bookmarks.php' ? 'active' : ''; ?>"><i class="fas fa-bookmark"></i> <span>Saved</span></a></li>
                <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> <span>Profile</span></a></li>
                <li><a href="create_post.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create_post.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> <span>Create</span></a></li>
                <li><a href="nfc_scan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'nfc_scan.php' ? 'active' : ''; ?>" title="NFC Scanner"><i class="fas fa-wifi"></i> <span>NFC</span></a></li>
                <?php
                // Load admin auth if needed
                if (!function_exists('is_admin')) {
                    require_once __DIR__ . '/../config/admin_auth.php';
                }
                if (is_admin()):
                ?>
                <li><a href="admin_dashboard.php" class="<?php echo strpos(basename($_SERVER['PHP_SELF']), 'admin') === 0 ? 'active' : ''; ?>" title="Admin Panel" style="background: var(--gradient-primary); color: #000; border-radius: var(--radius); padding: 8px 12px;"><i class="fas fa-shield-alt"></i> <span>Admin</span></a></li>
                <?php endif; ?>
                <li>
                    <!-- Messages -->
                    <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" title="Messages" style="position: relative;">
                        <i class="fas fa-envelope"></i> <span>Messages</span>
                        <?php
                        if (!function_exists('get_unread_messages_count')) {
                            require_once __DIR__ . '/../config/messages.php';
                        }
                        $unread_message_count = get_unread_messages_count($_SESSION['user_id']);
                        if ($unread_message_count > 0):
                        ?>
                        <span class="message-badge"><?php echo $unread_message_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
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
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main>
