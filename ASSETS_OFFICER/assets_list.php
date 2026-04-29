<?php
// ASSETS_OFFICER/assets_list.php - List all assets with filters
$page_title = "All Assets";
include 'includes/asset_header.php';

// Show success message if asset was deleted
if(isset($_GET['deleted'])) {
    echo '<div class="alert alert-success">✅ Asset deleted successfully!</div>';
}
if(isset($_GET['updated'])) {
    echo '<div class="alert alert-success">✅ Asset updated successfully!</div>';
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT a.*, u.full_name as custodian_name 
        FROM assets a
        LEFT JOIN users u ON a.custodian_id = u.id
        WHERE 1=1";
$params = [];

if(!empty($status_filter)) {
    $sql .= " AND a.asset_status = ?";
    $params[] = $status_filter;
}
if(!empty($department_filter)) {
    $sql .= " AND a.department = ?";
    $params[] = $department_filter;
}
if(!empty($category_filter)) {
    $sql .= " AND a.category = ?";
    $params[] = $category_filter;
}
if(!empty($search)) {
    $sql .= " AND (a.asset_name LIKE ? OR a.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// Get distinct departments and categories for filters
$departments = $pdo->query("SELECT DISTINCT department FROM assets ORDER BY department")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM assets ORDER BY category")->fetchAll();

// Status counts for badge
$status_counts = [];
$count_stmt = $pdo->query("SELECT asset_status, COUNT(*) as count FROM assets GROUP BY asset_status");
while($row = $count_stmt->fetch()) {
    $status_counts[$row['asset_status']] = $row['count'];
}
?>

<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <h3><i class="fas fa-boxes"></i> All Assets</h3>
        <a href="register.php" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Register New Asset</a>
    </div>
    
    <!-- Filter Bar -->
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="">All Status</option>
                <option value="Nzima" <?php echo $status_filter == 'Nzima' ? 'selected' : ''; ?>>✅ Nzima</option>
                <option value="Inahitaji Service" <?php echo $status_filter == 'Inahitaji Service' ? 'selected' : ''; ?>>⚠️ Inahitaji Service</option>
                <option value="Mbovu" <?php echo $status_filter == 'Mbovu' ? 'selected' : ''; ?>>❌ Mbovu</option>
                <option value="Imeuzwa" <?php echo $status_filter == 'Imeuzwa' ? 'selected' : ''; ?>>💰 Imeuzwa</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Department</label>
            <select name="department">
                <option value="">All Departments</option>
                <?php foreach($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department_filter == $dept['department'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['department']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Category</label>
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <div class="filter-group" style="flex: 0 0 auto;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="assets_list.php" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Status Summary Badges -->
<div class="filter-bar" style="margin-bottom: 1rem;">
    <a href="assets_list.php?status=Nzima" class="btn btn-sm" style="background: #d4edda; color: #155724;">
        ✅ Nzima: <?php echo $status_counts['Nzima'] ?? 0; ?>
    </a>
    <a href="assets_list.php?status=Inahitaji Service" class="btn btn-sm" style="background: #fff3cd; color: #856404;">
        ⚠️ Needs Service: <?php echo $status_counts['Inahitaji Service'] ?? 0; ?>
    </a>
    <a href="assets_list.php?status=Mbovu" class="btn btn-sm" style="background: #f8d7da; color: #721c24;">
        ❌ Mbovu: <?php echo $status_counts['Mbovu'] ?? 0; ?>
    </a>
    <a href="assets_list.php?status=Imeuzwa" class="btn btn-sm" style="background: #e2e3e5; color: #383d41;">
        💰 Imeuzwa: <?php echo $status_counts['Imeuzwa'] ?? 0; ?>
    </a>
</div>

<div class="form-card">
    <?php if(count($assets) > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Custodian</th>
                        <th>Purchase Price</th>
                        <th>Current Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($assets as $asset): ?>
                    <tr>
                        <td>
                            <?php if($asset['asset_image'] && file_exists('../' . $asset['asset_image'])): ?>
                                <img src="../<?php echo $asset['asset_image']; ?>" class="asset-image">
                            <?php else: ?>
                                <div style="width:45px; height:45px; background:#f0f0f0; border-radius:6px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fas fa-box" style="color:#999;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($asset['asset_name']); ?></strong></td>
                        <td><?php echo $asset['category']; ?></td>
                        <td><?php echo $asset['department']; ?></td>
                        <td><?php echo htmlspecialchars($asset['custodian_name'] ?? 'Not assigned'); ?></td>
                        <td>TZS <?php echo number_format($asset['purchase_price'], 0); ?></td>
                        <td>TZS <?php echo number_format($asset['current_value'], 0); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '', $asset['asset_status']); ?>">
                                <?php echo $asset['asset_status']; ?>
                            </span>
                        </td>
                        <td>
                            <!-- View Button - Available to ALL -->
                            <a href="view_asset.php?id=<?php echo $asset['asset_id']; ?>" class="btn-sm btn-primary"><i class="fas fa-eye"></i> View</a>
                            
                            <!-- Service Button - Available to ALL -->
                            <a href="service.php?asset_id=<?php echo $asset['asset_id']; ?>" class="btn-sm btn-warning"><i class="fas fa-tools"></i> Service</a>
                            
                            <!-- Edit Button - Available to ALL (not just super_admin) -->
                            <a href="edit_asset.php?id=<?php echo $asset['asset_id']; ?>" class="btn-sm btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                            
                            <!-- Delete Button - Available to ALL with confirmation -->
                            <a href="delete_asset.php?id=<?php echo $asset['asset_id']; ?>" class="btn-sm btn-danger" onclick="return confirmDelete('<?php echo htmlspecialchars($asset['asset_name']); ?>')"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No assets found. <a href="register.php">Register your first asset</a></div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(assetName) {
    return confirm('Je, una uhakika unataka kufuta mali: ' + assetName + '?\n\nHii action haiwezi kutenduliwa!');
}
</script>

<?php include 'includes/asset_footer.php'; ?>