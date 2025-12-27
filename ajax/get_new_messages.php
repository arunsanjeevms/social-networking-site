<?php
/**
 * AJAX: Get New Messages
 * Fetches new messages after a specific message ID
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
if (!isset($_GET['conversation'])) {
    echo json_encode(['success' => false, 'error' => 'Missing conversation ID']);
    exit();
}

$conversation_id = (int)$_GET['conversation'];
$after_id = isset($_GET['after']) ? (int)$_GET['after'] : 0;

// Verify user is part of the conversation
$stmt = $conn->prepare("
    SELECT id FROM conversations 
    WHERE id = ? AND (user1_id = ? OR user2_id = ?)
");
$stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
$stmt->execute();
$conversation = $stmt->get_result()->fetch_assoc();

if (!$conversation) {
    echo json_encode(['success' => false, 'error' => 'Conversation not found']);
    exit();
}

// Get new messages
$stmt = $conn->prepare("
    SELECT m.id, m.message, m.sender_id, m.created_at,
           u.username as sender_username,
           u.profile_image as sender_profile_image
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.conversation_id = ? AND m.id > ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $conversation_id, $after_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $row['sender_profile_image'] = 'assets/uploads/profiles/' . ($row['sender_profile_image'] ?? 'default-avatar.png');
    $messages[] = $row;
}

// Mark new messages as read if they're for this user
if (!empty($messages)) {
    mark_messages_read($conversation_id, $user_id);
}

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>
