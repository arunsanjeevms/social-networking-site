<?php
/**
 * People Suggestions Page
 * Discover new people to follow
 */

session_start();
require_once 'config/database.php';
require_login();

$page_title = 'Discover People';
$user_id = $_SESSION['user_id'];

// Get random users to suggest (excluding current user and already followed)
$query = "SELECT u.id, u.username, u.email, u.profile_image, u.bio, u.is_verified,
          (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND is_deleted = 0) as post_count,
          (SELECT COUNT(*) FROM followers WHERE following_id = u.id) as follower_count
          FROM users u
          WHERE u.id != ? 
          AND u.id NOT IN (SELECT following_id FROM followers WHERE follower_id = ?)
          ORDER BY RAND()
          LIMIT 12";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$suggestions_result = $stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px; color: var(--text-primary);">
        <i class="fas fa-user-friends"></i> People You May Know
    </h1>
    
    <?php if ($suggestions_result->num_rows > 0): ?>
        <div class="suggestions-grid">
            <?php while ($user = $suggestions_result->fetch_assoc()): ?>
                <div class="suggestion-card">
                    <img src="assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>"
                         class="suggestion-avatar"
                         onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                    <div class="suggestion-name">
                        <?php echo htmlspecialchars($user['username']); ?>
                        <?php if ($user['is_verified']): ?>
                            <span class="verified-badge" title="Verified User"><i class="fas fa-check"></i></span>
                        <?php endif; ?>
                    </div>
                    <div class="suggestion-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <p style="color: var(--text-secondary); font-size:13px; margin-bottom:12px; line-height:1.4;">
                            <?php echo htmlspecialchars(substr($user['bio'], 0, 60)) . (strlen($user['bio']) > 60 ? '...' : ''); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="display:flex; gap:16px; justify-content:center; margin-bottom:12px; font-size:13px; color: var(--text-secondary);">
                        <div><strong><?php echo $user['post_count']; ?></strong> posts</div>
                        <div><strong><?php echo $user['follower_count']; ?></strong> followers</div>
                    </div>
                    
                    <button class="follow-btn" onclick="followUser(<?php echo $user['id']; ?>, this)">
                        <i class="fas fa-user-plus"></i> Follow
                    </button>
                    <button class="btn btn-secondary" style="margin-top:8px; padding:8px 20px; font-size:13px;" onclick="window.location.href='user_profile.php?id=<?php echo $user['id']; ?>'">
                        <i class="fas fa-eye"></i> View Profile
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="post text-center" style="padding: 40px;">
            <i class="fas fa-users" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-secondary);">No suggestions available</h3>
            <p style="color: var(--text-secondary);">Check back later for new people to connect with!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function followUser(userId, button) {
    const formData = new FormData();
    formData.append('user_id', userId);
    
    fetch('ajax/follow_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.following) {
                button.innerHTML = '<i class="fas fa-user-check"></i> Following';
                button.classList.add('following');
            } else {
                button.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                button.classList.remove('following');
            }
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Error', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error following user', 'error');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
