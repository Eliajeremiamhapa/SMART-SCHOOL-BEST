<?php
// ASSETS_OFFICER/edit_asset.php
$page_title = "Edit Asset";
include 'includes/asset_header.php';

$asset_id = $_GET['id'] ?? 0;
if(!$asset_id) {
    header('Location: assets_list.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM assets WHERE asset_id = ?");
$stmt->execute([$asset_id]);
$asset = $stmt->fetch();

if(!$asset) {
    header('Location: assets_list.php');
    exit();
}

// Get departments
$departments = $pdo->query("SELECT dept_name FROM departments WHERE is_active = 1 ORDER BY dept_name")->fetchAll();

// Get staff
$staff = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('super_admin', 'accountant', 'store_keeper', 'teacher') AND is_active = 1 ORDER BY full_name")->fetchAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_name = trim($_POST['asset_name']);
    $category = $_POST['category'];
    $department = $_POST['department'];
    $custodian_id = $_POST['custodian_id'] ?: null;
    $asset_status = $_POST['asset_status'];
    
    if(empty($asset_name) || empty($category) || empty($department)) {
        $error = "Please fill all required fields!";
    } else {
        $sql = "UPDATE assets SET asset_name = ?, category = ?, department = ?, custodian_id = ?, asset_status = ? WHERE asset_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$asset_name, $category, $department, $custodian_id, $asset_status, $asset_id]);
        
        $success = "Asset updated successfully!";
    }
}
?>

<?php if($success): ?>
    <div class="alert alert-success">✅ <?php echo $success; ?></div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger">❌ <?php echo $error; ?></div>
<?php endif; ?>

<div class="form-card">
    <h3><i class="fas fa-edit"></i> Edit Asset: <?php echo htmlspecialchars($asset['asset_name']); ?></h3>
    
    <form method="POST">
        <div class="two-columns">
            <div class="form-group">
                <label>Asset Name *</label>
                <input type="text" name="asset_name" required value="<?php echo htmlspecialchars($asset['asset_name']); ?>">
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select name="category" required>
                    <option value="Samani" <?php echo $asset['category'] == 'Samani' ? 'selected' : ''; ?>>Samani (Furniture)</option>
                    <option value="Elektroniki" <?php echo $asset['category'] == 'Elektroniki' ? 'selected' : ''; ?>>Elektroniki (Electronics)</option>
                    <option value="Magari" <?php echo $asset['category'] == 'Magari' ? 'selected' : ''; ?>>Magari (Vehicles)</option>
                    <option value="Majengo" <?php echo $asset['category'] == 'Majengo' ? 'selected' : ''; ?>>Majengo (Buildings)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Department *</label>
                <select name="department" required>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['dept_name']); ?>" <?php echo $asset['department'] == $dept['dept_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Custodian</label>
                <select name="custodian_id">
                    <option value="">-- Not Assigned --</option>
                    <?php foreach($staff as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $asset['custodian_id'] == $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['role']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="asset_status" required>
                    <option value="Nzima" <?php echo $asset['asset_status'] == 'Nzima' ? 'selected' : ''; ?>>Nzima (Good)</option>
                    <option value="Inahitaji Service" <?php echo $asset['asset_status'] == 'Inahitaji Service' ? 'selected' : ''; ?>>Inahitaji Service</option>
                    <option value="Mbovu" <?php echo $asset['asset_status'] == 'Mbovu' ? 'selected' : ''; ?>>Mbovu (Damaged)</option>
                    <option value="Imeuzwa" <?php echo $asset['asset_status'] == 'Imeuzwa' ? 'selected' : ''; ?>>Imeuzwa (Sold)</option>
                </select>
            </div>
        </div>
        
        <div style="margin-top: 1rem; text-align: right;">
            <a href="assets_list.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </div>
    </form>
</div>

<?php include 'includes/asset_footer.php'; ?>