<?php
/**
 * Get Stories AJAX Endpoint
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

$user_id = intval($_GET['user_id']);

// Get user's stories from last 24 hours
$query = "SELECT s.*, u.username, u.profile_image 
          FROM stories s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.user_id = ? AND s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
          ORDER BY s.created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$stories = [];
while ($row = $result->fetch_assoc()) {
    $time_ago = time_ago($row['created_at']);
    $stories[] = [
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'username' => $row['username'],
        'profile_image' => $row['profile_image'],
        'image' => $row['image'],
        'caption' => $row['caption'],
        'created_at' => $row['created_at'],
        'time_ago' => $time_ago
    ];
}

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $hours = round($time_difference / 3600);
    
    if ($hours < 1) return "Just now";
    if ($hours == 1) return "1 hour ago";
    return "$hours hours ago";
}

echo json_encode(['success' => true, 'stories' => $stories]);
