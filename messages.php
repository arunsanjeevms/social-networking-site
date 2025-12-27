<?php
/**
 * Direct Messages Page
 * Real-time messaging interface
 */

session_start();
require_once 'config/database.php';
require_once 'config/messages.php';
require_login();

$page_title = 'Messages';
$user_id = $_SESSION['user_id'];

// Get conversation ID if provided
$conversation_id = isset($_GET['conversation']) ? (int)$_GET['conversation'] : null;
$other_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;

// If user ID provided, get or create conversation
if ($other_user_id && $other_user_id != $user_id) {
    $conversation_id = get_or_create_conversation($user_id, $other_user_id);
    header("Location: messages.php?conversation={$conversation_id}");
    exit();
}

// Get all conversations
$conversations = get_user_conversations($user_id);

// Get active conversation details
$active_conversation = null;
$messages = [];
$other_user = null;

if ($conversation_id) {
    // Get conversation details
    foreach ($conversations as $conv) {
        if ($conv['id'] == $conversation_id) {
            $active_conversation = $conv;
            break;
        }
    }
    
    if ($active_conversation) {
        // Get other user details
        $other_user = [
            'id' => $active_conversation['other_user_id'],
            'username' => $active_conversation['other_username'],
            'profile_image' => $active_conversation['other_profile_image']
        ];
        
        // Get messages
        $messages = get_conversation_messages($conversation_id, $user_id);
        
        // Mark messages as read
        mark_messages_read($conversation_id, $user_id);
    }
}

include 'includes/header.php';
?>

<style>
.messages-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 0;
    height: calc(100vh - 80px);
    max-width: 1400px;
    margin: 0 auto;
    background: var(--bg-primary);
}

.conversations-list {
    background: var(--bg-secondary);
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.conversations-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.conversations-header h2 {
    margin: 0 0 12px 0;
    color: var(--text-primary);
    font-size: 24px;
}

.conversations-search {
    position: relative;
}

.conversations-search input {
    width: 100%;
    padding: 10px 12px 10px 38px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    color: var(--text-primary);
    font-size: 14px;
}

.conversations-search i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.conversations-scroll {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
    position: relative;
}

.conversation-item:hover {
    background: var(--bg-hover);
}

.conversation-item.active {
    background: var(--bg-tertiary);
    border-left: 3px solid var(--accent-teal);
}

.conversation-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conversation-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.conversation-preview {
    font-size: 14px;
    color: var(--text-secondary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.unread-badge {
    position: absolute;
    top: 16px;
    right: 20px;
    background: var(--accent-teal);
    color: #000;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: bold;
}

.chat-container {
    display: flex;
    flex-direction: column;
    background: var(--bg-primary);
}

.chat-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-secondary);
}

.chat-header-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
}

.chat-header-info {
    flex: 1;
}

.chat-header-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 16px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.message {
    display: flex;
    gap: 12px;
    max-width: 70%;
}

.message.sent {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.message-content {
    background: var(--bg-secondary);
    padding: 12px 16px;
    border-radius: 16px;
    color: var(--text-primary);
    word-wrap: break-word;
}

.message.sent .message-content {
    background: var(--accent-teal);
    color: #000;
}

.message-time {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 4px;
    padding: 0 4px;
}

.chat-input-container {
    padding: 16px 24px;
    border-top: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.chat-input-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.chat-input {
    flex: 1;
    padding: 12px 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 24px;
    color: var(--text-primary);
    font-size: 14px;
    resize: none;
    max-height: 120px;
}

.send-button {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--accent-teal);
    color: #000;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
}

.send-button:hover {
    transform: scale(1.1);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-secondary);
    text-align: center;
    padding: 40px;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

@media (max-width: 768px) {
    .messages-container {
        grid-template-columns: 1fr;
    }
    
    .conversations-list {
        display: <?php echo $conversation_id ? 'none' : 'flex'; ?>;
    }
    
    .chat-container {
        display: <?php echo $conversation_id ? 'flex' : 'none'; ?>;
    }
}
</style>

<div class="messages-container">
    <!-- Conversations List -->
    <div class="conversations-list">
        <div class="conversations-header">
            <h2><i class="fas fa-comments"></i> Messages</h2>
            <div class="conversations-search">
                <i class="fas fa-search"></i>
                <input type="text" id="conversationSearch" placeholder="Search conversations...">
            </div>
        </div>
        
        <div class="conversations-scroll" id="conversationsList">
            <?php if (empty($conversations)): ?>
                <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px; display: block;"></i>
                    <p>No messages yet</p>
                    <p style="font-size: 13px;">Start a conversation from a user's profile</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item <?php echo $conv['id'] == $conversation_id ? 'active' : ''; ?>" 
                         onclick="window.location.href='messages.php?conversation=<?php echo $conv['id']; ?>'">
                        <img src="assets/uploads/profiles/<?php echo htmlspecialchars($conv['other_profile_image'] ?? 'default-avatar.png'); ?>" 
                             class="conversation-avatar"
                             onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <span><?php echo htmlspecialchars($conv['other_username']); ?></span>
                                <?php if ($conv['last_message_time']): ?>
                                    <span class="conversation-time">
                                        <?php echo date('M d', strtotime($conv['last_message_time'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($conv['last_message']): ?>
                                <div class="conversation-preview">
                                    <?php 
                                    $prefix = $conv['last_sender_id'] == $user_id ? 'You: ' : '';
                                    echo htmlspecialchars($prefix . substr($conv['last_message'], 0, 50));
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chat Container -->
    <div class="chat-container">
        <?php if ($active_conversation && $other_user): ?>
            <!-- Chat Header -->
            <div class="chat-header">
                <?php if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false): ?>
                    <button onclick="window.location.href='messages.php'" style="background: none; border: none; color: var(--text-primary); font-size: 20px; cursor: pointer; padding: 0; margin-right: 8px;">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                <?php endif; ?>
                <img src="assets/uploads/profiles/<?php echo htmlspecialchars($other_user['profile_image'] ?? 'default-avatar.png'); ?>" 
                     class="chat-header-avatar"
                     onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                <div class="chat-header-info">
                    <div class="chat-header-name"><?php echo htmlspecialchars($other_user['username']); ?></div>
                </div>
                <a href="user_profile.php?id=<?php echo $other_user['id']; ?>" 
                   style="color: var(--text-secondary); text-decoration: none;">
                    <i class="fas fa-info-circle"></i>
                </a>
            </div>
            
            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : ''; ?>" data-message-id="<?php echo $msg['id']; ?>">
                        <img src="assets/uploads/profiles/<?php echo htmlspecialchars($msg['sender_profile_image'] ?? 'default-avatar.png'); ?>" 
                             class="message-avatar"
                             onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                        <div>
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Input -->
            <div class="chat-input-container">
                <form class="chat-input-form" id="messageForm">
                    <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $other_user['id']; ?>">
                    <textarea class="chat-input" 
                              id="messageInput" 
                              name="message" 
                              placeholder="Type a message..." 
                              rows="1"
                              required></textarea>
                    <button type="submit" class="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3 style="margin-bottom: 8px;">Select a conversation</h3>
                <p>Choose a conversation from the left to start messaging</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll to bottom
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Auto-resize textarea
const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    
    // Submit on Enter (Shift+Enter for new line)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('messageForm').dispatchEvent(new Event('submit'));
        }
    });
}

// Send message
const messageForm = document.getElementById('messageForm');
if (messageForm) {
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageText = formData.get('message').trim();
        
        if (!messageText) return;
        
        fetch('ajax/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add message to chat with message ID
                const isSent = true;
                const messageHTML = `
                    <div class="message ${isSent ? 'sent' : ''}" data-message-id="${data.message.id}">
                        <img src="${data.message.sender_profile_image}" class="message-avatar">
                        <div>
                            <div class="message-content">${escapeHtml(data.message.message)}</div>
                            <div class="message-time">Just now</div>
                        </div>
                    </div>
                `;
                chatMessages.insertAdjacentHTML('beforeend', messageHTML);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Clear input
                messageInput.value = '';
                messageInput.style.height = 'auto';
            } else {
                alert('Error sending message');
            }
        })
        .catch(error => console.error('Error:', error));
    });
}

// Search conversations
const searchInput = document.getElementById('conversationSearch');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const items = document.querySelectorAll('.conversation-item');
        
        items.forEach(item => {
            const username = item.querySelector('.conversation-name span').textContent.toLowerCase();
            if (username.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Helper function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-refresh messages every 5 seconds
<?php if ($conversation_id): ?>
setInterval(function() {
    loadNewMessages();
}, 5000);

function loadNewMessages() {
    const lastMessageId = document.querySelector('.message:last-child')?.dataset?.messageId || 0;
    
    fetch(`ajax/get_new_messages.php?conversation=<?php echo $conversation_id; ?>&after=${lastMessageId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const isSent = msg.sender_id == <?php echo $user_id; ?>;
                    const messageHTML = `
                        <div class="message ${isSent ? 'sent' : ''}" data-message-id="${msg.id}">
                            <img src="${msg.sender_profile_image}" class="message-avatar">
                            <div>
                                <div class="message-content">${escapeHtml(msg.message)}</div>
                                <div class="message-time">${formatTime(msg.created_at)}</div>
                            </div>
                        </div>
                    `;
                    chatMessages.insertAdjacentHTML('beforeend', messageHTML);
                });
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
