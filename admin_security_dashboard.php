<?php
/**
 * Admin Security Dashboard
 * Monitor security events, login attempts, and suspicious activities
 */

session_start();
require_once 'config/database.php';
require_once 'config/admin_auth.php';
require_once 'config/admin_security.php';

// Require admin authentication
require_admin();

$page_title = 'Security Dashboard';

// Clean old logs (run occasionally)
if (rand(1, 100) === 1) {
    cleanup_old_logs();
}

// Get security statistics
$login_stats = get_login_stats(24);
$active_sessions = get_active_admin_sessions();
$security_alerts = get_security_alerts(20, true); // Unresolved only
$all_recent_alerts = get_security_alerts(50, false); // All recent

// Get failed login attempts by IP
$failed_by_ip_query = "SELECT ip_address, COUNT(*) as attempt_count, MAX(attempt_time) as last_attempt
                       FROM login_attempts 
                       WHERE success = 0 
                       AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                       GROUP BY ip_address 
                       ORDER BY attempt_count DESC 
                       LIMIT 10";
$failed_by_ip_result = $conn->query($failed_by_ip_query);

// Get recent admin sessions
$sessions_query = "SELECT ads.*, u.username, u.email 
                   FROM admin_sessions ads
                   JOIN users u ON ads.admin_id = u.id
                   WHERE ads.is_active = 1
                   ORDER BY ads.last_activity DESC
                   LIMIT 20";
$sessions_result = $conn->query($sessions_query);

// Get locked accounts
$locked_query = "SELECT id, username, email, account_locked_until, failed_login_attempts 
                 FROM users 
                 WHERE account_locked_until > NOW()
                 ORDER BY account_locked_until DESC";
$locked_result = $conn->query($locked_query);

// Handle alert resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_alert'])) {
    $alert_id = (int)$_POST['alert_id'];
    $admin_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("UPDATE security_alerts 
                            SET is_resolved = 1, resolved_by = ?, resolved_at = NOW() 
                            WHERE id = ?");
    $stmt->bind_param("ii", $admin_id, $alert_id);
    $stmt->execute();
    $stmt->close();
    
    log_admin_action('resolve_security_alert', 'security_alert', $alert_id);
    
    header("Location: admin_security_dashboard.php");
    exit();
}

// Handle account unlock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_account'])) {
    $user_id = (int)$_POST['user_id'];
    
    $stmt = $conn->prepare("UPDATE users 
                            SET account_locked_until = NULL, failed_login_attempts = 0 
                            WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    log_admin_action('unlock_account', 'user', $user_id);
    
    header("Location: admin_security_dashboard.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
        <h1 style="color: var(--text-primary);">
            <i class="fas fa-shield-alt"></i> Security Dashboard
        </h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Admin
        </a>
    </div>

    <!-- Security Overview Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Total Login Attempts -->
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($login_stats['total_attempts']); ?></h3>
                <p>Login Attempts (24h)</p>
            </div>
        </div>

        <!-- Failed Attempts -->
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($login_stats['failed_attempts']); ?></h3>
                <p>Failed Attempts (24h)</p>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $active_sessions; ?></h3>
                <p>Active Admin Sessions</p>
            </div>
        </div>

        <!-- Unresolved Alerts -->
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($security_alerts); ?></h3>
                <p>Unresolved Alerts</p>
            </div>
        </div>
    </div>

    <!-- Unresolved Security Alerts -->
    <?php if (count($security_alerts) > 0): ?>
    <div class="admin-card" style="margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-exclamation-circle" style="color: #f5576c;"></i> Unresolved Security Alerts
        </h2>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--bg-tertiary); border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Severity</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Type</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">User</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Description</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">IP Address</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Time</th>
                        <th style="padding: 12px; text-align: center; color: var(--text-secondary);">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_alerts as $alert): 
                        $severity_colors = [
                            'low' => '#4caf50',
                            'medium' => '#ff9800',
                            'high' => '#f44336',
                            'critical' => '#d32f2f'
                        ];
                        $color = $severity_colors[$alert['severity']] ?? '#666';
                    ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <span style="background: <?php echo $color; ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; text-transform: uppercase;">
                                <?php echo htmlspecialchars($alert['severity']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; color: var(--text-primary);">
                            <?php echo htmlspecialchars(str_replace('_', ' ', $alert['alert_type'])); ?>
                        </td>
                        <td style="padding: 12px; color: var(--text-primary);">
                            <?php echo $alert['username'] ? htmlspecialchars($alert['username']) : 'N/A'; ?>
                        </td>
                        <td style="padding: 12px; color: var(--text-secondary); max-width: 300px;">
                            <?php echo htmlspecialchars($alert['description']); ?>
                        </td>
                        <td style="padding: 12px; color: var(--text-secondary); font-family: monospace;">
                            <?php echo htmlspecialchars($alert['ip_address']); ?>
                        </td>
                        <td style="padding: 12px; color: var(--text-secondary); font-size: 13px;">
                            <?php echo date('M d, H:i', strtotime($alert['created_at'])); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                <button type="submit" name="resolve_alert" class="btn btn-sm" 
                                        style="background: var(--success); padding: 6px 12px;">
                                    <i class="fas fa-check"></i> Resolve
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Locked Accounts -->
    <?php if ($locked_result->num_rows > 0): ?>
    <div class="admin-card" style="margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-lock"></i> Locked Accounts
        </h2>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--bg-tertiary); border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Username</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Email</th>
                        <th style="padding: 12px; text-align: center; color: var(--text-secondary);">Failed Attempts</th>
                        <th style="padding: 12px; text-align: left; color: var(--text-secondary);">Locked Until</th>
                        <th style="padding: 12px; text-align: center; color: var(--text-secondary);">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($locked = $locked_result->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px; color: var(--text-primary);">
                            <?php echo htmlspecialchars($locked['username']); ?>
                        </td>
                        <td style="padding: 12px; color: var(--text-secondary);">
                            <?php echo htmlspecialchars($locked['email']); ?>
                        </td>
                        <td style="padding: 12px; text-align: center; color: var(--danger);">
                            <?php echo $locked['failed_login_attempts']; ?>
                        </td>
                        <td style="padding: 12px; color: var(--text-secondary);">
                            <?php echo date('M d, Y H:i', strtotime($locked['account_locked_until'])); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $locked['id']; ?>">
                                <button type="submit" name="unlock_account" class="btn btn-sm" 
                                        style="background: var(--warning); padding: 6px 12px;">
                                    <i class="fas fa-unlock"></i> Unlock
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Two Column Layout -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Failed Attempts by IP -->
        <div class="admin-card">
            <h2 style="margin-bottom: 20px; color: var(--text-primary);">
                <i class="fas fa-network-wired"></i> Failed Attempts by IP
            </h2>
            
            <?php if ($failed_by_ip_result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg-tertiary); border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 10px; text-align: left; color: var(--text-secondary); font-size: 13px;">IP Address</th>
                            <th style="padding: 10px; text-align: center; color: var(--text-secondary); font-size: 13px;">Attempts</th>
                            <th style="padding: 10px; text-align: left; color: var(--text-secondary); font-size: 13px;">Last Attempt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ip_data = $failed_by_ip_result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px; color: var(--text-primary); font-family: monospace; font-size: 13px;">
                                <?php echo htmlspecialchars($ip_data['ip_address']); ?>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="background: var(--danger); color: white; padding: 3px 10px; border-radius: 10px; font-size: 12px;">
                                    <?php echo $ip_data['attempt_count']; ?>
                                </span>
                            </td>
                            <td style="padding: 10px; color: var(--text-secondary); font-size: 12px;">
                                <?php echo date('M d, H:i', strtotime($ip_data['last_attempt'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 20px;">No failed attempts in the last 24 hours</p>
            <?php endif; ?>
        </div>

        <!-- Active Admin Sessions -->
        <div class="admin-card">
            <h2 style="margin-bottom: 20px; color: var(--text-primary);">
                <i class="fas fa-user-shield"></i> Active Admin Sessions
            </h2>
            
            <?php if ($sessions_result->num_rows > 0): ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php while ($session = $sessions_result->fetch_assoc()): ?>
                <div style="padding: 12px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--text-primary); font-weight: 500; margin-bottom: 4px;">
                            <?php echo htmlspecialchars($session['username']); ?>
                        </div>
                        <div style="color: var(--text-secondary); font-size: 12px; font-family: monospace;">
                            <?php echo htmlspecialchars($session['ip_address']); ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: var(--success); font-size: 11px; margin-bottom: 2px;">
                            <i class="fas fa-circle" style="font-size: 6px;"></i> Active
                        </div>
                        <div style="color: var(--text-secondary); font-size: 11px;">
                            <?php 
                            $minutes_ago = round((time() - strtotime($session['last_activity'])) / 60);
                            echo $minutes_ago === 0 ? 'Just now' : "{$minutes_ago}m ago";
                            ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <p style="color: var(--text-secondary); text-align: center; padding: 20px;">No active admin sessions</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Security Settings Quick Access -->
    <div class="admin-card">
        <h2 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-cog"></i> Security Settings
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="padding: 15px; background: var(--bg-tertiary); border-radius: var(--radius); border-left: 3px solid var(--primary);">
                <div style="font-weight: 500; color: var(--text-primary); margin-bottom: 8px;">Max Login Attempts</div>
                <div style="color: var(--text-secondary); font-size: 24px; font-weight: bold;">
                    <?php echo get_setting('max_login_attempts', '5'); ?>
                </div>
            </div>
            
            <div style="padding: 15px; background: var(--bg-tertiary); border-radius: var(--radius); border-left: 3px solid var(--warning);">
                <div style="font-weight: 500; color: var(--text-primary); margin-bottom: 8px;">Lockout Duration</div>
                <div style="color: var(--text-secondary); font-size: 24px; font-weight: bold;">
                    <?php echo get_setting('account_lockout_duration', '30'); ?> min
                </div>
            </div>
            
            <div style="padding: 15px; background: var(--bg-tertiary); border-radius: var(--radius); border-left: 3px solid var(--info);">
                <div style="font-weight: 500; color: var(--text-primary); margin-bottom: 8px;">Session Timeout</div>
                <div style="color: var(--text-secondary); font-size: 24px; font-weight: bold;">
                    <?php echo get_setting('session_timeout', '60'); ?> min
                </div>
            </div>
            
            <div style="padding: 15px; background: var(--bg-tertiary); border-radius: var(--radius); border-left: 3px solid var(--success);">
                <div style="font-weight: 500; color: var(--text-primary); margin-bottom: 8px;">Log Retention</div>
                <div style="color: var(--text-secondary); font-size: 24px; font-weight: bold;">
                    <?php echo get_setting('log_retention_days', '90'); ?> days
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="admin_settings.php" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modify Security Settings
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
