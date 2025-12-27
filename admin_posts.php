<?php
/**
 * Admin Post Management
 * View, filter, and delete posts from all users
 */

session_start();
require_once 'config/database.php';
require_once 'config/admin_auth.php';
require_admin();

$page_title = 'Post Management';
$success = '';
$error = '';

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_post'])) {
    $post_id = (int)($_POST['post_id'] ?? 0);
    $reason = sanitize_input($_POST['reason'] ?? 'Deleted by admin');
    
    if ($post_id > 0) {
        $admin_id = $_SESSION['user_id'];
        
        // Soft delete the post
        $stmt = $conn->prepare("UPDATE posts SET is_deleted = 1, deleted_by = ?, deleted_at = NOW(), delete_reason = ? WHERE id = ?");
        $stmt->bind_param("isi", $admin_id, $reason, $post_id);
        
        if ($stmt->execute()) {
            log_admin_action('delete_post', 'post', $post_id, ['reason' => $reason]);
            $success = "Post deleted successfully";
        } else {
            $error = "Failed to delete post";
        }
        $stmt->close();
    }
}

// Handle post restoration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_post'])) {
    $post_id = (int)($_POST['post_id'] ?? 0);
    
    if ($post_id > 0) {
        $stmt = $conn->prepare("UPDATE posts SET is_deleted = 0, deleted_by = NULL, deleted_at = NULL, delete_reason = NULL WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        
        if ($stmt->execute()) {
            log_admin_action('restore_post', 'post', $post_id);
            $success = "Post restored successfully";
        } else {
            $error = "Failed to restore post";
        }
        $stmt->close();
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$user_filter = $_GET['user_id'] ?? '';
$status_filter = $_GET['status'] ?? 'active'; // active, deleted, all

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($status_filter === 'deleted') {
    $where_clauses[] = "p.is_deleted = 1";
} elseif ($status_filter === 'active') {
    $where_clauses[] = "p.is_deleted = 0";
}
// 'all' shows both

if (!empty($search)) {
    $where_clauses[] = "(p.content LIKE ? OR u.username LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

if (!empty($user_filter) && is_numeric($user_filter)) {
    $where_clauses[] = "p.user_id = ?";
    $params[] = (int)$user_filter;
    $types .= 'i';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "
    SELECT p.*, u.username, u.profile_image,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           da.username as deleted_by_username
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN users da ON p.deleted_by = da.id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT 100
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user list for filter
$users_list = $conn->query("SELECT id, username FROM users ORDER BY username ASC LIMIT 100")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container" style="max-width: 1400px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: var(--text-primary);">
            <i class="fas fa-images"></i> Post Management
        </h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="post" style="padding: 20px; margin-bottom: 24px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 200px 200px auto auto; gap: 12px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-search"></i> Search
                </label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search posts or users..."
                       style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-user"></i> User
                </label>
                <select name="user_id" style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <option value="">All Users</option>
                    <?php foreach ($users_list as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-filter"></i> Status
                </label>
                <select name="status" style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Posts</option>
                    <option value="deleted" <?php echo $status_filter === 'deleted' ? 'selected' : ''; ?>>Deleted Posts</option>
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Posts</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            
            <?php if (!empty($search) || !empty($user_filter) || $status_filter !== 'active'): ?>
                <a href="admin_posts.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Posts Grid -->
    <div style="margin-bottom: 20px; color: var(--text-secondary);">
        <i class="fas fa-info-circle"></i> Showing <?php echo count($posts); ?> posts
    </div>

    <?php if (!empty($posts)): ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($posts as $post): ?>
                <div class="post" style="<?php echo $post['is_deleted'] ? 'opacity: 0.6; border: 2px solid var(--danger-color);' : ''; ?>">
                    <!-- Post Header -->
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <img src="assets/uploads/profiles/<?php echo htmlspecialchars($post['profile_image'] ?: 'default-avatar.png'); ?>" 
                                 style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                            <div>
                                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                                    <?php echo htmlspecialchars($post['username']); ?>
                                </div>
                                <div style="font-size: 13px; color: var(--text-muted);">
                                    <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                </div>
                                <?php if ($post['is_deleted']): ?>
                                    <div style="margin-top: 4px; padding: 3px 8px; background: var(--danger-color); color: #fff; border-radius: 4px; font-size: 11px; display: inline-block;">
                                        DELETED by <?php echo htmlspecialchars($post['deleted_by_username']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <?php if ($post['is_deleted']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="restore_post" class="btn btn-sm" style="background: var(--success-color); padding: 6px 12px; font-size: 12px;" onclick="return confirm('Restore this post?')">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm" style="background: var(--danger-color); padding: 6px 12px; font-size: 12px;" onclick="showDeleteModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['username']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div style="margin-bottom: 16px; color: var(--text-primary);">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>

                    <!-- Post Media (Image or Video) -->
                    <?php if (!empty($post['image']) && $post['media_type'] == 'image'): ?>
                        <div style="margin-bottom: 16px;">
                            <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                                 style="width: 100%; max-height: 500px; object-fit: cover; border-radius: var(--radius);"
                                 onerror="this.style.display='none'">
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['video']) && $post['media_type'] == 'video'): ?>
                        <div style="margin-bottom: 16px;">
                            <video controls preload="metadata" style="width: 100%; max-height: 500px; border-radius: var(--radius); background: #000;">
                                <source src="assets/uploads/posts/<?php echo htmlspecialchars($post['video']); ?>" type="video/mp4">
                                <source src="assets/uploads/posts/<?php echo htmlspecialchars($post['video']); ?>" type="video/webm">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    <?php endif; ?>

                    <!-- Post Stats -->
                    <div style="display: flex; gap: 24px; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 14px; color: var(--text-secondary);">
                        <span><i class="fas fa-heart"></i> <?php echo number_format($post['like_count']); ?> likes</span>
                        <span><i class="fas fa-comment"></i> <?php echo number_format($post['comment_count']); ?> comments</span>
                        <span><i class="fas fa-hashtag"></i> ID: <?php echo $post['id']; ?></span>
                    </div>

                    <?php if ($post['is_deleted'] && $post['delete_reason']): ?>
                        <div style="margin-top: 12px; padding: 12px; background: rgba(239, 68, 68, 0.1); border-left: 3px solid var(--danger-color); border-radius: var(--radius); font-size: 13px;">
                            <strong style="color: var(--danger-color);">Deletion Reason:</strong>
                            <div style="color: var(--text-secondary); margin-top: 4px;">
                                <?php echo htmlspecialchars($post['delete_reason']); ?>
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                Deleted on: <?php echo date('M d, Y H:i', strtotime($post['deleted_at'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="post" style="padding: 60px; text-align: center;">
            <i class="fas fa-inbox" style="font-size: 64px; color: var(--text-muted); margin-bottom: 20px;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 8px;">No posts found</h3>
            <p style="color: var(--text-muted);">Try adjusting your filters</p>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Post Modal -->
<div id="deleteModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
    <div class="post" style="width: 90%; max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-trash" style="color: var(--danger-color);"></i> Delete Post
        </h3>
        <form method="POST">
            <input type="hidden" name="post_id" id="deletePostId">
            
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
                You are about to delete a post by <strong id="deleteUsername"></strong>. This action can be reversed later if needed.
            </p>
            
            <div class="form-group">
                <label>Reason for deletion</label>
                <textarea name="reason" required placeholder="Enter reason (e.g., spam, inappropriate content, violation of terms)..." style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary); min-height: 100px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <button type="submit" name="delete_post" class="btn" style="background: var(--danger-color);">
                    <i class="fas fa-trash"></i> Delete Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showDeleteModal(postId, username) {
    document.getElementById('deletePostId').value = postId;
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal on background click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
