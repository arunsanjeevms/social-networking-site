<?php
/**
 * AJAX Like Post Handler
 * 
 * This script handles like/unlike actions via AJAX.
 * Features:
 * - Toggle like/unlike
 * - Return updated like count
 * - JSON response for AJAX
 */

// Start session
session_start();

// Include database connection
require_once '../config/database.php';
require_once '../config/notifications.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if post_id is provided
if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Post ID required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// Check if user already liked this post
$stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();

$liked = false;

if ($result->num_rows > 0) {
    // Unlike: Remove like from database
    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $liked = false;
} else {
    // Like: Add like to database
    $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $liked = true;
    
    // Get post owner to create notification
    $stmt_post = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt_post->bind_param("i", $post_id);
    $stmt_post->execute();
    $post_result = $stmt_post->get_result();
    
    if ($post_result->num_rows > 0) {
        $post_owner = $post_result->fetch_assoc()['user_id'];
        // Create notification for post owner
        create_notification($post_owner, $user_id, 'like', $post_id, 'liked your post');
    }
    $stmt_post->close();
}

// Get updated like count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$like_count = $row['count'];

// Return JSON response
echo json_encode([
    'success' => true,
    'liked' => $liked,
    'like_count' => $like_count
]);

$stmt->close();
$conn->close();
?>
