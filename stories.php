<?php
/**
 * Stories Feature
 * Instagram-style stories that disappear after 24 hours
 */

session_start();
require_once 'config/database.php';
require_login();

$page_title = 'Stories';
$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Handle story upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_story'])) {
    if (isset($_FILES['story_image']) && $_FILES['story_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['story_image']['type'];
        $file_size = $_FILES['story_image']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= 10000000) {
            $upload_dir = 'assets/uploads/stories/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['story_image']['name'], PATHINFO_EXTENSION);
            $filename = 'story_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['story_image']['tmp_name'], $upload_path)) {
                $caption = sanitize_input($_POST['caption'] ?? '');
                $stmt = $conn->prepare("INSERT INTO stories (user_id, image, caption) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $filename, $caption);
                
                if ($stmt->execute()) {
                    $success = "Story uploaded successfully!";
                } else {
                    $error = "Error uploading story.";
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid file type or file too large (max 10MB)";
        }
    }
}

// Delete old stories (older than 24 hours)
$conn->query("DELETE FROM stories WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

// Get all users with active stories
$stories_query = "SELECT DISTINCT u.id, u.username, u.profile_image,
                  (SELECT COUNT(*) FROM stories WHERE user_id = u.id AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as story_count
                  FROM users u
                  WHERE EXISTS (SELECT 1 FROM stories WHERE user_id = u.id AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR))
                  ORDER BY u.id = ? DESC, u.username ASC";
$stmt = $conn->prepare($stories_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$users_with_stories = $stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <h1 style="margin-bottom: 30px; color: var(--text-primary);">
        <i class="fas fa-circle-notch"></i> Stories
    </h1>
    
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
    
    <!-- Upload Story -->
    <div class="create-post-box">
        <h3><i class="fas fa-plus-circle"></i> Create Your Story</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Story Image</label>
                <input type="file" name="story_image" accept="image/*" required id="storyImageInput">
            </div>
            <div class="form-group">
                <label>Caption (Optional)</label>
                <input type="text" name="caption" placeholder="Add a caption..." style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-primary); color: var(--text-primary);">
            </div>
            <div id="storyImagePreview" style="display: none; margin: 15px 0;"></div>
            <button type="submit" name="upload_story" class="btn btn-primary">
                <i class="fas fa-upload"></i> Post Story
            </button>
        </form>
    </div>
    
    <!-- Stories List -->
    <h2 style="margin: 30px 0 20px; color: var(--text-primary);">
        <i class="fas fa-fire"></i> Active Stories
    </h2>
    
    <div class="stories-container">
        <?php if ($users_with_stories->num_rows > 0): ?>
            <?php while ($user = $users_with_stories->fetch_assoc()): ?>
                <div class="story-card" onclick="viewStories(<?php echo $user['id']; ?>)">
                    <div class="story-avatar-wrapper">
                        <img src="assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>"
                             onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                        <div class="story-ring"></div>
                    </div>
                    <div class="story-username"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="story-count"><?php echo $user['story_count']; ?> <?php echo $user['story_count'] > 1 ? 'stories' : 'story'; ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="post text-center" style="padding: 40px;">
                <i class="fas fa-image" style="font-size: 48px; color: var(--text-secondary); margin-bottom: 15px;"></i>
                <h3 style="color: var(--text-secondary);">No active stories</h3>
                <p style="color: var(--text-secondary);">Be the first to share a story!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Story Viewer Modal -->
<div id="storyViewer" class="story-viewer">
    <button class="story-close" onclick="closeStoryViewer()">
        <i class="fas fa-times"></i>
    </button>
    <button class="story-nav story-prev" onclick="previousStory()">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button class="story-nav story-next" onclick="nextStory()">
        <i class="fas fa-chevron-right"></i>
    </button>
    
    <div class="story-content">
        <div class="story-header">
            <img id="storyUserAvatar" src="" alt="" class="story-user-avatar">
            <div>
                <div id="storyUsername" class="story-user-name"></div>
                <div id="storyTime" class="story-time"></div>
            </div>
        </div>
        <div class="story-progress">
            <div id="storyProgressBar" class="story-progress-bar"></div>
        </div>
        <img id="storyImage" src="" alt="Story" class="story-image">
        <div id="storyCaption" class="story-caption"></div>
    </div>
</div>

<script>
let currentStories = [];
let currentStoryIndex = 0;
let currentUserId = null;

function viewStories(userId) {
    fetch('ajax/get_stories.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentStories = data.stories;
                currentStoryIndex = 0;
                currentUserId = userId;
                showStory();
                document.getElementById('storyViewer').classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
}

function showStory() {
    if (currentStories.length === 0) return;
    
    const story = currentStories[currentStoryIndex];
    document.getElementById('storyUserAvatar').src = 'assets/uploads/profiles/' + story.profile_image;
    document.getElementById('storyUsername').textContent = story.username;
    document.getElementById('storyTime').textContent = story.time_ago;
    document.getElementById('storyImage').src = 'assets/uploads/stories/' + story.image;
    document.getElementById('storyCaption').textContent = story.caption || '';
    
    animateProgressBar();
}

function animateProgressBar() {
    const progressBar = document.getElementById('storyProgressBar');
    progressBar.style.width = '0%';
    progressBar.style.transition = 'none';
    
    setTimeout(() => {
        progressBar.style.transition = 'width 5s linear';
        progressBar.style.width = '100%';
    }, 50);
    
    setTimeout(() => {
        nextStory();
    }, 5000);
}

function nextStory() {
    if (currentStoryIndex < currentStories.length - 1) {
        currentStoryIndex++;
        showStory();
    } else {
        closeStoryViewer();
    }
}

function previousStory() {
    if (currentStoryIndex > 0) {
        currentStoryIndex--;
        showStory();
    }
}

function closeStoryViewer() {
    document.getElementById('storyViewer').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Image preview for story upload
document.getElementById('storyImageInput').addEventListener('change', function() {
    const preview = document.getElementById('storyImagePreview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.style.display = 'block';
            preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100%; border-radius: var(--radius);">';
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
