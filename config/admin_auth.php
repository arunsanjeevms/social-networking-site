<?php
/**
 * Admin Authentication Helper
 * 
 * Functions to check admin status and protect admin pages
 */

/**
 * Check if current user is an admin
 */
function is_admin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    // Cache admin status in session
    if (!isset($_SESSION['is_admin'])) {
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
        } else {
            $_SESSION['is_admin'] = false;
        }
        $stmt->close();
    }
    
    return $_SESSION['is_admin'];
}

/**
 * Require admin access - redirect if not admin
 * Enhanced with security checks
 */
function require_admin() {
    // Include security functions
    require_once __DIR__ . '/admin_security.php';
    
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
    
    if (!is_admin()) {
        header("Location: home.php");
        exit();
    }
    
    // Validate admin session
    if (!validate_admin_session()) {
        // Session expired or invalid
        logout_admin_session();
        $_SESSION['error'] = 'Your session has expired. Please login again.';
        header("Location: index.php");
        exit();
    }
    
    // Check IP whitelist
    if (!is_ip_whitelisted()) {
        create_security_alert('suspicious_activity', 'high', $_SESSION['user_id'],
                            'Admin access attempted from non-whitelisted IP');
        $_SESSION['error'] = 'Access denied from your location.';
        header("Location: home.php");
        exit();
    }
    
    // Detect suspicious activity
    detect_suspicious_activity($_SESSION['user_id']);
}

/**
 * Log admin action
 */
function log_admin_action($action, $target_type = null, $target_id = null, $details = null) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $details_json = $details ? json_encode($details) : null;
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $admin_id, $action, $target_type, $target_id, $details_json, $ip_address);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get system setting value
 */
function get_setting($key, $default = null) {
    global $conn;
    
    // Cache settings in session
    if (!isset($_SESSION['system_settings'])) {
        $_SESSION['system_settings'] = [];
    }
    
    if (isset($_SESSION['system_settings'][$key])) {
        return $_SESSION['system_settings'][$key];
    }
    
    $stmt = $conn->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $value = $row['setting_value'];
        
        // Convert value based on type
        switch ($row['setting_type']) {
            case 'boolean':
                $value = (bool)$value;
                break;
            case 'number':
                $value = is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : $value;
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
        }
        
        $_SESSION['system_settings'][$key] = $value;
        $stmt->close();
        return $value;
    }
    
    $stmt->close();
    return $default;
}

/**
 * Update system setting
 */
function update_setting($key, $value) {
    global $conn;
    
    $admin_id = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?");
    $stmt->bind_param("sis", $value, $admin_id, $key);
    $result = $stmt->execute();
    $stmt->close();
    
    // Clear cached settings
    unset($_SESSION['system_settings'][$key]);
    
    return $result;
}
?>
