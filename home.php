<?php
/**
 * Home Feed Page
 * 
 * This is the main feed page showing all posts from all users.
 * Features:
 * - Display posts sorted by newest first
 * - Like/unlike functionality with AJAX
 * - Comment functionality with AJAX
 * - Show post images
 * - Show user profile images
 * - Responsive design
 */

// Start session and check authentication
session_start();
require_once 'config/database.php';
require_login();

// Set page title
$page_title = 'Home Feed';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Fetch all posts with user information, ordered by newest first (exclude deleted posts)
$query = "SELECT p.*, u.username, u.profile_image, u.is_verified,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
          (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
          FROM posts p
          JOIN users u ON p.user_id = u.id
          WHERE p.is_deleted = 0
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts_result = $stmt->get_result();

/**
 * Format time ago function
 * Converts timestamp to readable format (e.g., "2 hours ago")
 */
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
    <!-- Search Bar -->
    <div class="search-container">
        <input type="text" 
               id="userSearch" 
               class="search-box" 
               placeholder="Search users...">
        <i class="fas fa-search search-icon"></i>
        <div id="searchResults" class="search-results"></div>
    </div>
    
    <h1 style="margin-bottom: 30px; color: var(--text-primary);">
        <i class="fas fa-stream"></i> News Feed
    </h1>
    
    <?php if ($posts_result->num_rows > 0): ?>
        <?php while ($post = $posts_result->fetch_assoc()): ?>
            <?php
            // Get comments for this post
            $post_id = $post['id'];
            $comments_query = "SELECT c.*, u.username, u.profile_image 
                              FROM comments c 
                              JOIN users u ON c.user_id = u.id 
                              WHERE c.post_id = ? 
                              ORDER BY c.created_at DESC 
                              LIMIT 3";
            $comments_stmt = $conn->prepare($comments_query);
            $comments_stmt->bind_param("i", $post_id);
            $comments_stmt->execute();
            $comments_result = $comments_stmt->get_result();
            ?>
            
            <div class="post" id="post-<?php echo $post['id']; ?>">
                <!-- Post Header (User Info) -->
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
                    
                    <!-- Post Menu (Edit/Delete) - Only for post owner -->
                    <?php if ($post['user_id'] == $user_id): ?>
                        <div class="post-menu">
                            <button class="post-menu-btn" onclick="togglePostMenu(<?php echo $post['id']; ?>)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div id="post-menu-<?php echo $post['id']; ?>" class="post-menu-dropdown">
                                <div class="post-menu-item" onclick="editPost(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </div>
                                <div class="post-menu-item delete" onclick="deletePost(<?php echo $post['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bookmark Button -->
                    <?php
                    $bookmark_check = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND post_id = ?");
                    $bookmark_check->bind_param("ii", $user_id, $post['id']);
                    $bookmark_check->execute();
                    $is_bookmarked = $bookmark_check->get_result()->num_rows > 0;
                    $bookmark_check->close();
                    ?>
                    <button class="bookmark-btn <?php echo $is_bookmarked ? 'bookmarked' : ''; ?>" 
                            onclick="toggleBookmark(<?php echo $post['id']; ?>, this)" 
                            title="<?php echo $is_bookmarked ? 'Remove bookmark' : 'Bookmark post'; ?>">
                        <i class="<?php echo $is_bookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i>
                    </button>
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
                
                <!-- Post Actions (Like, Comment) -->
                <div class="post-actions">
                    <button class="post-action-btn <?php echo $post['user_liked'] > 0 ? 'liked' : ''; ?>" 
                            onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                        <i class="<?php echo $post['user_liked'] > 0 ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span class="like-count"><?php echo $post['like_count']; ?></span>
                    </button>
                    
                    <button class="post-action-btn comment-btn">
                        <i class="far fa-comment"></i>
                        <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                    </button>
                    
                    <button class="post-action-btn" onclick="openShareModal(<?php echo $post['id']; ?>)">
                        <i class="fas fa-share"></i>
                        <span>Share</span>
                    </button>
                </div>
                
                <!-- Comments Section -->
                <?php if ($comments_result->num_rows > 0 || true): ?>
                    <div class="comments-section">
                        <div class="comments-list">
                            <?php while ($comment = $comments_result->fetch_assoc()): ?>
                                <div class="comment">
                                    <img src="assets/uploads/profiles/<?php echo htmlspecialchars($comment['profile_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($comment['username']); ?>" 
                                         class="comment-avatar"
                                         onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                                    <div class="comment-content">
                                        <div class="comment-author">
                                            <?php echo htmlspecialchars($comment['username']); ?>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </div>
                                        <div class="comment-time">
                                            <?php echo time_ago($comment['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Add Comment Form -->
                        <form class="add-comment-form" onsubmit="addComment(event, <?php echo $post['id']; ?>)">
                            <input type="text" name="comment" placeholder="Write a comment..." required>
                            <button type="submit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php $comments_stmt->close(); ?>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="post text-center" style="padding: 40px;">
            <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-secondary);">No posts yet</h3>
            <p style="color: var(--text-secondary);">Be the first to share something!</p>
            <a href="create_post.php" class="btn btn-primary" style="display: inline-block; width: auto; margin-top: 15px;">
                <i class="fas fa-plus"></i> Create Post
            </a>
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
