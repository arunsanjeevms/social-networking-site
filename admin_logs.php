<?php
/**
 * Admin Activity Logs
 * View all administrative actions and system logs
 */

session_start();
require_once 'config/database.php';
require_once 'config/admin_auth.php';
require_admin();

$page_title = 'Activity Logs';

// Get filters
$action_filter = $_GET['action'] ?? 'all';
$admin_filter = $_GET['admin'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($action_filter !== 'all') {
    $where_clauses[] = "al.action = ?";
    $params[] = $action_filter;
    $types .= 's';
}

if ($admin_filter !== 'all') {
    $where_clauses[] = "al.admin_id = ?";
    $params[] = (int)$admin_filter;
    $types .= 'i';
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_clauses[] = "DATE(al.created_at) = CURDATE()";
            break;
        case 'week':
            $where_clauses[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_clauses[] = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM admin_logs al $where_sql";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_logs = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $per_page);

// Get logs
$query = "SELECT al.*, u.username as admin_username 
          FROM admin_logs al 
          JOIN users u ON al.admin_id = u.id 
          $where_sql
          ORDER BY al.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

// Get unique actions for filter
$actions_result = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
$actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Get all admins for filter
$admins_result = $conn->query("SELECT DISTINCT u.id, u.username FROM admin_logs al JOIN users u ON al.admin_id = u.id ORDER BY u.username");
$admins = [];
while ($row = $admins_result->fetch_assoc()) {
    $admins[] = $row;
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 1600px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: var(--text-primary);">
            <i class="fas fa-history"></i> Activity Logs
        </h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px;">
        <div class="post" style="padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 700; color: var(--accent-teal); margin-bottom: 4px;">
                <?php echo number_format($total_logs); ?>
            </div>
            <div style="color: var(--text-secondary); font-size: 13px;">Total Logs</div>
        </div>
        
        <div class="post" style="padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 700; color: var(--accent-violet); margin-bottom: 4px;">
                <?php 
                $today_count = $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
                echo number_format($today_count);
                ?>
            </div>
            <div style="color: var(--text-secondary); font-size: 13px;">Today</div>
        </div>
        
        <div class="post" style="padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 700; color: var(--accent-blue); margin-bottom: 4px;">
                <?php 
                $week_count = $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
                echo number_format($week_count);
                ?>
            </div>
            <div style="color: var(--text-secondary); font-size: 13px;">This Week</div>
        </div>
        
        <div class="post" style="padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 700; color: var(--accent-pink); margin-bottom: 4px;">
                <?php 
                $admin_count = $conn->query("SELECT COUNT(DISTINCT admin_id) as count FROM admin_logs")->fetch_assoc()['count'];
                echo number_format($admin_count);
                ?>
            </div>
            <div style="color: var(--text-secondary); font-size: 13px;">Active Admins</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="post" style="padding: 20px; margin-bottom: 24px;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-bolt"></i> Action
                </label>
                <select name="action" style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <option value="all">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $action))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-user-shield"></i> Admin
                </label>
                <select name="admin" style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <option value="all">All Admins</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?php echo $admin['id']; ?>" <?php echo $admin_filter == $admin['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($admin['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px;">
                    <i class="fas fa-calendar"></i> Date Range
                </label>
                <select name="date" style="width: 100%; padding: 10px 14px; border: 2px solid var(--border-color); border-radius: var(--radius); background: var(--bg-tertiary); color: var(--text-primary);">
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if ($action_filter !== 'all' || $admin_filter !== 'all' || $date_filter !== 'all'): ?>
                    <a href="admin_logs.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="post" style="padding: 24px;">
        <h3 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-list"></i> Activity Logs 
            <span style="color: var(--text-secondary); font-size: 14px; font-weight: 400;">
                (Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?>)
            </span>
        </h3>
        
        <?php if ($logs->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">ID</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Admin</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Action</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Type</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Entity ID</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">IP Address</th>
                            <th style="padding: 12px; text-align: left; color: var(--text-secondary); font-weight: 600;">Timestamp</th>
                            <th style="padding: 12px; text-align: center; color: var(--text-secondary); font-weight: 600;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px; color: var(--text-muted); font-family: monospace; font-size: 12px;">
                                    #<?php echo $log['id']; ?>
                                </td>
                                <td style="padding: 12px; color: var(--text-primary); font-weight: 600;">
                                    <?php echo htmlspecialchars($log['admin_username']); ?>
                                </td>
                                <td style="padding: 12px; color: var(--text-primary);">
                                    <?php 
                                    $action_name = ucwords(str_replace('_', ' ', $log['action']));
                                    $action_colors = [
                                        'create' => 'var(--success-color)',
                                        'update' => 'var(--info-color)',
                                        'delete' => 'var(--danger-color)',
                                        'block' => 'var(--danger-color)',
                                        'unblock' => 'var(--success-color)',
                                        'verify' => 'var(--accent-teal)',
                                        'login' => 'var(--accent-blue)'
                                    ];
                                    
                                    $color = 'var(--text-primary)';
                                    foreach ($action_colors as $keyword => $action_color) {
                                        if (stripos($log['action'], $keyword) !== false) {
                                            $color = $action_color;
                                            break;
                                        }
                                    }
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: 600;">
                                        <?php echo htmlspecialchars($action_name); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="padding: 4px 10px; background: var(--bg-tertiary); border-radius: 12px; font-size: 11px; color: var(--text-secondary); font-weight: 600;">
                                        <?php echo htmlspecialchars($log['entity_type'] ?? 'General'); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; color: var(--text-secondary); font-family: monospace; font-size: 12px;">
                                    <?php echo !empty($log['entity_id']) ? '#' . $log['entity_id'] : '—'; ?>
                                </td>
                                <td style="padding: 12px; color: var(--text-secondary); font-family: monospace; font-size: 12px;">
                                    <?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?>
                                </td>
                                <td style="padding: 12px; color: var(--text-secondary); font-size: 13px;">
                                    <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if (!empty($log['details'])): ?>
                                        <button onclick="showDetails(<?php echo htmlspecialchars(json_encode($log['details'])); ?>)" 
                                                class="btn btn-sm" 
                                                style="padding: 4px 10px; font-size: 11px; background: var(--bg-tertiary);">
                                            <i class="fas fa-info-circle"></i> View
                                        </button>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&action=<?php echo $action_filter; ?>&admin=<?php echo $admin_filter; ?>&date=<?php echo $date_filter; ?>" 
                           class="btn btn-secondary" style="padding: 8px 16px;">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 4px;">
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?>&action=<?php echo $action_filter; ?>&admin=<?php echo $admin_filter; ?>&date=<?php echo $date_filter; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>" 
                               style="padding: 8px 14px;">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&action=<?php echo $action_filter; ?>&admin=<?php echo $admin_filter; ?>&date=<?php echo $date_filter; ?>" 
                           class="btn btn-secondary" style="padding: 8px 16px;">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; padding: 40px;">
                <i class="fas fa-inbox" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                No logs found with the selected filters
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
    <div class="post" style="width: 100%; max-width: 600px; max-height: 80vh; overflow-y: auto; padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: var(--text-primary); margin: 0;">
                <i class="fas fa-info-circle"></i> Log Details
            </h3>
            <button onclick="closeDetails()" class="btn btn-secondary" style="padding: 6px 12px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <pre id="detailsContent" style="background: var(--bg-tertiary); padding: 16px; border-radius: var(--radius); color: var(--text-primary); overflow-x: auto; font-size: 13px; line-height: 1.6; font-family: 'Courier New', monospace;"></pre>
    </div>
</div>

<script>
function showDetails(details) {
    document.getElementById('detailsContent').textContent = JSON.stringify(details, null, 2);
    document.getElementById('detailsModal').style.display = 'flex';
}

function closeDetails() {
    document.getElementById('detailsModal').style.display = 'none';
}

// Close modal on background click
document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetails();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetails();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
