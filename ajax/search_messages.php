<?php
/**
 * AJAX: Search Conversations
 * Searches for conversations by username
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
if (!isset($_GET['query'])) {
    echo json_encode(['success' => false, 'error' => 'Missing search query']);
    exit();
}

$search_term = trim($_GET['query']);

if (empty($search_term)) {
    // Return all conversations if search is empty
    $conversations = get_user_conversations($user_id);
} else {
    // Search conversations
    $conversations = search_conversations($user_id, $search_term);
}

echo json_encode([
    'success' => true,
    'conversations' => $conversations
]);
?>
