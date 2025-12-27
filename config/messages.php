<?php
/**
 * Direct Messages Helper Functions
 */

require_once 'database.php';

/**
 * Get or create conversation between two users
 */
function get_or_create_conversation($user1_id, $user2_id) {
    global $conn;
    
    // Ensure user1_id is always smaller for consistency
    if ($user1_id > $user2_id) {
        $temp = $user1_id;
        $user1_id = $user2_id;
        $user2_id = $temp;
    }
    
    // Check if conversation exists
    $stmt = $conn->prepare("SELECT id FROM conversations 
                            WHERE user1_id = ? AND user2_id = ?");
    $stmt->bind_param("ii", $user1_id, $user2_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conversation_id = $result->fetch_assoc()['id'];
        $stmt->close();
        return $conversation_id;
    }
    $stmt->close();
    
    // Create new conversation
    $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user1_id, $user2_id);
    $stmt->execute();
    $conversation_id = $conn->insert_id;
    $stmt->close();
    
    return $conversation_id;
}

/**
 * Send a message
 */
function send_message($sender_id, $receiver_id, $message) {
    global $conn;
    
    // Get or create conversation
    $conversation_id = get_or_create_conversation($sender_id, $receiver_id);
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $conversation_id, $sender_id, $receiver_id, $message);
    $success = $stmt->execute();
    $message_id = $conn->insert_id;
    $stmt->close();
    
    if ($success) {
        // Update conversation's last message
        $stmt = $conn->prepare("UPDATE conversations 
                                SET last_message_id = ?, last_message_time = NOW() 
                                WHERE id = ?");
        $stmt->bind_param("ii", $message_id, $conversation_id);
        $stmt->execute();
        $stmt->close();
    }
    
    return $success ? $message_id : false;
}

/**
 * Get conversation messages
 */
function get_conversation_messages($conversation_id, $user_id, $limit = 50) {
    global $conn;
    
    // Verify user is part of conversation
    $stmt = $conn->prepare("SELECT id FROM conversations 
                            WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return [];
    }
    $stmt->close();
    
    // Get messages
    $stmt = $conn->prepare("SELECT m.*, 
                            sender.username as sender_username,
                            sender.profile_image as sender_profile_image
                            FROM messages m
                            JOIN users sender ON m.sender_id = sender.id
                            WHERE m.conversation_id = ?
                            ORDER BY m.created_at DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $conversation_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    return array_reverse($messages);
}

/**
 * Get user's conversations
 */
function get_user_conversations($user_id, $limit = 20) {
    global $conn;
    
    $query = "SELECT c.*,
              CASE 
                  WHEN c.user1_id = ? THEN u2.id
                  ELSE u1.id
              END as other_user_id,
              CASE 
                  WHEN c.user1_id = ? THEN u2.username
                  ELSE u1.username
              END as other_username,
              CASE 
                  WHEN c.user1_id = ? THEN u2.profile_image
                  ELSE u1.profile_image
              END as other_profile_image,
              m.message as last_message,
              m.sender_id as last_sender_id,
              (SELECT COUNT(*) FROM messages 
               WHERE conversation_id = c.id 
               AND receiver_id = ? 
               AND is_read = 0) as unread_count
              FROM conversations c
              JOIN users u1 ON c.user1_id = u1.id
              JOIN users u2 ON c.user2_id = u2.id
              LEFT JOIN messages m ON c.last_message_id = m.id
              WHERE c.user1_id = ? OR c.user2_id = ?
              ORDER BY c.last_message_time DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    $stmt->close();
    
    return $conversations;
}

/**
 * Mark messages as read
 */
function mark_messages_read($conversation_id, $user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE messages 
                            SET is_read = 1, read_at = NOW() 
                            WHERE conversation_id = ? 
                            AND receiver_id = ? 
                            AND is_read = 0");
    $stmt->bind_param("ii", $conversation_id, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Get unread message count
 */
function get_unread_messages_count($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count 
                            FROM messages 
                            WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return (int)$result['unread_count'];
}

/**
 * Delete conversation
 */
function delete_conversation($conversation_id, $user_id) {
    global $conn;
    
    // Verify user is part of conversation
    $stmt = $conn->prepare("SELECT id FROM conversations 
                            WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        return false;
    }
    $stmt->close();
    
    // Delete conversation (messages will be deleted by CASCADE)
    $stmt = $conn->prepare("DELETE FROM conversations WHERE id = ?");
    $stmt->bind_param("i", $conversation_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Search conversations
 */
function search_conversations($user_id, $search_term) {
    global $conn;
    
    $search_param = "%{$search_term}%";
    
    $query = "SELECT DISTINCT c.*,
              CASE 
                  WHEN c.user1_id = ? THEN u2.id
                  ELSE u1.id
              END as other_user_id,
              CASE 
                  WHEN c.user1_id = ? THEN u2.username
                  ELSE u1.username
              END as other_username,
              CASE 
                  WHEN c.user1_id = ? THEN u2.profile_image
                  ELSE u1.profile_image
              END as other_profile_image
              FROM conversations c
              JOIN users u1 ON c.user1_id = u1.id
              JOIN users u2 ON c.user2_id = u2.id
              WHERE (c.user1_id = ? OR c.user2_id = ?)
              AND (u1.username LIKE ? OR u2.username LIKE ?)
              ORDER BY c.last_message_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiss", $user_id, $user_id, $user_id, $user_id, $user_id, 
                      $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    $stmt->close();
    
    return $conversations;
}
