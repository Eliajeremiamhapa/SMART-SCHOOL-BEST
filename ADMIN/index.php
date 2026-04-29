<?php
// ADMIN/index.php
require_once '../config/database.php';

// Only super_admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Super Admin Dashboard";
include 'includes/admin_header.php';

// Get school settings for primary/secondary toggle
$stmt = $pdo->query("SELECT * FROM school_settings LIMIT 1");
$school_settings = $stmt->fetch();

// Set default school type if not set
$school_type = $school_settings['school_type'] ?? 'both';
$default_grading = $school_settings['default_grading_system'] ?? 'primary';

// Get statistics based on school type
$stats = [];

// Total students (with school level filter if needed)
if ($school_type == 'primary') {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE is_active = 1 AND (school_level = 'primary' OR school_level IS NULL)");
    $stats['total_students'] = $stmt->fetch()['total'];
    $student_label = 'Pupils';
} elseif ($school_type == 'secondary') {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE is_active = 1 AND school_level = 'secondary'");
    $stats['total_students'] = $stmt->fetch()['total'];
    $student_label = 'Students';
} else {
    // Both - show separate counts
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE is_active = 1 AND (school_level = 'primary' OR school_level IS NULL)");
    $stats['primary_students'] = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE is_active = 1 AND school_level = 'secondary'");
    $stats['secondary_students'] = $stmt->fetch()['total'];
    $stats['total_students'] = $stats['primary_students'] + $stats['secondary_students'];
    $student_label = 'Total Students';
}

// Total teachers
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND is_active = 1");
$stats['total_teachers'] = $stmt->fetch()['total'];

// Total parents
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'parent' AND is_active = 1");
$stats['total_parents'] = $stmt->fetch()['total'];

// Recent activities
$stmt = $pdo->query("
    SELECT l.*, u.full_name 
    FROM system_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10
");
$recent_logs = $stmt->fetchAll();

// Locked accounts
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE login_attempts >= 5");
$stats['locked_accounts'] = $stmt->fetch()['total'];

// Database size
$stmt = $pdo->query("
    SELECT SUM(data_length + index_length) as size 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()
");
$db_size = $stmt->fetch()['size'];
$stats['db_size'] = $db_size ? round($db_size / 1024 / 1024, 2) : 0;

// Users by role
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stats['users_by_role'] = $stmt->fetchAll();
?>

<div class="container">
    <h1>🏫 Super Admin Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    
    <!-- School Info Banner with Toggle Switch -->
    <?php if ($school_settings): ?>
    <div class="alert alert-info" style="background:#e8f4fd; border-left:4px solid #2196f3;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <strong><?php echo htmlspecialchars($school_settings['school_name']); ?></strong><br>
                Academic Year: <?php echo $school_settings['academic_year']; ?> | 
                Current Term: <?php echo $school_settings['current_term']; ?> |
                School Type: 
                <?php 
                $type_labels = ['primary' => '🏫 Primary', 'secondary' => '🏛️ Secondary', 'both' => '🏫🏛️ Both'];
                echo $type_labels[$school_settings['school_type'] ?? 'both'];
                ?>
            </div>
            <div>
                <a href="school_settings.php" class="btn-sm">⚙️ Edit Settings</a>
            </div>
        </div>
        
        <!-- Toggle Switch for Primary/Secondary View -->
        <?php if ($school_type == 'both'): ?>
        <div style="margin-top: 1rem; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <span><strong>View Mode:</strong></span>
                <div style="display: flex; background: #fff; border-radius: 30px; overflow: hidden; border: 1px solid #ddd;">
                    <a href="?view=primary" class="toggle-option <?php echo (!isset($_GET['view']) || $_GET['view'] == 'primary') ? 'active' : ''; ?>" style="padding: 0.3rem 1rem; text-decoration: none; color: #333; transition: all 0.3s;">
                        🏫 Primary View
                    </a>
                    <a href="?view=secondary" class="toggle-option <?php echo (isset($_GET['view']) && $_GET['view'] == 'secondary') ? 'active' : ''; ?>" style="padding: 0.3rem 1rem; text-decoration: none; color: #333; transition: all 0.3s;">
                        🏛️ Secondary View
                    </a>
                </div>
                <small style="color: #666;">Toggle between primary and secondary school data views</small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><?php echo ($school_type == 'primary') ? '👧' : (($school_type == 'secondary') ? '👨‍🎓' : '📚'); ?></div>
            <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
            <div class="stat-label"><?php echo $student_label; ?></div>
            <?php if ($school_type == 'both'): ?>
            <small style="color:#666;">Primary: <?php echo $stats['primary_students']; ?> | Secondary: <?php echo $stats['secondary_students']; ?></small>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👩‍🏫</div>
            <div class="stat-value"><?php echo number_format($stats['total_teachers']); ?></div>
            <div class="stat-label">Teachers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👪</div>
            <div class="stat-value"><?php echo number_format($stats['total_parents']); ?></div>
            <div class="stat-label">Parents</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💾</div>
            <div class="stat-value"><?php echo $stats['db_size']; ?> MB</div>
            <div class="stat-label">Database Size</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🔒</div>
            <div class="stat-value"><?php echo $stats['locked_accounts']; ?></div>
            <div class="stat-label">Locked Accounts</div>
        </div>
    </div>
    
    <!-- Users by Role -->
    <div class="two-columns">
        <div class="form-card">
            <h3>📊 Users by Role</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Role</th><th>Count</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['users_by_role'] as $role): ?>
                        <tr>
                            <td><?php echo ucfirst($role['role']); ?></small></td>
                            <td><?php echo $role['count']; ?></small></td>
                            <td><a href="users.php?role=<?php echo $role['role']; ?>" class="btn-sm">View</a></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="form-card">
            <h3>⚙️ Quick Actions</h3>
            <div class="action-buttons" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <a href="users.php" class="btn btn-primary">➕ Add New User</a>
                <a href="school_settings.php" class="btn btn-primary">⚙️ School Settings</a>
                <a href="grading_system.php" class="btn btn-primary">📊 Grading System</a>
                <a href="fee_structure.php" class="btn btn-primary">💰 Fee Structure</a>
                <a href="backup.php" class="btn btn-primary">💾 Backup Database</a>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="form-card">
        <h3>📋 Recent System Activities</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Time</th><th>User</th><th>Action</th><th>IP Address</th></tr>
                </thead>
                <tbody>
                    <?php if (count($recent_logs) == 0): ?>
                        <tr><td colspan="4" style="text-align:center;">No activities recorded yet</small></td>
                    <?php else: ?>
                        <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td><?php echo date('d-m-Y H:i', strtotime($log['created_at'])); ?></small></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></small></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></small></td>
                            <td><?php echo $log['ip_address']; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="system_logs.php" class="btn-sm" style="float:right;">View All Logs →</a>
    </div>
</div>

<style>
.toggle-option.active {
    background: #1e3c72;
    color: white !important;
}
.toggle-option:hover:not(.active) {
    background: #e0e0e0;
}
</style>

<?php include 'includes/admin_footer.php'; ?>