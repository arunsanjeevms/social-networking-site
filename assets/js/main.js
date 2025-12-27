/**
 * =====================================================
 * SOCIAL NETWORK - MAIN JAVASCRIPT
 * =====================================================
 * 
 * This file contains all JavaScript functionality including:
 * - Dark theme toggle with localStorage persistence
 * - AJAX for likes and comments
 * - Image preview functionality
 * - Form validations
 */

// =====================================================
// THEME TOGGLE FUNCTIONALITY
// =====================================================

/**
 * Initialize theme on page load
 * Check localStorage for saved theme preference
 */
document.addEventListener('DOMContentLoaded', function() {
    // Load saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        updateThemeIcon();
    }
    
    // Add event listener to theme toggle button if it exists
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // Initialize image preview functionality
    initImagePreview();
    
    // Set active nav link
    setActiveNavLink();
});

/**
 * Toggle between light and dark theme
 */
function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    
    // Save preference to localStorage
    if (document.body.classList.contains('dark-theme')) {
        localStorage.setItem('theme', 'dark');
    } else {
        localStorage.setItem('theme', 'light');
    }
    
    updateThemeIcon();
}

/**
 * Update theme toggle icon based on current theme
 */
function updateThemeIcon() {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        if (document.body.classList.contains('dark-theme')) {
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }
    }
}

// =====================================================
// IMAGE PREVIEW FUNCTIONALITY
// =====================================================

/**
 * Initialize image preview for file inputs
 */
function initImagePreview() {
    const imageInput = document.getElementById('postImage');
    const profileInput = document.getElementById('profileImage');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            previewImage(this, 'imagePreview');
        });
    }
    
    if (profileInput) {
        profileInput.addEventListener('change', function() {
            previewImage(this, 'profileImagePreview');
        });
    }
}

/**
 * Preview selected image before upload
 * @param {HTMLInputElement} input - File input element
 * @param {string} previewId - ID of preview container
 */
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            if (preview) {
                preview.style.display = 'block';
                const img = preview.querySelector('img') || document.createElement('img');
                img.src = e.target.result;
                if (!preview.querySelector('img')) {
                    preview.appendChild(img);
                }
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// =====================================================
// LIKE POST FUNCTIONALITY (AJAX)
// =====================================================

/**
 * Toggle like on a post
 * @param {number} postId - ID of the post to like/unlike
 * @param {HTMLElement} button - The like button element
 */
function toggleLike(postId, button) {
    // Create FormData for AJAX request
    const formData = new FormData();
    formData.append('post_id', postId);
    
    // Send AJAX request
    fetch('ajax/like_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update like count
            const likeCount = button.querySelector('.like-count');
            likeCount.textContent = data.like_count;
            
            // Toggle liked class
            if (data.liked) {
                button.classList.add('liked');
                button.querySelector('i').className = 'fas fa-heart';
            } else {
                button.classList.remove('liked');
                button.querySelector('i').className = 'far fa-heart';
            }
        } else {
            alert(data.message || 'Error liking post');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing like');
    });
}

// =====================================================
// COMMENT FUNCTIONALITY (AJAX)
// =====================================================

/**
 * Add a comment to a post
 * @param {Event} event - Form submit event
 * @param {number} postId - ID of the post to comment on
 */
function addComment(event, postId) {
    event.preventDefault();
    
    const form = event.target;
    const input = form.querySelector('input[name="comment"]');
    const comment = input.value.trim();
    
    // Validate comment
    if (comment === '') {
        alert('Please enter a comment');
        return;
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('comment', comment);
    
    // Send AJAX request
    fetch('ajax/add_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new comment to the DOM
            addCommentToDOM(postId, data.comment);
            
            // Clear input
            input.value = '';
            
            // Update comment count
            updateCommentCount(postId, 1);
        } else {
            alert(data.message || 'Error adding comment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding comment');
    });
}

/**
 * Add comment to the DOM
 * @param {number} postId - ID of the post
 * @param {Object} comment - Comment data object
 */
function addCommentToDOM(postId, comment) {
    const commentsSection = document.querySelector(`#post-${postId} .comments-list`);
    
    if (!commentsSection) return;
    
    // Create comment HTML
    const commentHTML = `
        <div class="comment">
            <img src="assets/uploads/profiles/${comment.profile_image}" alt="${comment.username}" class="comment-avatar">
            <div class="comment-content">
                <div class="comment-author">${comment.username}</div>
                <div class="comment-text">${comment.comment}</div>
                <div class="comment-time">Just now</div>
            </div>
        </div>
    `;
    
    // Insert comment at the beginning
    commentsSection.insertAdjacentHTML('afterbegin', commentHTML);
}

/**
 * Update comment count display
 * @param {number} postId - ID of the post
 * @param {number} change - Change in count (+1 or -1)
 */
function updateCommentCount(postId, change) {
    const commentBtn = document.querySelector(`#post-${postId} .comment-btn .comment-count`);
    if (commentBtn) {
        const currentCount = parseInt(commentBtn.textContent) || 0;
        commentBtn.textContent = currentCount + change;
    }
}

// =====================================================
// NAVIGATION ACTIVE LINK
// =====================================================

/**
 * Set active class on current page nav link
 */
function setActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage) {
            link.classList.add('active');
        }
    });
}

// =====================================================
// FORM VALIDATION
// =====================================================

/**
 * Validate login form
 */
function validateLoginForm(event) {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    if (email === '' || password === '') {
        alert('Please fill in all fields');
        event.preventDefault();
        return false;
    }
    
    return true;
}

/**
 * Validate signup form
 */
function validateSignupForm(event) {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (username === '' || email === '' || password === '' || confirmPassword === '') {
        alert('Please fill in all fields');
        event.preventDefault();
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        event.preventDefault();
        return false;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        event.preventDefault();
        return false;
    }
    
    return true;
}

// =====================================================
// AUTO-HIDE ALERTS
// =====================================================

/**
 * Auto-hide alert messages after 5 seconds
 */
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// =====================================================
// SEARCH USERS FUNCTIONALITY
// =====================================================

/**
 * Initialize user search
 */
function initUserSearch() {
    const searchBox = document.getElementById('userSearch');
    if (!searchBox) return;
    
    let searchTimeout;
    
    searchBox.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            document.getElementById('searchResults').classList.remove('active');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchUsers(query);
        }, 300);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            document.getElementById('searchResults').classList.remove('active');
        }
    });
}

/**
 * Search for users
 * @param {string} query - Search query
 */
function searchUsers(query) {
    const formData = new FormData();
    formData.append('query', query);
    
    fetch('ajax/search_users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySearchResults(data.users);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

/**
 * Display search results
 * @param {Array} users - Array of user objects
 */
function displaySearchResults(users) {
    const resultsContainer = document.getElementById('searchResults');
    
    if (users.length === 0) {
        resultsContainer.innerHTML = '<div style="padding: 15px; text-align: center; color: var(--text-secondary);">No users found</div>';
        resultsContainer.classList.add('active');
        return;
    }
    
    let html = '';
    users.forEach(user => {
        html += `
            <div class="search-result-item" onclick="window.location.href='user_profile.php?id=${user.id}'">
                <img src="assets/uploads/profiles/${user.profile_image}" alt="${user.username}" class="search-result-avatar" onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                <div class="search-result-info">
                    <div class="search-result-name">${user.username}</div>
                    <div class="search-result-username">@${user.username}</div>
                </div>
            </div>
        `;
    });
    
    resultsContainer.innerHTML = html;
    resultsContainer.classList.add('active');
}

// =====================================================
// POST MENU (Edit/Delete)
// =====================================================

/**
 * Toggle post menu
 * @param {number} postId - ID of the post
 */
function togglePostMenu(postId) {
    const menu = document.getElementById(`post-menu-${postId}`);
    
    // Close all other menus
    document.querySelectorAll('.post-menu-dropdown').forEach(m => {
        if (m.id !== `post-menu-${postId}`) {
            m.classList.remove('active');
        }
    });
    
    menu.classList.toggle('active');
}

/**
 * Edit post
 * @param {number} postId - ID of the post to edit
 */
function editPost(postId) {
    const postContent = document.querySelector(`#post-${postId} .post-content`).textContent.trim();
    const newContent = prompt('Edit your post:', postContent);
    
    if (newContent && newContent !== postContent) {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('content', newContent);
        
        fetch('ajax/edit_post.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`#post-${postId} .post-content`).textContent = newContent;
                showNotification('Post updated successfully!', 'success');
            } else {
                showNotification(data.message || 'Error updating post', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating post', 'error');
        });
    }
    
    togglePostMenu(postId);
}

/**
 * Delete post
 * @param {number} postId - ID of the post to delete
 */
function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('post_id', postId);
    
    fetch('ajax/delete_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const postElement = document.getElementById(`post-${postId}`);
            postElement.style.opacity = '0';
            postElement.style.transform = 'translateX(-100%)';
            setTimeout(() => postElement.remove(), 300);
            showNotification('Post deleted successfully!', 'success');
        } else {
            showNotification(data.message || 'Error deleting post', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting post', 'error');
    });
}

// =====================================================
// IMAGE LIGHTBOX
// =====================================================

/**
 * Open image in lightbox
 * @param {string} imageUrl - URL of the image
 */
function openLightbox(imageUrl) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    
    lightboxImage.src = imageUrl;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Close lightbox
 */
function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    lightbox.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// =====================================================
// EMOJI PICKER
// =====================================================

const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐', '😕', '😟', '🙁', '☹️', '😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨', '😰', '😥', '😢', '😭', '😱', '😖', '😣', '😞', '😓', '😩', '😫', '🥱', '😤', '😡', '😠', '🤬', '👍', '👎', '👏', '🙌', '👐', '🤝', '🙏', '✌️', '🤞', '🤟', '🤘', '🤙', '👌', '🤏', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐', '🖖', '👋', '🤙', '💪', '🦾', '🖕', '✍️', '🙏', '💅', '🤳', '💃', '🕺', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣', '⚫', '⚪', '🟤'];

/**
 * Toggle emoji picker
 * @param {string} pickerId - ID of the emoji picker
 */
function toggleEmojiPicker(pickerId) {
    const picker = document.getElementById(pickerId);
    picker.classList.toggle('active');
}

/**
 * Insert emoji into input
 * @param {string} emoji - Emoji to insert
 * @param {string} inputId - ID of the input field
 */
function insertEmoji(emoji, inputId) {
    const input = document.getElementById(inputId);
    const cursorPos = input.selectionStart;
    const textBefore = input.value.substring(0, cursorPos);
    const textAfter = input.value.substring(cursorPos);
    
    input.value = textBefore + emoji + textAfter;
    input.focus();
    input.selectionStart = input.selectionEnd = cursorPos + emoji.length;
}

/**
 * Initialize emoji pickers
 */
function initEmojiPickers() {
    const pickers = document.querySelectorAll('.emoji-picker');
    pickers.forEach(picker => {
        let html = '<div class="emoji-grid">';
        emojis.forEach(emoji => {
            const inputId = picker.getAttribute('data-input');
            html += `<span class="emoji-item" onclick="insertEmoji('${emoji}', '${inputId}')">${emoji}</span>`;
        });
        html += '</div>';
        picker.innerHTML = html;
    });
}

// =====================================================
// SHARE POST
// =====================================================

/**
 * Open share modal
 * @param {number} postId - ID of the post to share
 */
function openShareModal(postId) {
    const modal = document.getElementById('shareModal');
    modal.setAttribute('data-post-id', postId);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Close share modal
 */
function closeShareModal() {
    const modal = document.getElementById('shareModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

/**
 * Share post to platform
 * @param {string} platform - Platform to share to
 */
function sharePost(platform) {
    const modal = document.getElementById('shareModal');
    const postId = modal.getAttribute('data-post-id');
    const postUrl = `${window.location.origin}/social/home.php#post-${postId}`;
    const text = 'Check out this post!';
    
    let shareUrl = '';
    
    switch(platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(postUrl)}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + postUrl)}`;
            break;
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${encodeURIComponent(postUrl)}&text=${encodeURIComponent(text)}`;
            break;
        case 'copy':
            navigator.clipboard.writeText(postUrl).then(() => {
                showNotification('Link copied to clipboard!', 'success');
            });
            closeShareModal();
            return;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        closeShareModal();
    }
}

// =====================================================
// NOTIFICATIONS
// =====================================================

/**
 * Show notification
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, info)
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'error'}`;
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '10000';
    notification.style.minWidth = '250px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// =====================================================
// INITIALIZE ALL FEATURES
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    initUserSearch();
    initEmojiPickers();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.post-menu')) {
            document.querySelectorAll('.post-menu-dropdown').forEach(menu => {
                menu.classList.remove('active');
            });
        }
        
        if (!e.target.closest('.emoji-picker-container')) {
            document.querySelectorAll('.emoji-picker').forEach(picker => {
                picker.classList.remove('active');
            });
        }
    });
    
    // Lightbox keyboard controls
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
            closeShareModal();
        }
    });
});

// =====================================================
// BOOKMARK FUNCTIONALITY
// =====================================================

/**
 * Toggle bookmark on a post
 * @param {number} postId - ID of the post
 * @param {HTMLElement} button - The bookmark button
 */
function toggleBookmark(postId, button) {
    const formData = new FormData();
    formData.append('post_id', postId);
    
    fetch('ajax/toggle_bookmark.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.bookmarked) {
                button.classList.add('bookmarked');
                button.innerHTML = '<i class="fas fa-bookmark"></i>';
                showNotification('Post bookmarked!', 'success');
            } else {
                button.classList.remove('bookmarked');
                button.innerHTML = '<i class="far fa-bookmark"></i>';
                showNotification('Bookmark removed', 'success');
                
                // If on bookmarks page, remove the post
                if (window.location.pathname.includes('bookmarks.php')) {
                    const post = document.getElementById(`post-${postId}`);
                    post.style.opacity = '0';
                    post.style.transform = 'translateX(-100%)';
                    setTimeout(() => post.remove(), 300);
                }
            }
        } else {
            showNotification(data.message || 'Error', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error bookmarking post', 'error');
    });
}

// =====================================================
// REACTIONS SYSTEM
// =====================================================

const reactions = ['❤️', '😂', '😮', '😢', '😡', '👍'];

/**
 * Toggle reactions picker
 * @param {number} postId - ID of the post
 */
function toggleReactions(postId) {
    const container = document.getElementById(`reactions-${postId}`);
    container.classList.toggle('active');
}

/**
 * Add reaction to post
 * @param {number} postId - ID of the post
 * @param {string} reaction - The reaction emoji
 */
function addReaction(postId, reaction) {
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('reaction', reaction);
    
    fetch('ajax/add_reaction.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateReactionDisplay(postId, data.reactions);
            showNotification('Reaction added!', 'success');
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Update reaction display
 * @param {number} postId - ID of the post
 * @param {Object} reactions - Reactions data
 */
function updateReactionDisplay(postId, reactions) {
    const summary = document.getElementById(`reaction-summary-${postId}`);
    if (summary && reactions) {
        let html = '';
        for (const [emoji, count] of Object.entries(reactions)) {
            html += `<span>${emoji} ${count}</span>`;
        }
        summary.innerHTML = html;
    }
}

// =====================================================
// NOTIFICATIONS
// =====================================================

/**
 * Toggle notifications dropdown
 */
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('active');
    
    // Mark as read when opened
    if (dropdown.classList.contains('active')) {
        markNotificationsAsRead();
    }
}

/**
 * Mark all notifications as read
 */
function markNotificationsAsRead() {
    fetch('ajax/get_notifications.php?action=mark_all_read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.style.display = 'none';
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Load notifications
 */
function loadNotifications() {
    fetch('ajax/get_notifications.php?action=fetch')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Display notifications
 * @param {Array} notifications - Array of notification objects
 */
function displayNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--text-secondary);">No notifications yet</div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        // Safely get values with fallbacks
        const username = notif.from_username || 'Unknown User';
        const profileImage = notif.from_profile_image || 'default-avatar.png';
        const type = notif.type || 'like';
        const isRead = notif.is_read || 0;
        const createdAt = notif.created_at || new Date().toISOString();
        const postId = notif.post_id;
        const fromUserId = notif.from_user_id;
        
        const icon = getNotificationIcon(type);
        const message = getNotificationMessageFromData(username, type);
        const timeAgo = getTimeAgo(createdAt);
        const link = postId ? `home.php#post-${postId}` : `user_profile.php?id=${fromUserId}`;
        const unreadClass = isRead == 0 ? 'unread' : '';
        
        html += `
            <div class="notification-item ${unreadClass}" onclick="window.location.href='${link}'">
                <img src="assets/uploads/profiles/${profileImage}" 
                     class="notification-avatar"
                     onerror="this.src='assets/uploads/profiles/default-avatar.png'">
                <div class="notification-content">
                    <div class="notification-text">
                        ${icon} ${message}
                    </div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    });
    
    html += `
        <div style="padding: 12px; text-align: center; border-top: 1px solid var(--border-color);">
            <button onclick="markNotificationsAsRead(); event.stopPropagation();" 
                    class="btn btn-sm" 
                    style="background: var(--bg-tertiary); padding: 6px 16px;">
                <i class="fas fa-check-double"></i> Mark All Read
            </button>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Get notification icon HTML
 */
function getNotificationIcon(type) {
    const icons = {
        'like': '<i class="fas fa-heart" style="color: #f43f5e;"></i>',
        'comment': '<i class="fas fa-comment" style="color: #3b82f6;"></i>',
        'follow': '<i class="fas fa-user-plus" style="color: #10b981;"></i>',
        'mention': '<i class="fas fa-at" style="color: #8b5cf6;"></i>'
    };
    return icons[type] || '<i class="fas fa-bell"></i>';
}

/**
 * Get notification message from data
 */
function getNotificationMessageFromData(username, type) {
    const user = `<strong>${username}</strong>`;
    
    switch (type) {
        case 'like':
            return `${user} liked your post`;
        case 'comment':
            return `${user} commented on your post`;
        case 'follow':
            return `${user} started following you`;
        case 'mention':
            return `${user} mentioned you in a post`;
        default:
            return `${user} interacted with your content`;
    }
}

/**
 * Get time ago string
 */
function getTimeAgo(timestamp) {
    const now = new Date();
    const past = new Date(timestamp);
    const seconds = Math.floor((now - past) / 1000);
    
    if (seconds < 60) return 'Just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    
    return past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

/**
 * Update notification badge
 * @param {number} count - Unread count
 */
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.notification-bell')) {
        loadNotifications();
        // Refresh every 30 seconds
        setInterval(loadNotifications, 30000);
    }
});

// =====================================================
// LOADING SKELETON
// =====================================================

/**
 * Show loading skeleton
 */
function showLoadingSkeleton() {
    return `
        <div class="post skeleton" style="padding: 20px;">
            <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                <div class="skeleton skeleton-avatar"></div>
                <div style="flex: 1;">
                    <div class="skeleton skeleton-line short"></div>
                    <div class="skeleton skeleton-line short" style="width: 40%;"></div>
                </div>
            </div>
            <div class="skeleton skeleton-line long"></div>
            <div class="skeleton skeleton-line medium"></div>
            <div class="skeleton skeleton-line short"></div>
        </div>
    `;
}

// =====================================================
// SMOOTH SCROLL
// =====================================================

/**
 * Smooth scroll for anchor links
 */
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-bell')) {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.remove('active');
        }
    });
});

// =====================================================
// VIDEO & IMAGE UPLOAD HANDLING
// =====================================================

/**
 * Handle media upload (image or video)
 * Ensures only one media type is selected at a time
 * @param {HTMLInputElement} input - File input element
 * @param {string} type - 'image' or 'video'
 */
function handleMediaUpload(input, type) {
    const imageInput = document.getElementById('postImage');
    const videoInput = document.getElementById('postVideo');
    const imagePreview = document.getElementById('imagePreview');
    const videoPreview = document.getElementById('videoPreview');
    
    if (type === 'image' && input.files && input.files[0]) {
        // Clear video if image is selected
        if (videoInput) videoInput.value = '';
        if (videoPreview) {
            videoPreview.style.display = 'none';
            videoPreview.innerHTML = '';
        }
        
        // Show image preview
        const reader = new FileReader();
        reader.onload = function(e) {
            if (imagePreview) {
                imagePreview.style.display = 'block';
                imagePreview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" style="max-width: 100%; border-radius: 8px;">
                    <button type="button" onclick="clearMediaPreview('image')" 
                            style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); 
                            color: white; border: none; border-radius: 50%; width: 30px; height: 30px; 
                            cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            }
        };
        reader.readAsDataURL(input.files[0]);
        
    } else if (type === 'video' && input.files && input.files[0]) {
        // Clear image if video is selected
        if (imageInput) imageInput.value = '';
        if (imagePreview) {
            imagePreview.style.display = 'none';
            imagePreview.innerHTML = '';
        }
        
        // Show video preview
        const reader = new FileReader();
        reader.onload = function(e) {
            if (videoPreview) {
                videoPreview.style.display = 'block';
                videoPreview.innerHTML = `
                    <div style="position: relative;">
                        <video controls style="max-width: 100%; border-radius: 8px; background: #000;">
                            <source src="${e.target.result}" type="${input.files[0].type}">
                            Your browser does not support the video tag.
                        </video>
                        <button type="button" onclick="clearMediaPreview('video')" 
                                style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); 
                                color: white; border: none; border-radius: 50%; width: 30px; height: 30px; 
                                cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Clear media preview
 * @param {string} type - 'image' or 'video'
 */
function clearMediaPreview(type) {
    if (type === 'image') {
        const imageInput = document.getElementById('postImage');
        const imagePreview = document.getElementById('imagePreview');
        if (imageInput) imageInput.value = '';
        if (imagePreview) {
            imagePreview.style.display = 'none';
            imagePreview.innerHTML = '';
        }
    } else if (type === 'video') {
        const videoInput = document.getElementById('postVideo');
        const videoPreview = document.getElementById('videoPreview');
        if (videoInput) videoInput.value = '';
        if (videoPreview) {
            videoPreview.style.display = 'none';
            videoPreview.innerHTML = '';
        }
    }
}
