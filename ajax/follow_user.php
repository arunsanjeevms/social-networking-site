<?php
/**
 * Follow User AJAX Endpoint
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

$follower_id = $_SESSION['user_id'];
$following_id = intval($_POST['user_id']);

// Check if already following
$check_query = "SELECT id FROM followers WHERE follower_id = ? AND following_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $follower_id, $following_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Unfollow
    $delete_query = "DELETE FROM followers WHERE follower_id = ? AND following_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $follower_id, $following_id);
    $delete_stmt->execute();
    
    echo json_encode(['success' => true, 'following' => false, 'message' => 'Unfollowed successfully']);
} else {
    // Follow
    $insert_query = "INSERT INTO followers (follower_id, following_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $follower_id, $following_id);
    $insert_stmt->execute();
    
    echo json_encode(['success' => true, 'following' => true, 'message' => 'Following successfully']);
}
