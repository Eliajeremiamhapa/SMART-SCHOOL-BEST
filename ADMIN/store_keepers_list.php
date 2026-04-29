<?php
// ADMIN/store_keepers_list.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Assets Officers List";
include 'includes/admin_header.php';

$error = '';
$success = '';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Store keepers per page
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = ["role = 'store_keeper'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "is_active = ?";
    $params[] = $status_filter == 'active' ? 1 : 0;
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM users $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_store_keepers = $stmt->fetch()['total'];
$total_pages = ceil($total_store_keepers / $limit);

// Get store keepers with pagination
$sql = "SELECT * FROM users $where_sql ORDER BY full_name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$store_keepers = $stmt->fetchAll();

// Get asset statistics for each store keeper (how many assets they manage as custodian)
foreach ($store_keepers as &$keeper) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as asset_count FROM assets WHERE custodian_id = ?");
    $stmt->execute([$keeper['id']]);
    $keeper['asset_count'] = $stmt->fetch()['asset_count'];
    
    // Get assets by status managed by this custodian
    $stmt = $pdo->prepare("
        SELECT asset_status, COUNT(*) as count 
        FROM assets 
        WHERE custodian_id = ? 
        GROUP BY asset_status
    ");
    $stmt->execute([$keeper['id']]);
    $keeper['asset_statuses'] = $stmt->fetchAll();
}

// Handle Reset Password
if (isset($_GET['reset_password'])) {
    $user_id = $_GET['reset_password'];
    $new_password = 'password123';
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);
    $success = "✅ Password reset to 'password123' for Assets Officer!";
}

// Handle Delete Store Keeper
if (isset($_GET['delete_id'])) {
    $user_id = $_GET['delete_id'];
    try {
        // Get store keeper name before deleting
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $keeper_name = $stmt->fetchColumn();
        
        // Check if they have assets assigned as custodian
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE custodian_id = ?");
        $stmt->execute([$user_id]);
        $asset_count = $stmt->fetch()['count'];
        
        if ($asset_count > 0) {
            // Option to reassign assets first or prevent deletion
            $error = "❌ Cannot delete Assets Officer because they are custodian for $asset_count asset(s). Please reassign assets first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'store_keeper'");
            $stmt->execute([$user_id]);
            $success = "✅ Assets Officer '" . htmlspecialchars($keeper_name) . "' deleted successfully!";
            
            // Redirect to refresh
            header("Location: store_keepers_list.php?message=deleted");
            exit();
        }
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle Toggle Status (Activate/Deactivate)
if (isset($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_status = $stmt->fetchColumn();
    $new_status = $current_status ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    $status_text = $new_status ? 'activated' : 'deactivated';
    $success = "✅ Assets Officer account $status_text successfully!";
    
    // Redirect to refresh
    header("Location: store_keepers_list.php?message=toggled");
    exit();
}

// Show success message from redirect
if (isset($_GET['message']) && empty($success)) {
    if ($_GET['message'] == 'deleted') {
        $success = "✅ Assets Officer deleted successfully!";
    } elseif ($_GET['message'] == 'toggled') {
        $success = "✅ Assets Officer status updated successfully!";
    }
}

// Get overall statistics for store keepers
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
    FROM users WHERE role = 'store_keeper'";
$overall_stats = $pdo->query($stats_sql)->fetch();
?>

<div class="container">
    <h1>🏢 Assets Officers (Store Keepers) List</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-value"><?php echo $overall_stats['total'] ?? 0; ?></div>
            <div class="stat-label">Total Assets Officers</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $overall_stats['active'] ?? 0; ?></div>
            <div class="stat-label">✅ Active</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $overall_stats['inactive'] ?? 0; ?></div>
            <div class="stat-label">❌ Inactive</div>
        </div>
    </div>
    
    <div class="form-card">
        <div class="action-buttons" style="margin-bottom: 1rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <a href="users.php" class="btn btn-primary" style="background: #28a745;">
                <i class="fas fa-user-plus"></i> Add New Assets Officer
            </a>
            <a href="store_keepers_list.php" class="btn btn-secondary">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
            <a href="../ASSETS_OFFICER/dashboard.php" class="btn btn-primary" style="background: #17a2b8;" target="_blank">
                <i class="fas fa-boxes"></i> Go to Asset Management
            </a>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filter" style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label>🔍 Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, username, or email..." 
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label>📊 Status</label>
                    <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">-- All --</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <?php if ($search || $status_filter): ?>
                        <a href="store_keepers_list.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <p><strong>Total Assets Officers:</strong> <?php echo $total_store_keepers; ?></p>
            <p><strong>Showing:</strong> <?php echo count($store_keepers); ?> officers</p>
        </div>
        
        <!-- Store Keepers Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Assets Managed</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($store_keepers)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem;">
                                <?php if ($search || $status_filter): ?>
                                    No Assets Officers found matching your search criteria.
                                <?php else: ?>
                                    No Assets Officers registered yet. Click "Add New Assets Officer" to add your first Assets Officer.
                                <?php endif; ?>
                             </small></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($store_keepers as $keeper): ?>
                        <tr>
                            <td><?php echo $keeper['id']; ?></td>
                            <td><?php echo htmlspecialchars($keeper['username']); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($keeper['full_name']); ?></strong></small></td>
                            <td><?php echo htmlspecialchars($keeper['email'] ?? '-'); ?></small></small></td>
                            <td><?php echo htmlspecialchars($keeper['phone'] ?? '-'); ?></small></small></td>
                            <td>
                                <span style="font-weight: bold; color: #17a2b8;"><?php echo $keeper['asset_count']; ?> assets</span>
                                <?php if (!empty($keeper['asset_statuses'])): ?>
                                    <br>
                                    <small>
                                        <?php foreach ($keeper['asset_statuses'] as $status): ?>
                                            <span class="status-badge status-<?php echo str_replace(' ', '', $status['asset_status']); ?>" style="font-size: 0.6rem;">
                                                <?php echo $status['asset_status']; ?>: <?php echo $status['count']; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </small>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <?php if ($keeper['is_active']): ?>
                                    <span style="color:green;">✅ Active</span>
                                <?php else: ?>
                                    <span style="color:red;">❌ Inactive</span>
                                <?php endif; ?>
                             </small></small></td>
                            <td><?php echo date('d-m-Y', strtotime($keeper['created_at'])); ?></small></td>
                            <td>
                                <!-- FIXED: Changed from edit_user.php to edit_store_keeper.php -->
                                <a href="edit_store_keeper.php?id=<?php echo $keeper['id']; ?>" class="btn-sm">✏️ Edit</a>
                                <a href="?toggle_status=<?php echo $keeper['id']; ?>" class="btn-sm" style="background: #17a2b8;" onclick="return confirm('<?php echo $keeper['is_active'] ? 'Deactivate' : 'Activate'; ?> account for <?php echo htmlspecialchars($keeper['full_name']); ?>?')">
                                    <?php echo $keeper['is_active'] ? '🔒 Deactivate' : '🔓 Activate'; ?>
                                </a>
                                <a href="?reset_password=<?php echo $keeper['id']; ?>" class="btn-sm" onclick="return confirm('Reset password for <?php echo htmlspecialchars($keeper['full_name']); ?>?')">🔑 Reset Pwd</a>
                                <?php if ($keeper['asset_count'] == 0): ?>
                                    <a href="?delete_id=<?php echo $keeper['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete Assets Officer <?php echo htmlspecialchars($keeper['full_name']); ?>? This action cannot be undone.')">🗑️ Delete</a>
                                <?php else: ?>
                                    <span class="btn-sm" style="background:#6c757d; cursor: not-allowed;" title="Cannot delete: Has <?php echo $keeper['asset_count']; ?> assets assigned">🚫 Has Assets</span>
                                <?php endif; ?>
                                <a href="../ASSETS_OFFICER/dashboard.php?user_id=<?php echo $keeper['id']; ?>" class="btn-sm" style="background:#28a745;" target="_blank">
                                    🏢 Login as Officer
                                </a>
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination" style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn-sm">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                   class="btn-sm <?php echo $i == $page ? 'active' : ''; ?>" 
                   style="<?php echo $i == $page ? 'background:#1e3c72;' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn-sm">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .btn-sm.active {
        background: #1e3c72;
        color: white;
    }
    .search-filter label {
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
        display: block;
    }
    .status-badge {
        display: inline-block;
        padding: 0.1rem 0.4rem;
        border-radius: 10px;
        font-size: 0.6rem;
        font-weight: bold;
        margin-right: 0.2rem;
        margin-top: 0.2rem;
    }
    .status-nzima {
        background: #d4edda;
        color: #155724;
    }
    .status-inahitaji-service {
        background: #fff3cd;
        color: #856404;
    }
    .status-mbovu {
        background: #f8d7da;
        color: #721c24;
    }
    .status-imeuzwa {
        background: #e2e3e5;
        color: #383d41;
    }
</style>

<?php include 'includes/admin_footer.php'; ?>