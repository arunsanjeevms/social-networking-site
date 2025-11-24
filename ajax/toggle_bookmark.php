<?php
/**
 * Toggle Bookmark AJAX Endpoint
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Post ID required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// Check if already bookmarked
$check_query = "SELECT id FROM bookmarks WHERE user_id = ? AND post_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $user_id, $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Remove bookmark
    $delete_query = "DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $user_id, $post_id);
    $delete_stmt->execute();
    
    echo json_encode(['success' => true, 'bookmarked' => false, 'message' => 'Bookmark removed']);
} else {
    // Add bookmark
    $insert_query = "INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $user_id, $post_id);
    $insert_stmt->execute();
    
    echo json_encode(['success' => true, 'bookmarked' => true, 'message' => 'Post bookmarked']);
}
