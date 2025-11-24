<?php
/**
 * Create Post Page
 * 
 * This page allows users to create new posts.
 * Features:
 * - Text content input
 * - Optional image upload
 * - Image preview before posting
 * - File validation (type and size)
 */

// Start session and check authentication
session_start();
require_once 'config/database.php';
require_login();

// Set page title
$page_title = 'Create Post';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Initialize variables
$success = '';
$error = '';

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $content = sanitize_input($_POST['content']);
    
    // Validate content
    if (empty($content)) {
        $error = "Post content cannot be empty";
    } else {
        $image_filename = null;
        
        // Handle image upload if provided
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $file_type = $_FILES['post_image']['type'];
            $file_size = $_FILES['post_image']['size'];
            
            // Validate file type and size (max 10MB)
            if (in_array($file_type, $allowed_types) && $file_size <= 10000000) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'assets/uploads/posts/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION);
                $image_filename = 'post_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $image_filename;
                
                // Move uploaded file
                if (!move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_path)) {
                    $error = "Error uploading image";
                    $image_filename = null;
                }
            } else {
                $error = "Invalid file type or file too large (max 10MB)";
            }
        }
        
        // Insert post into database if no errors
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $content, $image_filename);
            
            if ($stmt->execute()) {
                $success = "Post created successfully!";
                // Redirect to home after 2 seconds
                header("refresh:2;url=home.php");
            } else {
                $error = "Error creating post. Please try again.";
            }
            
            $stmt->close();
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px; color: var(--text-primary);">
        <i class="fas fa-plus-circle"></i> Create New Post
    </h1>
    
    <!-- Success/Error Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <br><small>Redirecting to home feed...</small>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Create Post Form -->
    <div class="create-post-box">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="content">
                    <i class="fas fa-pen"></i> What's on your mind?
                </label>
                <textarea id="content" 
                          name="content" 
                          rows="6" 
                          placeholder="Share your thoughts with the community..." 
                          required></textarea>
                
                <!-- Emoji Picker -->
                <div style="margin-top: 10px;">
                    <div class="emoji-picker-container">
                        <button type="button" class="emoji-btn" onclick="toggleEmojiPicker('contentEmojiPicker')" title="Add emoji">
                            😀
                        </button>
                        <div id="contentEmojiPicker" class="emoji-picker" data-input="content"></div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="postImage">
                    <i class="fas fa-image"></i> Add Image (Optional)
                </label>
                <div class="file-input-wrapper">
                    <input type="file" 
                           id="postImage" 
                           name="post_image" 
                           accept="image/*">
                    <label for="postImage" class="file-input-label">
                        <i class="fas fa-upload"></i> Choose Image
                    </label>
                </div>
                <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                    Maximum file size: 10MB. Supported formats: JPG, PNG, GIF
                </small>
            </div>
            
            <!-- Image Preview -->
            <div id="imagePreview" class="image-preview"></div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="create_post" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-paper-plane"></i> Post
                </button>
                <a href="home.php" class="btn btn-secondary" style="flex: 1; text-align: center; padding: 12px;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tips Box -->
    <div style="background-color: var(--bg-secondary); padding: 20px; border-radius: var(--border-radius); margin-top: 20px; box-shadow: 0 2px 8px var(--shadow);">
        <h3 style="margin-bottom: 15px; color: var(--text-primary);">
            <i class="fas fa-lightbulb"></i> Tips for Great Posts
        </h3>
        <ul style="color: var(--text-secondary); line-height: 1.8;">
            <li>✨ Be authentic and share your genuine thoughts</li>
            <li>📸 Add images to make your posts more engaging</li>
            <li>💬 Ask questions to start conversations</li>
            <li>🎯 Keep it relevant and respectful</li>
            <li>❤️ Engage with comments on your posts</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
