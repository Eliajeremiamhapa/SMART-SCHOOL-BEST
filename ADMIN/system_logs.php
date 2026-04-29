<?php
// ADMIN/system_logs.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$page_title = "System Logs";
include 'includes/admin_header.php';

// Get filter
$filter = $_GET['filter'] ?? '';
$sql = "
    SELECT l.*, u.full_name, u.username 
    FROM system_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 500
";
$logs = $pdo->query($sql)->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs");
$total_logs = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as users FROM system_logs WHERE user_id IS NOT NULL");
$active_users = $stmt->fetch()['users'];
?>

<div class="container">
    <h1>📋 System Logs</h1>
    
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_logs; ?></div>
            <div class="stat-label">Total Activities</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $active_users; ?></div>
            <div class="stat-label">Active Users</div>
        </div>
    </div>
    
    <div class="form-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('d-m-Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                        <td><?php echo htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System'); ?></small></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></small></td>
                        <td><?php echo htmlspecialchars($log['description']); ?></small></td>
                        <td><?php echo $log['ip_address']; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="?filter=clear" class="btn btn-secondary" style="margin-top: 1rem;" onclick="return confirm('Clear all logs? This action cannot be undone.')">🗑️ Clear All Logs</a>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>