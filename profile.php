<?php
/**
 * Profile Page
 * 
 * This page displays user profile information and allows
 * profile image and bio updates.
 * Features:
 * - Display user information
 * - Profile image upload with preview
 * - Bio editing
 * - View user's posts
 */

// Start session and check authentication
session_start();
require_once 'config/database.php';
require_login();

// Set page title
$page_title = 'Profile';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Initialize variables
$success = '';
$error = '';

// Handle profile image upload
if (isset($_POST['update_profile'])) {
    $bio = sanitize_input($_POST['bio']);
    
    // Update bio
    $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->bind_param("si", $bio, $user_id);
    $stmt->execute();
    $stmt->close();
    
    $success = "Profile updated successfully!";
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $file_type = $_FILES['profile_image']['type'];
    $file_size = $_FILES['profile_image']['size'];
    
    // Validate file type and size (max 5MB)
    if (in_array($file_type, $allowed_types) && $file_size <= 5000000) {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'assets/uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            // Update database
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);
            $stmt->execute();
            $stmt->close();
            
            $success = "Profile image updated successfully!";
        } else {
            $error = "Error uploading image";
        }
    } else {
        $error = "Invalid file type or file too large (max 5MB)";
    }
}

// Fetch user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch user's posts
$stmt = $conn->prepare("SELECT p.*, 
                        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
                        FROM posts p 
                        WHERE p.user_id = ? 
                        ORDER BY p.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts_result = $stmt->get_result();

// Calculate user statistics
$total_posts = $posts_result->num_rows;

// Get total likes received
$stmt_likes = $conn->prepare("SELECT COUNT(*) as total_likes 
                               FROM likes l 
                               JOIN posts p ON l.post_id = p.id 
                               WHERE p.user_id = ?");
$stmt_likes->bind_param("i", $user_id);
$stmt_likes->execute();
$likes_result = $stmt_likes->get_result();
$total_likes = $likes_result->fetch_assoc()['total_likes'];

// Get total comments received
$stmt_comments = $conn->prepare("SELECT COUNT(*) as total_comments 
                                 FROM comments c 
                                 JOIN posts p ON c.post_id = p.id 
                                 WHERE p.user_id = ?");
$stmt_comments->bind_param("i", $user_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
$total_comments = $comments_result->fetch_assoc()['total_comments'];

// Include header
include 'includes/header.php';
?>

<div class="container">
    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-image-wrapper">
            <img src="assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                 alt="Profile" 
                 class="profile-image"
                 onerror="this.src='assets/uploads/profiles/default-avatar.png'">
        </div>
        
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
            <p class="bio">
                <?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio yet. Tell us about yourself!'; ?>
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
            
            <!-- Profile Upload Form -->
            <div class="profile-upload">
                <form method="POST" enctype="multipart/form-data" style="display: inline-block; margin-right: 10px;">
                    <div class="file-input-wrapper">
                        <input type="file" name="profile_image" id="profileImage" accept="image/*" onchange="this.form.submit()">
                        <label for="profileImage" class="file-input-label">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Bio Section -->
    <div class="create-post-box">
        <h3><i class="fas fa-edit"></i> Edit Bio</h3>
        <form method="POST" action="">
            <div class="form-group">
                <textarea name="bio" class="form-control" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Bio
            </button>
        </form>
    </div>
    
    <!-- User's Posts -->
    <h2 style="margin-bottom: 20px; color: var(--text-primary);">
        <i class="fas fa-images"></i> My Posts (<?php echo $posts_result->num_rows; ?>)
    </h2>
    
    <?php if ($posts_result->num_rows > 0): ?>
        <?php while ($post = $posts_result->fetch_assoc()): ?>
            <div class="post">
                <!-- Post Header -->
                <div class="post-header">
                    <img src="assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>" 
                         class="post-avatar"
                         onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                    <div class="post-user-info">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <span class="username">@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <span class="post-time">
                        <i class="far fa-clock"></i> <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                    </span>
                </div>
                
                <!-- Post Content -->
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                
                <!-- Post Image -->
                <?php if (!empty($post['image'])): ?>
                    <div class="post-image">
                        <img src="assets/uploads/posts/<?php echo htmlspecialchars($post['image']); ?>" 
                             alt="Post image">
                    </div>
                <?php endif; ?>
                
                <!-- Post Stats -->
                <div class="post-actions">
                    <div class="post-action-btn">
                        <i class="fas fa-heart"></i>
                        <span><?php echo $post['like_count']; ?> likes</span>
                    </div>
                    <div class="post-action-btn">
                        <i class="fas fa-comment"></i>
                        <span><?php echo $post['comment_count']; ?> comments</span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="post text-center" style="padding: 40px;">
            <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-secondary);">No posts yet</h3>
            <p style="color: var(--text-secondary);">Share your first post with the community!</p>
            <a href="create_post.php" class="btn btn-primary" style="display: inline-block; width: auto; margin-top: 15px;">
                <i class="fas fa-plus"></i> Create Post
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
include 'includes/footer.php';
?>
