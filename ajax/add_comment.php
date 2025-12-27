<?php
/**
 * AJAX Add Comment Handler
 * 
 * This script handles adding comments to posts via AJAX.
 * Features:
 * - Insert comment into database
 * - Return comment data for DOM insertion
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

// Check if required data is provided
if (!isset($_POST['post_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);
$comment = sanitize_input($_POST['comment']);

// Validate comment
if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit();
}

// Insert comment into database
$stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $post_id, $comment);

if ($stmt->execute()) {
    // Get post owner to create notification
    $stmt_post = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt_post->bind_param("i", $post_id);
    $stmt_post->execute();
    $post_result = $stmt_post->get_result();
    
    if ($post_result->num_rows > 0) {
        $post_owner = $post_result->fetch_assoc()['user_id'];
        // Create notification for post owner
        create_notification($post_owner, $user_id, 'comment', $post_id, 'commented on your post');
    }
    $stmt_post->close();
    
    // Get user information for response
    $stmt = $conn->prepare("SELECT username, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Return success response with comment data
    echo json_encode([
        'success' => true,
        'comment' => [
            'username' => $user['username'],
            'profile_image' => $user['profile_image'],
            'comment' => $comment
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error adding comment']);
}

$stmt->close();
$conn->close();
?>
