<?php
/**
 * Admin Dashboard - Central Control Panel
 * Main hub for administrative functions
 */

session_start();
require_once 'config/database.php';
require_once 'config/admin_auth.php';
require_admin();

$page_title = 'Admin Dashboard';

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total posts
$result = $conn->query("SELECT COUNT(*) as count FROM posts WHERE is_deleted = 0");
$stats['total_posts'] = $result->fetch_assoc()['count'];

// Total comments
$result = $conn->query("SELECT COUNT(*) as count FROM comments");
$stats['total_comments'] = $result->fetch_assoc()['count'];

// Total likes
$result = $conn->query("SELECT COUNT(*) as count FROM likes");
$stats['total_likes'] = $result->fetch_assoc()['count'];

// Blocked users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'blocked'");
$stats['blocked_users'] = $result->fetch_assoc()['count'];

// Verified users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 1");
$stats['verified_users'] = $result->fetch_assoc()['count'];

// Recent users (last 7 days)
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_users'] = $result->fetch_assoc()['count'];

// Recent posts (last 24 hours)
$result = $conn->query("SELECT COUNT(*) as count FROM posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_deleted = 0");
$stats['recent_posts'] = $result->fetch_assoc()['count'];

include 'includes/header.php';
?>

<div class="container" style="max-width: 1400px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 8px;">
                <i class="fas fa-shield-alt"></i> Admin Dashboard
            </h1>
            <p style="color: var(--text-secondary);">Central control panel for site management</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="home.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Site
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <!-- Total Users -->
        <div class="post" style="padding: 24px; text-align: center; background: linear-gradient(135deg, rgba(0, 240, 255, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);">
            <div style="font-size: 48px; color: var(--accent-teal); margin-bottom: 12px;">
                <i class="fas fa-users"></i>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <?php echo number_format($stats['total_users']); ?>
            </div>
            <div style="color: var(--text-secondary); font-weight: 600;">Total Users</div>
            <div style="font-size: 13px; color: var(--accent-teal); margin-top: 8px;">
                +<?php echo $stats['new_users']; ?> this week
            </div>
        </div>

        <!-- Total Posts -->
        <div class="post" style="padding: 24px; text-align: center; background: linear-gradient(135deg, rgba(168, 85, 247, 0.1) 0%, rgba(244, 114, 182, 0.1) 100%);">
            <div style="font-size: 48px; color: var(--accent-violet); margin-bottom: 12px;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <?php echo number_format($stats['total_posts']); ?>
            </div>
            <div style="color: var(--text-secondary); font-weight: 600;">Total Posts</div>
            <div style="font-size: 13px; color: var(--accent-violet); margin-top: 8px;">
                +<?php echo $stats['recent_posts']; ?> today
            </div>
        </div>

        <!-- Total Comments -->
        <div class="post" style="padding: 24px; text-align: center; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(0, 240, 255, 0.1) 100%);">
            <div style="font-size: 48px; color: var(--accent-blue); margin-bottom: 12px;">
                <i class="fas fa-comments"></i>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <?php echo number_format($stats['total_comments']); ?>
            </div>
            <div style="color: var(--text-secondary); font-weight: 600;">Total Comments</div>
        </div>

        <!-- Total Likes -->
        <div class="post" style="padding: 24px; text-align: center; background: linear-gradient(135deg, rgba(244, 114, 182, 0.1) 0%, rgba(239, 68, 68, 0.1) 100%);">
            <div style="font-size: 48px; color: var(--accent-pink); margin-bottom: 12px;">
                <i class="fas fa-heart"></i>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <?php echo number_format($stats['total_likes']); ?>
            </div>
            <div style="color: var(--text-secondary); font-weight: 600;">Total Likes</div>
        </div>

        <!-- Verified Users -->
        <div class="post" style="padding: 24px; text-align: center; background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(0, 240, 255, 0.1) 100%);">
            <div style="font-size: 48px; color: var(--success-color); margin-bottom: 12px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <?php echo number_format($stats['verified_users']); ?>
            </div>
            <div style="color: var(--text-secondary); font-weight: 600;">Verified Users</div>
        </div>

        <!-- Blocked Users -->
        <div class="post" style="padding: 24px; text-align: center; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);">
            <div style="font-size: 48px; color: var(--danger-color); margin-bottom: 12px;">
                <i class="fas fa-ban"></i>
            </div>
            <div style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                <?php echo number_format($stats['blocked_users']); ?>
            </div>
            <div style="color: var(--text-secondary); font-weight: 600;">Blocked Users</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h2 style="color: var(--text-primary); margin-bottom: 24px;">
        <i class="fas fa-bolt"></i> Quick Actions
    </h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <!-- User Management -->
        <a href="admin_users.php" class="post" style="padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                <div style="width: 56px; height: 56px; background: var(--gradient-primary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #000;">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 4px;">User Management</h3>
                    <p style="color: var(--text-secondary); font-size: 13px;">Manage users, roles, and permissions</p>
                </div>
            </div>
        </a>

        <!-- Post Management -->
        <a href="admin_posts.php" class="post" style="padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                <div style="width: 56px; height: 56px; background: var(--gradient-secondary); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #000;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 4px;">Post Management</h3>
                    <p style="color: var(--text-secondary); font-size: 13px;">Moderate posts and content</p>
                </div>
            </div>
        </a>

        <!-- Security Dashboard -->
        <a href="admin_security_dashboard.php" class="post" style="padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                <div style="width: 56px; height: 56px; background: var(--gradient-danger); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 4px;">Security Dashboard</h3>
                    <p style="color: var(--text-secondary); font-size: 13px;">Monitor security and threats</p>
                </div>
            </div>
        </a>

        <!-- Activity Logs -->
        <a href="admin_logs.php" class="post" style="padding: 24px; text-decoration: none; transition: transform 0.2s; display: block;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                <div style="width: 56px; height: 56px; background: var(--gradient-success); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #000;">
                    <i class="fas fa-history"></i>
                </div>
                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 4px;">Activity Logs</h3>
                    <p style="color: var(--text-secondary); font-size: 13px;">View admin actions and logs</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Activity -->
    <h2 style="color: var(--text-primary); margin-bottom: 24px;">
        <i class="fas fa-clock"></i> Recent Activity
    </h2>
    
    <div class="post" style="padding: 24px;">
        <?php
        // Get recent admin actions
        $stmt = $conn->prepare("SELECT al.*, u.username as admin_username 
                                FROM admin_logs al 
                                JOIN users u ON al.admin_id = u.id 
                                ORDER BY al.created_at DESC 
                                LIMIT 10");
        $stmt->execute();
        $logs = $stmt->get_result();
        
        if ($logs->num_rows > 0):
        ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Admin</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Action</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Type</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px; color: var(--text-primary);">
                                <?php echo htmlspecialchars($log['admin_username']); ?>
                            </td>
                            <td style="padding: 12px; color: var(--text-primary);">
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))); ?>
                            </td>
                            <td style="padding: 12px;">
                                <span style="padding: 4px 10px; background: var(--bg-tertiary); border-radius: 12px; font-size: 12px; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($log['entity_type'] ?? 'General'); ?>
                                </span>
                            </td>
                            <td style="padding: 12px; color: var(--text-secondary); font-size: 13px;">
                                <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; padding: 40px;">No recent activity</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>