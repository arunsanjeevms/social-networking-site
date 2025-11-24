<?php
/**
 * AJAX Delete Post Handler
 * 
 * This script handles post deletion functionality.
 * Only the post owner can delete their posts.
 */

// Start session
session_start();

// Include database connection
require_once '../config/database.php';

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

// Check if user owns the post
$stmt = $conn->prepare("SELECT user_id, image FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit();
}

$post = $result->fetch_assoc();

if ($post['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Delete post image if exists
if (!empty($post['image'])) {
    $image_path = '../assets/uploads/posts/' . $post['image'];
    if (file_exists($image_path)) {
        @unlink($image_path);
    }
}

// Delete post (cascade delete will remove likes and comments)
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Post deleted successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting post']);
}

$stmt->close();
$conn->close();
?>
