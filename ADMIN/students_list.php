<?php
// ADMIN/students_list.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Students List";
include 'includes/admin_header.php';

$error = '';
$success = '';
$search = $_GET['search'] ?? '';
$class_filter = $_GET['class'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Students per page
$offset = ($page - 1) * $limit;

// Get all distinct classes for filter dropdown
$classes = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class")->fetchAll();

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR student_number LIKE ? OR class LIKE ? OR parent_phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($class_filter)) {
    $where_conditions[] = "class = ?";
    $params[] = $class_filter;
}

$where_sql = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM students $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_students = $stmt->fetch()['total'];
$total_pages = ceil($total_students / $limit);

// Get students with pagination
$sql = "SELECT * FROM students $where_sql ORDER BY class, full_name LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Handle Delete Student
if (isset($_GET['delete_id'])) {
    $student_id = $_GET['delete_id'];
    try {
        $pdo->beginTransaction();
        
        // Get student number before deleting
        $stmt = $pdo->prepare("SELECT student_number, full_name FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student_data = $stmt->fetch();
        
        if ($student_data) {
            // Delete from students table
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            
            // Delete associated user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$student_data['student_number']]);
            
            $pdo->commit();
            $success = "✅ Student '" . htmlspecialchars($student_data['full_name']) . "' deleted successfully!";
            
            // Redirect to refresh the page
            header("Location: students_list.php?message=deleted");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Show success message from redirect
if (isset($_GET['message']) && $_GET['message'] == 'deleted' && empty($success)) {
    $success = "✅ Student deleted successfully!";
}
?>

<div class="container">
    <h1>👨‍🎓 Students List</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <div class="action-buttons" style="margin-bottom: 1rem; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <a href="add_student.php" class="btn btn-primary" style="background: #28a745;">
                <i class="fas fa-user-plus"></i> Add New Student
            </a>
            <a href="students_list.php" class="btn btn-secondary">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filter" style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label>🔍 Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, ID, class, or parent phone..." 
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label>📚 Filter by Class</label>
                    <select name="class" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">-- All Classes --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['class']); ?>" <?php echo $class_filter == $c['class'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <?php if ($search || $class_filter): ?>
                        <a href="students_list.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Summary -->
        <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <p><strong>Total Students:</strong> <?php echo $total_students; ?></p>
            <p><strong>Showing:</strong> <?php echo count($students); ?> students</p>
        </div>
        
        <!-- Students Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Number</th>
                        <th>Full Name</th>
                        <th>Class</th>
                        <th>Parent Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                <?php if ($search || $class_filter): ?>
                                    No students found matching your search criteria.
                                <?php else: ?>
                                    No students found. Click "Add New Student" to add your first student.
                                <?php endif; ?>
                             </small></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                            <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                            <td><?php echo htmlspecialchars($student['parent_phone']); ?></td>
                            <td>
                                <?php if ($student['is_active']): ?>
                                    <span style="color:green;">✅ Active</span>
                                <?php else: ?>
                                    <span style="color:red;">❌ Inactive</span>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <a href="../STUDENTS/index.php" class="btn-sm" style="background:#17a2b8;" target="_blank">👤 View</a>
                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn-sm">✏️ Edit</a>
                                <a href="?delete_id=<?php echo $student['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete student <?php echo htmlspecialchars($student['full_name']); ?>? This action cannot be undone.')">🗑️ Delete</a>
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
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($class_filter); ?>" class="btn-sm">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($class_filter); ?>" 
                   class="btn-sm <?php echo $i == $page ? 'active' : ''; ?>" 
                   style="<?php echo $i == $page ? 'background:#1e3c72;' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&class=<?php echo urlencode($class_filter); ?>" class="btn-sm">Next »</a>
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