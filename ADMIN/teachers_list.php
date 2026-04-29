<?php
// ADMIN/teachers_list.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Teachers List";
include 'includes/admin_header.php';

$error = '';
$success = '';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Teachers per page
$offset = ($page - 1) * $limit;

// Build query with filters
$where_conditions = ["role = 'teacher'"];
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
$total_teachers = $stmt->fetch()['total'];
$total_pages = ceil($total_teachers / $limit);

// Get teachers with pagination
$sql = "SELECT * FROM users $where_sql ORDER BY full_name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll();

// Handle Reset Password
if (isset($_GET['reset_password'])) {
    $user_id = $_GET['reset_password'];
    $new_password = 'password123';
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);
    $success = "✅ Password reset to 'password123' for teacher!";
}

// Handle Delete Teacher
if (isset($_GET['delete_id'])) {
    $user_id = $_GET['delete_id'];
    try {
        // Get teacher name before deleting
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $teacher_name = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$user_id]);
        $success = "✅ Teacher '" . htmlspecialchars($teacher_name) . "' deleted successfully!";
        
        // Redirect to refresh
        header("Location: teachers_list.php?message=deleted");
        exit();
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Show success message from redirect
if (isset($_GET['message']) && $_GET['message'] == 'deleted' && empty($success)) {
    $success = "✅ Teacher deleted successfully!";
}
?>

<div class="container">
    <h1>👩‍🏫 Teachers List</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <div class="action-buttons" style="margin-bottom: 1rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <a href="users.php" class="btn btn-primary" style="background: #28a745;">
                <i class="fas fa-user-plus"></i> Add New Teacher
            </a>
            <a href="teachers_list.php" class="btn btn-secondary">
                <i class="fas fa-sync-alt"></i> Refresh
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
                        <a href="teachers_list.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <p><strong>Total Teachers:</strong> <?php echo $total_teachers; ?></p>
            <p><strong>Showing:</strong> <?php echo count($teachers); ?> teachers</p>
        </div>
        
        <!-- Teachers Table -->
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
                    <?php if (empty($teachers)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                <?php if ($search || $status_filter): ?>
                                    No teachers found matching your search criteria.
                                <?php else: ?>
                                    No teachers found. Click "Add New Teacher" to add your first teacher.
                                <?php endif; ?>
                             </small></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?php echo $teacher['id']; ?></td>
                            <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                            <td><strong><?php echo htmlspecialchars($teacher['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($teacher['phone'] ?? '-'); ?></td>
                            <td>
                                <?php if ($teacher['is_active']): ?>
                                    <span style="color:green;">✅ Active</span>
                                <?php else: ?>
                                    <span style="color:red;">❌ Inactive</span>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn-sm">✏️ Edit</a>
                                <a href="?reset_password=<?php echo $teacher['id']; ?>" class="btn-sm" onclick="return confirm('Reset password for <?php echo htmlspecialchars($teacher['full_name']); ?>?')">🔑 Reset Pwd</a>
                                <a href="?delete_id=<?php echo $teacher['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete teacher <?php echo htmlspecialchars($teacher['full_name']); ?>? This action cannot be undone.')">🗑️ Delete</a>
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