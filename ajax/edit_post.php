<?php
/**
 * AJAX Edit Post Handler
 * 
 * This script handles post editing functionality.
 * Only the post owner can edit their posts.
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

// Check if required data is provided
if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);
$content = sanitize_input($_POST['content']);

// Validate content
if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
    exit();
}

// Check if user owns the post
$stmt = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
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

// Update post
$stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ?");
$stmt->bind_param("si", $content, $post_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Post updated successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating post']);
}

$stmt->close();
$conn->close();
?>
