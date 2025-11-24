<?php
/**
 * User Profile View Page
 * 
 * This page displays another user's profile information
 * and their posts.
 */

// Start session and check authentication
session_start();
require_once 'config/database.php';
require_login();

// Set page title
$page_title = 'User Profile';

// Get profile user ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$profile_user_id = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];

// Redirect to own profile if viewing self
if ($profile_user_id == $current_user_id) {
    header("Location: profile.php");
    exit();
}

// Fetch profile user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: home.php");
    exit();
}

$profile_user = $result->fetch_assoc();
$stmt->close();

// Fetch user's posts
$stmt = $conn->prepare("SELECT p.*, u.username, u.profile_image,
                        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                        (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                        FROM posts p 
                        JOIN users u ON p.user_id = u.id
                        WHERE p.user_id = ? 
                        ORDER BY p.created_at DESC");
$stmt->bind_param("ii", $current_user_id, $profile_user_id);
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
            <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>
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
                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
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
                
                <!-- Post Image -->
                <?php if (!empty($post['image'])): ?>
                    <div class="post-image" onclick="openLightbox('assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>')" style="cursor: pointer;">
                        <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                             alt="Post image">
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
