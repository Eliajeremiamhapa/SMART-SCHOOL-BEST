<?php
// ADMIN/accountants_list.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Accountants List";
include 'includes/admin_header.php';

$error = '';
$success = '';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Accountants per page
$offset = ($page - 1) * $limit;

// Handle Reset Password
if (isset($_GET['reset_password'])) {
    $user_id = $_GET['reset_password'];
    $new_password = 'password123';
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);
    $success = "✅ Password reset to 'password123' for accountant!";
}

// Handle Delete Accountant
if (isset($_GET['delete_id'])) {
    $user_id = $_GET['delete_id'];
    try {
        // Get accountant name before deleting
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $accountant_name = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'accountant'");
        $stmt->execute([$user_id]);
        $success = "✅ Accountant '" . htmlspecialchars($accountant_name) . "' deleted successfully!";
        
        // Redirect to refresh
        header("Location: accountants_list.php?message=deleted");
        exit();
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Show success message from redirect
if (isset($_GET['message']) && $_GET['message'] == 'deleted' && empty($success)) {
    $success = "✅ Accountant deleted successfully!";
}

// Build query with filters
$where_conditions = ["role = 'accountant'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
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
$total_accountants = $stmt->fetch()['total'];
$total_pages = ceil($total_accountants / $limit);

// Get accountants with pagination
$sql = "SELECT * FROM users $where_sql ORDER BY full_name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accountants = $stmt->fetchAll();
?>

<div class="container">
    <h1>💰 Accountants List</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <div class="action-buttons" style="margin-bottom: 1rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <a href="users.php" class="btn btn-primary" style="background: #28a745;">
                <i class="fas fa-user-plus"></i> Add New Accountant
            </a>
            <a href="accountants_list.php" class="btn btn-secondary">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filter" style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label>🔍 Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, username, email, or phone..." 
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
                        <a href="accountants_list.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <p><strong>Total Accountants:</strong> <?php echo $total_accountants; ?></p>
            <p><strong>Showing:</strong> <?php echo count($accountants); ?> accountants</p>
        </div>
        
        <!-- Accountants Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accountants)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                <?php if ($search || $status_filter): ?>
                                    No accountants found matching your search criteria.
                                <?php else: ?>
                                    No accountants found. Click "Add New Accountant" to add your first accountant.
                                <?php endif; ?>
                             </small></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accountants as $accountant): ?>
                        <tr>
                            <td><?php echo $accountant['id']; ?></td>
                            <td><?php echo htmlspecialchars($accountant['username']); ?></td>
                            <td><strong><?php echo htmlspecialchars($accountant['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($accountant['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($accountant['phone'] ?? '-'); ?></td>
                            <td>
                                <?php if ($accountant['is_active']): ?>
                                    <span style="color:green;">✅ Active</span>
                                <?php else: ?>
                                    <span style="color:red;">❌ Inactive</span>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <a href="edit_accountant.php?id=<?php echo $accountant['id']; ?>" class="btn-sm">✏️ Edit</a>
                                <a href="?reset_password=<?php echo $accountant['id']; ?>" class="btn-sm" onclick="return confirm('Reset password for <?php echo htmlspecialchars($accountant['full_name']); ?>?')">🔑 Reset Pwd</a>
                                <a href="?delete_id=<?php echo $accountant['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete accountant <?php echo htmlspecialchars($accountant['full_name']); ?>? This action cannot be undone.')">🗑️ Delete</a>
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
</style>

<?php include 'includes/admin_footer.php'; ?>