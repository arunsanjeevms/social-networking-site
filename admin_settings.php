<?php
/**
 * Admin System Settings
 * Configure site-wide settings
 */

session_start();
require_once 'config/database.php';
require_once 'config/admin_auth.php';
require_admin();

$page_title = 'System Settings';
$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $updated = 0;
    $failed = 0;
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'update_settings' && strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8); // Remove 'setting_' prefix
            
            // Convert checkbox values
            if (!isset($_POST[$key]) && strpos($value, 'checkbox') !== false) {
                $value = '0';
            }
            
            if (update_setting($setting_key, $value)) {
                $updated++;
            } else {
                $failed++;
            }
        }
    }
    
    if ($updated > 0) {
        log_admin_action('update_settings', 'system', null, ['updated_count' => $updated]);
        $success = "Settings updated successfully ($updated settings)";
    }
    
    if ($failed > 0) {
        $error = "Failed to update $failed settings";
    }
}

// Get all settings grouped by category
$settings = $conn->query("
    SELECT setting_key, setting_value, setting_type, description 
    FROM system_settings 
    ORDER BY setting_key
")->fetch_all(MYSQLI_ASSOC);

// Group settings
$general_settings = [];
$feature_settings = [];
$content_settings = [];
$security_settings = [];

foreach ($settings as $setting) {
    $key = $setting['setting_key'];
    
    if (in_array($key, ['site_name', 'site_description', 'maintenance_mode'])) {
        $general_settings[] = $setting;
    } elseif (strpos($key, 'enabled') !== false || strpos($key, 'allow') !== false) {
        $feature_settings[] = $setting;
    } elseif (strpos($key, 'max') !== false || strpos($key, 'duration') !== false) {
        $content_settings[] = $setting;
    } elseif (strpos($key, 'require') !== false || strpos($key, 'verification') !== false) {
        $security_settings[] = $setting;
    } else {
        $general_settings[] = $setting;
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 1200px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: var(--text-primary);">
            <i class="fas fa-cogs"></i> System Settings
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

    <form method="POST">
        <!-- General Settings -->
        <div class="post" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin-bottom: 20px; color: var(--text-primary); border-bottom: 2px solid var(--border-color); padding-bottom: 12px;">
                <i class="fas fa-globe"></i> General Settings
            </h3>
            
            <?php foreach ($general_settings as $setting): ?>
                <div class="form-group">
                    <label style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600; color: var(--text-primary);">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </span>
                    </label>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($setting['description']); ?>
                    </p>
                    
                    <?php if ($setting['setting_type'] === 'boolean'): ?>
                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="setting_<?php echo $setting['setting_key']; ?>" 
                                   value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?>
                                   style="width: 20px; height: 20px; margin-right: 10px;">
                            <span style="color: var(--text-secondary);">Enabled</span>
                        </label>
                    <?php elseif ($setting['setting_type'] === 'number'): ?>
                        <input type="number" name="setting_<?php echo $setting['setting_key']; ?>" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                               style="width: 200px; padding: 10px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <?php else: ?>
                        <input type="text" name="setting_<?php echo $setting['setting_key']; ?>" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                               style="width: 100%; padding: 10px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Feature Settings -->
        <div class="post" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin-bottom: 20px; color: var(--text-primary); border-bottom: 2px solid var(--border-color); padding-bottom: 12px;">
                <i class="fas fa-toggle-on"></i> Feature Toggles
            </h3>
            
            <?php foreach ($feature_settings as $setting): ?>
                <div class="form-group">
                    <label style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600; color: var(--text-primary);">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </span>
                    </label>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($setting['description']); ?>
                    </p>
                    
                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="setting_<?php echo $setting['setting_key']; ?>" 
                               value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?>
                               style="width: 20px; height: 20px; margin-right: 10px;">
                        <span style="color: var(--text-secondary);">Enabled</span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Content Limits -->
        <div class="post" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin-bottom: 20px; color: var(--text-primary); border-bottom: 2px solid var(--border-color); padding-bottom: 12px;">
                <i class="fas fa-ruler"></i> Content Limits
            </h3>
            
            <?php foreach ($content_settings as $setting): ?>
                <div class="form-group">
                    <label style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600; color: var(--text-primary);">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </span>
                    </label>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($setting['description']); ?>
                    </p>
                    
                    <input type="number" name="setting_<?php echo $setting['setting_key']; ?>" 
                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                           min="1"
                           style="width: 200px; padding: 10px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Security Settings -->
        <?php if (!empty($security_settings)): ?>
        <div class="post" style="padding: 24px; margin-bottom: 24px;">
            <h3 style="margin-bottom: 20px; color: var(--text-primary); border-bottom: 2px solid var(--border-color); padding-bottom: 12px;">
                <i class="fas fa-shield-alt"></i> Security Settings
            </h3>
            
            <?php foreach ($security_settings as $setting): ?>
                <div class="form-group">
                    <label style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600; color: var(--text-primary);">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </span>
                    </label>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($setting['description']); ?>
                    </p>
                    
                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="setting_<?php echo $setting['setting_key']; ?>" 
                               value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?>
                               style="width: 20px; height: 20px; margin-right: 10px;">
                        <span style="color: var(--text-secondary);">Enabled</span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Save Button -->
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" name="update_settings" class="btn btn-primary">
                <i class="fas fa-save"></i> Save All Settings
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
