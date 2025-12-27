<?php
/**
 * User Profile View Page - Modern Dark Theme
 * 
 * This page displays another user's profile information
 * and their posts. Used for NFC-based profile opening.
 * 
 * Features:
 * - View any user's profile via ?id= parameter
 * - NFC-compatible: NFC tags redirect here
 * - Display user stats and posts
 * - Follow functionality
 */

// Start session and check authentication
session_start();
require_once 'config/database.php';

// Set page title
$page_title = 'User Profile';

// Get profile user ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit();
}

// Validate and sanitize user ID
$profile_user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if ($profile_user_id === false) {
    header("Location: home.php");
    exit();
}

// Check if user is logged in
$is_logged_in = is_logged_in();
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Redirect to own profile if viewing self and logged in
if ($is_logged_in && $profile_user_id == $current_user_id) {
    header("Location: profile.php");
    exit();
}

// Fetch profile user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found - show error page
    $page_title = 'User Not Found';
    if ($is_logged_in) {
        include 'includes/header.php';
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php if (!$is_logged_in): ?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Not Found - SocialNet</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="/social/assets/css/dark.css">
        <?php endif; ?>
    </head>
    <body>
        <div class="container">
            <div class="nfc-section">
                <div class="nfc-icon" style="background: var(--gradient-danger);">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h3 style="color: var(--danger-color);">User Not Found</h3>
                <p>The user you're looking for doesn't exist or has been removed.</p>
                <a href="<?php echo $is_logged_in ? 'home.php' : 'index.php'; ?>" class="btn btn-primary" style="margin-top: 20px; display: inline-flex; width: auto;">
                    <i class="fas fa-home"></i>
                    <span>Go Home</span>
                </a>
            </div>
        </div>
        <?php if ($is_logged_in): include 'includes/footer.php'; endif; ?>
    </body>
    </html>
    <?php
    exit();
}

$profile_user = $result->fetch_assoc();
$stmt->close();

// Update page title with username
$page_title = htmlspecialchars($profile_user['username']) . "'s Profile";

// Fetch user's posts (allow viewing even if not logged in)
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT p.*, u.username, u.profile_image, u.is_verified,
                            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                            FROM posts p 
                            JOIN users u ON p.user_id = u.id
                            WHERE p.user_id = ? AND p.is_deleted = 0 
                            ORDER BY p.created_at DESC");
    $stmt->bind_param("ii", $current_user_id, $profile_user_id);
} else {
    $stmt = $conn->prepare("SELECT p.*, u.username, u.profile_image, u.is_verified,
                            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                            0 as user_liked,
                            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                            FROM posts p 
                            JOIN users u ON p.user_id = u.id
                            WHERE p.user_id = ? AND p.is_deleted = 0 
                            ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $profile_user_id);
}
$stmt->execute();
$posts_result = $stmt->get_result();

// Calculate user statistics
$total_posts = $posts_result->num_rows;

// Get total likes received
$stmt_likes = $conn->prepare("SELECT COUNT(*) as total_likes 
                               FROM likes l 
                               JOIN posts p ON l.post_id = p.id 
                               WHERE p.user_id = ?");
$stmt_likes->bind_param("i", $profile_user_id);
$stmt_likes->execute();
$likes_result = $stmt_likes->get_result();
$total_likes = $likes_result->fetch_assoc()['total_likes'];

// Get total comments received
$stmt_comments = $conn->prepare("SELECT COUNT(*) as total_comments 
                                 FROM comments c 
                                 JOIN posts p ON c.post_id = p.id 
                                 WHERE p.user_id = ?");
$stmt_comments->bind_param("i", $profile_user_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
$total_comments = $comments_result->fetch_assoc()['total_comments'];

// Time ago function
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return $minutes == 1 ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return $hours == 1 ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return $days == 1 ? "Yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return $weeks == 1 ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return $months == 1 ? "1 month ago" : "$months months ago";
    } else {
        return $years == 1 ? "1 year ago" : "$years years ago";
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-image-wrapper">
            <img src="assets/uploads/profiles/<?php echo htmlspecialchars($profile_user['profile_image']); ?>" 
                 alt="Profile" 
                 class="profile-image"
                 onerror="this.src='assets/uploads/profiles/default-avatar.png'">
        </div>
        
        <div class="profile-info">
            <h1>
                <?php echo htmlspecialchars($profile_user['username']); ?>
                <?php if ($profile_user['is_verified']): ?>
                    <span class="verified-badge" title="Verified User"><i class="fas fa-check"></i></span>
                <?php endif; ?>
            </h1>
            <p class="username">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
            <p class="bio">
                <?php echo !empty($profile_user['bio']) ? nl2br(htmlspecialchars($profile_user['bio'])) : 'No bio yet.'; ?>
            </p>
            
            <!-- User Statistics -->
            <div class="stats-container">
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_posts; ?></span>
                    <span class="stat-label">Posts</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_likes; ?></span>
                    <span class="stat-label">Likes Received</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo $total_comments; ?></span>
                    <span class="stat-label">Comments Received</span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <?php if ($is_logged_in && $profile_user_id != $current_user_id): ?>
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <a href="messages.php?user=<?php echo $profile_user_id; ?>" 
                   class="btn" 
                   style="background: var(--gradient-primary); color: #000; padding: 10px 24px; text-decoration: none; border-radius: var(--radius); display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fas fa-envelope"></i> Send Message
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- User's Posts -->
    <h2 style="margin-bottom: 20px; color: var(--text-primary);">
        <i class="fas fa-images"></i> Posts by <?php echo htmlspecialchars($profile_user['username']); ?> (<?php echo $total_posts; ?>)
    </h2>
    
    <?php if ($posts_result->num_rows > 0): ?>
        <?php 
        // Reset pointer to beginning
        $posts_result->data_seek(0);
        while ($post = $posts_result->fetch_assoc()): 
        ?>
            <div class="post" id="post-<?php echo $post['id']; ?>">
                <!-- Post Header -->
                <div class="post-header">
                    <img src="assets/uploads/profiles/<?php echo htmlspecialchars($post['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($post['username']); ?>" 
                         class="post-avatar"
                         onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                    <div class="post-user-info">
                        <h4>
                            <?php echo htmlspecialchars($post['username']); ?>
                            <?php if ($post['is_verified']): ?>
                                <span class="verified-badge" title="Verified User"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                        </h4>
                        <span class="username">@<?php echo htmlspecialchars($post['username']); ?></span>
                    </div>
                    <span class="post-time">
                        <i class="far fa-clock"></i> <?php echo time_ago($post['created_at']); ?>
                    </span>
                </div>
                
                <!-- Post Content -->
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                
                <!-- Post Media (Image or Video) -->
                <?php if (!empty($post['image']) && $post['media_type'] == 'image'): ?>
                    <div class="post-image" onclick="openLightbox('assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>')" style="cursor: pointer;">
                        <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                             alt="Post image">
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($post['video']) && $post['media_type'] == 'video'): ?>
                    <div class="post-video">
                        <video controls preload="metadata" style="max-width: 100%; border-radius: 8px; background: #000;">
                            <source src="assets/uploads/posts/<?php echo htmlspecialchars($post['video']); ?>" type="video/mp4">
                            <source src="assets/uploads/posts/<?php echo htmlspecialchars($post['video']); ?>" type="video/webm">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                <?php endif; ?>
                
                <!-- Post Actions -->
                <div class="post-actions">
                    <button class="post-action-btn <?php echo $post['user_liked'] > 0 ? 'liked' : ''; ?>" 
                            onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                        <i class="<?php echo $post['user_liked'] > 0 ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span class="like-count"><?php echo $post['like_count']; ?></span>
                    </button>
                    
                    <div class="post-action-btn">
                        <i class="fas fa-comment"></i>
                        <span><?php echo $post['comment_count']; ?> comments</span>
                    </div>
                    
                    <button class="post-action-btn" onclick="openShareModal(<?php echo $post['id']; ?>)">
                        <i class="fas fa-share"></i>
                        <span>Share</span>
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="post text-center" style="padding: 40px;">
            <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-secondary);">No posts yet</h3>
            <p style="color: var(--text-secondary);">This user hasn't shared anything yet.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Image Lightbox -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <button class="lightbox-close" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </button>
        <img id="lightboxImage" class="lightbox-image" src="" alt="Post image">
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="modal" onclick="closeShareModal()">
    <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeShareModal()">
            <i class="fas fa-times"></i>
        </button>
        <h2><i class="fas fa-share-alt"></i> Share Post</h2>
        <div class="share-options">
            <div class="share-option" onclick="sharePost('twitter')" style="color: #1DA1F2;">
                <i class="fab fa-twitter"></i>
                <div>Twitter</div>
            </div>
            <div class="share-option" onclick="sharePost('facebook')" style="color: #1877F2;">
                <i class="fab fa-facebook"></i>
                <div>Facebook</div>
            </div>
            <div class="share-option" onclick="sharePost('whatsapp')" style="color: #25D366;">
                <i class="fab fa-whatsapp"></i>
                <div>WhatsApp</div>
            </div>
            <div class="share-option" onclick="sharePost('telegram')" style="color: #0088cc;">
                <i class="fab fa-telegram"></i>
                <div>Telegram</div>
            </div>
            <div class="share-option" onclick="sharePost('copy')" style="color: #6c757d;">
                <i class="fas fa-link"></i>
                <div>Copy Link</div>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>
