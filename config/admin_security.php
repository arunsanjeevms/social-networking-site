<?php
/**
 * Admin Security Functions
 * Enhanced security features for admin module
 */

require_once 'database.php';
require_once __DIR__ . '/admin_auth.php';

/**
 * Log login attempt
 */
function log_login_attempt($username, $success, $failure_reason = null) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, user_agent, success, failure_reason) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $username, $ip_address, $user_agent, $success, $failure_reason);
    $stmt->execute();
    $stmt->close();
}

/**
 * Check if IP is rate limited
 */
function is_rate_limited($ip_address = null) {
    global $conn;
    
    if (!get_setting('enable_rate_limiting', '1')) {
        return false;
    }
    
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }
    
    // Check failed attempts in last 15 minutes
    $stmt = $conn->prepare("SELECT COUNT(*) as attempt_count 
                            FROM login_attempts 
                            WHERE ip_address = ? 
                            AND success = 0 
                            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $max_attempts = (int)get_setting('max_login_attempts', '5');
    
    return $result['attempt_count'] >= $max_attempts;
}

/**
 * Check if account is locked
 */
function is_account_locked($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT account_locked_until FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result && $result['account_locked_until']) {
        return strtotime($result['account_locked_until']) > time();
    }
    
    return false;
}

/**
 * Lock user account after failed attempts
 */
function lock_account($user_id) {
    global $conn;
    
    $lockout_minutes = (int)get_setting('account_lockout_duration', '30');
    
    $stmt = $conn->prepare("UPDATE users 
                            SET account_locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                                failed_login_attempts = failed_login_attempts + 1
                            WHERE id = ?");
    $stmt->bind_param("ii", $lockout_minutes, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Create security alert
    create_security_alert('failed_login', 'medium', $user_id, 
                         "Account locked after multiple failed login attempts");
}

/**
 * Reset failed login attempts
 */
function reset_failed_attempts($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE users 
                            SET failed_login_attempts = 0,
                                account_locked_until = NULL
                            WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Create admin session
 */
function create_admin_session($admin_id) {
    global $conn;
    
    $session_token = bin2hex(random_bytes(32));
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Deactivate old sessions
    $stmt = $conn->prepare("UPDATE admin_sessions SET is_active = 0 WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
    
    // Create new session
    $stmt = $conn->prepare("INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $session_token, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['admin_session_token'] = $session_token;
    $_SESSION['session_start_time'] = time();
    
    return $session_token;
}

/**
 * Validate admin session
 */
function validate_admin_session() {
    global $conn;
    
    if (!isset($_SESSION['admin_session_token']) || !isset($_SESSION['user_id'])) {
        return false;
    }
    
    $token = $_SESSION['admin_session_token'];
    $timeout_minutes = (int)get_setting('session_timeout', '60');
    
    $stmt = $conn->prepare("SELECT admin_id, ip_address, last_activity 
                            FROM admin_sessions 
                            WHERE session_token = ? 
                            AND is_active = 1
                            AND last_activity > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->bind_param("si", $token, $timeout_minutes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $session = $result->fetch_assoc();
    $stmt->close();
    
    // Verify IP hasn't changed (prevents session hijacking)
    if ($session['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        create_security_alert('session_hijack', 'critical', $session['admin_id'],
                            "Session IP mismatch detected");
        return false;
    }
    
    // Update last activity
    $stmt = $conn->prepare("UPDATE admin_sessions SET last_activity = NOW() WHERE session_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

/**
 * Logout admin session
 */
function logout_admin_session() {
    global $conn;
    
    if (isset($_SESSION['admin_session_token'])) {
        $token = $_SESSION['admin_session_token'];
        
        $stmt = $conn->prepare("UPDATE admin_sessions 
                                SET is_active = 0, logout_time = NOW() 
                                WHERE session_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
    
    unset($_SESSION['admin_session_token']);
    unset($_SESSION['session_start_time']);
}

/**
 * Create security alert
 */
function create_security_alert($alert_type, $severity, $user_id = null, $description = '', $details = null) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $details_json = $details ? json_encode($details) : null;
    
    $stmt = $conn->prepare("INSERT INTO security_alerts 
                            (alert_type, severity, user_id, ip_address, description, details) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $alert_type, $severity, $user_id, $ip_address, $description, $details_json);
    $stmt->execute();
    $stmt->close();
}

/**
 * Check if IP is whitelisted for admin access
 */
function is_ip_whitelisted($ip_address = null) {
    global $conn;
    
    if (!get_setting('enable_ip_whitelist', '0')) {
        return true; // Whitelist disabled, allow all
    }
    
    if ($ip_address === null) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }
    
    $stmt = $conn->prepare("SELECT id FROM admin_ip_whitelist 
                            WHERE ip_address = ? AND is_active = 1");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_whitelisted = $result->num_rows > 0;
    $stmt->close();
    
    return $is_whitelisted;
}

/**
 * Get recent security alerts
 */
function get_security_alerts($limit = 10, $unresolved_only = false) {
    global $conn;
    
    $where = $unresolved_only ? "WHERE is_resolved = 0" : "";
    
    $query = "SELECT sa.*, u.username 
              FROM security_alerts sa
              LEFT JOIN users u ON sa.user_id = u.id
              {$where}
              ORDER BY sa.created_at DESC 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    $stmt->close();
    return $alerts;
}

/**
 * Get login attempt statistics
 */
function get_login_stats($hours = 24) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT 
                            COUNT(*) as total_attempts,
                            SUM(success = 1) as successful_logins,
                            SUM(success = 0) as failed_attempts,
                            COUNT(DISTINCT ip_address) as unique_ips
                            FROM login_attempts 
                            WHERE attempt_time > DATE_SUB(NOW(), INTERVAL ? HOUR)");
    $stmt->bind_param("i", $hours);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * Get active admin sessions count
 */
function get_active_admin_sessions() {
    global $conn;
    
    $timeout_minutes = (int)get_setting('session_timeout', '60');
    
    $stmt = $conn->prepare("SELECT COUNT(*) as active_sessions 
                            FROM admin_sessions 
                            WHERE is_active = 1 
                            AND last_activity > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->bind_param("i", $timeout_minutes);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['active_sessions'];
}

/**
 * Detect suspicious activity patterns
 */
function detect_suspicious_activity($user_id) {
    global $conn;
    
    // Check for rapid location changes (different IPs in short time)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT ip_address) as ip_count 
                            FROM admin_logs 
                            WHERE admin_id = ? 
                            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['ip_count'] > 3) {
        create_security_alert('unusual_location', 'high', $user_id,
                            "Multiple IP addresses detected in short timeframe",
                            ['ip_count' => $result['ip_count']]);
        return true;
    }
    
    // Check for mass deletion attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as delete_count 
                            FROM admin_logs 
                            WHERE admin_id = ? 
                            AND action IN ('delete_post', 'block_user')
                            AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['delete_count'] > 10) {
        create_security_alert('mass_deletion', 'high', $user_id,
                            "Unusual number of deletion actions detected",
                            ['delete_count' => $result['delete_count']]);
        return true;
    }
    
    return false;
}

/**
 * Clean old security logs
 */
function cleanup_old_logs() {
    global $conn;
    
    $retention_days = (int)get_setting('log_retention_days', '90');
    
    // Clean old login attempts
    $stmt = $conn->prepare("DELETE FROM login_attempts 
                            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $retention_days);
    $stmt->execute();
    $stmt->close();
    
    // Clean old inactive sessions
    $stmt = $conn->prepare("DELETE FROM admin_sessions 
                            WHERE is_active = 0 
                            AND logout_time < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $retention_days);
    $stmt->execute();
    $stmt->close();
}
