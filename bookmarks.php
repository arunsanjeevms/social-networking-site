<?php
/**
 * Bookmarks Feature
 * Save posts for later viewing
 */

session_start();
require_once 'config/database.php';
require_login();

$page_title = 'Bookmarks';
$user_id = $_SESSION['user_id'];

// Fetch bookmarked posts
$query = "SELECT p.*, u.username, u.profile_image,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
          (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
          b.created_at as bookmarked_at
          FROM bookmarks b
          JOIN posts p ON b.post_id = p.id
          JOIN users u ON p.user_id = u.id
          WHERE b.user_id = ?
          ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$bookmarks_result = $stmt->get_result();

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $hours = round($time_difference / 3600);
    $days = round($time_difference / 86400);
    
    if ($time_difference <= 60) return "Just now";
    if ($time_difference <= 3600) return round($time_difference / 60) . " minutes ago";
    if ($hours <= 24) return $hours == 1 ? "1 hour ago" : "$hours hours ago";
    return $days == 1 ? "Yesterday" : "$days days ago";
}

include 'includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px; color: var(--text-primary);">
        <i class="fas fa-bookmark"></i> Saved Posts
    </h1>
    
    <?php if ($bookmarks_result->num_rows > 0): ?>
        <?php while ($post = $bookmarks_result->fetch_assoc()): ?>
            <div class="post" id="post-<?php echo $post['id']; ?>">
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
                    <button class="bookmark-btn bookmarked" onclick="toggleBookmark(<?php echo $post['id']; ?>, this)" title="Remove bookmark">
                        <i class="fas fa-bookmark"></i>
                    </button>
                </div>
                
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                
                <?php if (!empty($post['image'])): ?>
                    <div class="post-image" onclick="openLightbox('assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>')">
                        <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" alt="Post image">
                    </div>
                <?php endif; ?>
                
                <div class="post-actions">
                    <button class="post-action-btn <?php echo $post['user_liked'] > 0 ? 'liked' : ''; ?>" 
                            onclick="toggleLike(<?php echo $post['id']; ?>, this)">
                        <i class="<?php echo $post['user_liked'] > 0 ? 'fas' : 'far'; ?> fa-heart"></i>
                        <span class="like-count"><?php echo $post['like_count']; ?></span>
                    </button>
                    <button class="post-action-btn">
                        <i class="far fa-comment"></i>
                        <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                    </button>
                    <button class="post-action-btn" onclick="window.location.href='home.php#post-<?php echo $post['id']; ?>'">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View</span>
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="post text-center" style="padding: 40px;">
            <i class="fas fa-bookmark" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-secondary);">No saved posts yet</h3>
            <p style="color: var(--text-secondary);">Bookmark posts to save them for later!</p>
            <a href="home.php" class="btn btn-primary" style="display: inline-block; width: auto; margin-top: 15px;">
                <i class="fas fa-home"></i> Go to Home Feed
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
