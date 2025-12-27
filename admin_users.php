<?php
/**
 * Admin User Management
 * Block/unblock users, manage admin roles
 */

session_start();
require_once 'config/database.php';
require_once 'config/admin_auth.php';
require_admin();

$page_title = 'User Management';
$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
        switch ($action) {
            case 'block':
                $reason = sanitize_input($_POST['reason'] ?? 'No reason provided');
                $stmt = $conn->prepare("UPDATE users SET status = 'blocked', blocked_at = NOW(), blocked_reason = ? WHERE id = ?");
                $stmt->bind_param("si", $reason, $user_id);
                if ($stmt->execute()) {
                    log_admin_action('block_user', 'user', $user_id, ['reason' => $reason]);
                    $success = "User blocked successfully";
                } else {
                    $error = "Failed to block user";
                }
                $stmt->close();
                break;
                
            case 'unblock':
                $stmt = $conn->prepare("UPDATE users SET status = 'active', blocked_at = NULL, blocked_reason = NULL WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    log_admin_action('unblock_user', 'user', $user_id);
                    $success = "User unblocked successfully";
                } else {
                    $error = "Failed to unblock user";
                }
                $stmt->close();
                break;
                
            case 'make_admin':
                $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    log_admin_action('make_admin', 'user', $user_id);
                    $success = "User promoted to admin";
                } else {
                    $error = "Failed to make user admin";
                }
                $stmt->close();
                break;
                
            case 'remove_admin':
                $stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    log_admin_action('remove_admin', 'user', $user_id);
                    $success = "Admin privileges removed";
                } else {
                    $error = "Failed to remove admin";
                }
                $stmt->close();
                break;
        }
    } else {
        $error = "Invalid user or cannot modify your own account";
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "SELECT id, username, email, profile_image, bio, status, is_admin, is_verified, created_at, blocked_at, blocked_reason FROM users $where_sql ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'includes/header.php';
?>

<div class="container" style="max-width: 1400px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: var(--text-primary);">
            <i class="fas fa-users-cog"></i> User Management
        </h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="post" style="padding: 20px; margin-bottom: 24px;">
        <form method="GET" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-search"></i> Search
                </label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by username or email..."
                       style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
            </div>
            
            <div style="min-width: 180px;">
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-filter"></i> Status
                </label>
                <select name="status" style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            
            <?php if (!empty($search) || $status_filter !== 'all'): ?>
                <a href="admin_users.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="post" style="padding: 24px;">
        <h3 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-list"></i> Users (<?php echo count($users); ?>)
        </h3>
        
        <?php if (!empty($users)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">User</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Email</th>
                            <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Status</th>
                            <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Role</th>
                            <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Verified</th>
                            <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Joined</th>
                            <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="assets/uploads/profiles/<?php echo htmlspecialchars($user['profile_image'] ?: 'default-avatar.png'); ?>" 
                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary);">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="verified-badge" title="Verified User"><i class="fas fa-check"></i></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span style="font-size: 11px; color: var(--accent-teal); font-weight: 600;">YOU</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 12px; color: var(--text-secondary); font-size: 14px;">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span style="padding: 4px 12px; background: var(--success-color); color: #fff; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            ACTIVE
                                        </span>
                                    <?php elseif ($user['status'] === 'blocked'): ?>
                                        <span style="padding: 4px 12px; background: var(--danger-color); color: #fff; border-radius: 12px; font-size: 11px; font-weight: 600;" title="<?php echo htmlspecialchars($user['blocked_reason']); ?>">
                                            BLOCKED
                                        </span>
                                    <?php else: ?>
                                        <span style="padding: 4px 12px; background: var(--text-muted); color: #fff; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            <?php echo strtoupper($user['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($user['is_admin']): ?>
                                        <span style="padding: 4px 12px; background: var(--gradient-primary); color: #000; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                            ADMIN
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 13px;">User</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($user['is_verified']): ?>
                                        <span class="verified-badge" style="width: 22px; height: 22px; font-size: 11px;" title="Verified"><i class="fas fa-check"></i></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 20px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center; color: var(--text-secondary); font-size: 13px;">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                                            <!-- Verification Toggle -->
                                            <button class="btn btn-sm <?php echo $user['is_verified'] ? 'btn-secondary' : ''; ?>" 
                                                    style="<?php echo $user['is_verified'] ? 'background: var(--bg-tertiary); color: var(--text-secondary);' : 'background: linear-gradient(135deg, var(--accent-teal) 0%, var(--success-color) 100%); color: #000;'; ?> padding: 6px 14px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;"
                                                    onclick="toggleVerification(<?php echo $user['id']; ?>, <?php echo $user['is_verified'] ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                    data-user-id="<?php echo $user['id']; ?>">
                                                <?php if ($user['is_verified']): ?>
                                                    <i class="fas fa-times-circle"></i> Unverify
                                                <?php else: ?>
                                                    <i class="fas fa-check-circle"></i> Verify
                                                <?php endif; ?>
                                            </button>
                                            
                                            <?php if ($user['status'] === 'blocked'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="unblock">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm" style="background: var(--success-color); padding: 6px 12px; font-size: 12px;" onclick="return confirm('Unblock this user?')">
                                                        <i class="fas fa-unlock"></i> Unblock
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm" style="background: var(--danger-color); padding: 6px 12px; font-size: 12px;" onclick="showBlockModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-ban"></i> Block
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['is_admin']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="remove_admin">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Remove admin privileges?')">
                                                        <i class="fas fa-user-minus"></i> Remove Admin
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="make_admin">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm" style="background: var(--gradient-primary); color: #000; padding: 6px 12px; font-size: 12px;" onclick="return confirm('Make this user an admin?')">
                                                        <i class="fas fa-user-shield"></i> Make Admin
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 12px;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; padding: 40px;">No users found</p>
        <?php endif; ?>
    </div>
</div>

<!-- Block User Modal -->
<div id="blockModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
    <div class="post" style="width: 90%; max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-ban" style="color: var(--danger-color);"></i> Block User
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="block">
            <input type="hidden" name="user_id" id="blockUserId">
            
            <p style="margin-bottom: 20px; color: var(--text-secondary);">
                You are about to block <strong id="blockUsername"></strong>. They will not be able to post, comment, or interact with the site.
            </p>
            
            <div class="form-group">
                <label>Reason for blocking</label>
                <textarea name="reason" required placeholder="Enter reason..." style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary); min-height: 100px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeBlockModal()">
                    Cancel
                </button>
                <button type="submit" class="btn" style="background: var(--danger-color);">
                    <i class="fas fa-ban"></i> Block User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showBlockModal(userId, username) {
    document.getElementById('blockUserId').value = userId;
    document.getElementById('blockUsername').textContent = username;
    document.getElementById('blockModal').style.display = 'flex';
}

function closeBlockModal() {
    document.getElementById('blockModal').style.display = 'none';
}

// Close modal on background click
document.getElementById('blockModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBlockModal();
    }
});

// Toggle user verification
function toggleVerification(userId, currentStatus, username) {
    const action = currentStatus ? 'remove verification from' : 'verify';
    const confirmMsg = `Are you sure you want to ${action} ${username}?`;
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    const reason = currentStatus ? 
        prompt('Optional: Enter reason for removing verification:') : 
        prompt('Optional: Enter reason for verification (e.g., "Public figure", "Official account"):');
    
    const formData = new FormData();
    formData.append('user_id', userId);
    if (reason) formData.append('reason', reason);
    
    const button = document.querySelector(`button[data-user-id="${userId}"]`);
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    fetch('ajax/toggle_verification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to update verification'));
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}
</script>

<?php include 'includes/footer.php'; ?>
