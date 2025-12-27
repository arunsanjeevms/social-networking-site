<?php
/**
 * AJAX: Send Message
 * Sends a new message in a conversation
 */

session_start();
require_once '../config/database.php';
require_once '../config/messages.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate input
if (!isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$receiver_id = (int)$_POST['receiver_id'];
$message = trim($_POST['message']);

// Validate data
if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit();
}

if ($receiver_id == $user_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot send message to yourself']);
    exit();
}

// Check if receiver exists
$stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$receiver = $stmt->get_result()->fetch_assoc();

if (!$receiver) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

// Send message
$message_id = send_message($user_id, $receiver_id, $message);

if ($message_id) {
    // Get sender info for response
    $stmt = $conn->prepare("SELECT username, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sender = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $message_id,
            'message' => $message,
            'sender_id' => $user_id,
            'sender_username' => $sender['username'],
            'sender_profile_image' => 'assets/uploads/profiles/' . ($sender['profile_image'] ?? 'default-avatar.png'),
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
?>
