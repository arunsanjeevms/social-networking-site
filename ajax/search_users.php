<?php
/**
 * AJAX Search Users Handler
 * 
 * This script handles user search functionality.
 * Returns matching users based on username or email.
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

// Check if query is provided
if (!isset($_POST['query'])) {
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit();
}

$query = sanitize_input($_POST['query']);
$current_user_id = $_SESSION['user_id'];

// Search for users (exclude current user)
$search_pattern = "%{$query}%";
$stmt = $conn->prepare("SELECT id, username, email, profile_image 
                        FROM users 
                        WHERE (username LIKE ? OR email LIKE ?) 
                        AND id != ? 
                        LIMIT 10");
$stmt->bind_param("ssi", $search_pattern, $search_pattern, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'username' => $row['username'],
        'email' => $row['email'],
        'profile_image' => $row['profile_image']
    ];
}

echo json_encode([
    'success' => true,
    'users' => $users
]);

$stmt->close();
$conn->close();
?>
