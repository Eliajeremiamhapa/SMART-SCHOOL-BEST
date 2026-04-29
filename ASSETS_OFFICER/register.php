<?php
// ASSETS_OFFICER/register.php - Register New Asset
$page_title = "Register New Asset";
include 'includes/asset_header.php';

// Get departments
$departments = $pdo->query("SELECT dept_name FROM departments WHERE is_active = 1 ORDER BY dept_name")->fetchAll();

// Get users for custodian dropdown (only staff members)
$staff = $pdo->query("
    SELECT id, full_name, role 
    FROM users 
    WHERE role IN ('super_admin', 'accountant', 'store_keeper', 'teacher')
    AND is_active = 1 
    ORDER BY full_name
")->fetchAll();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_name = trim($_POST['asset_name']);
    $category = $_POST['category'];
    $department = $_POST['department'];
    $custodian_id = $_POST['custodian_id'] ?: null;
    $purchase_price = floatval($_POST['purchase_price']);
    $useful_life = intval($_POST['useful_life']);
    $purchase_date = $_POST['purchase_date'];
    $notes = trim($_POST['notes']);
    
    // Validate
    if(empty($asset_name) || empty($category) || empty($department) || $purchase_price <= 0 || $useful_life <= 0 || empty($purchase_date)) {
        $error = "❌ Please fill all required fields!";
    } else {
        // Calculate initial current value
        $years_old = date('Y') - date('Y', strtotime($purchase_date));
        if($years_old < 0) $years_old = 0;
        $annual_depreciation = $purchase_price / $useful_life;
        $current_value = $purchase_price - ($annual_depreciation * $years_old);
        if($current_value < 0) $current_value = 0;
        
        // Handle image upload
        $image_path = null;
        if(isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] === 0) {
            $upload_dir = '../uploads/assets/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['asset_image']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, $allowed)) {
                $image_name = time() . '_' . uniqid() . '.' . $ext;
                if(move_uploaded_file($_FILES['asset_image']['tmp_name'], $upload_dir . $image_name)) {
                    $image_path = 'uploads/assets/' . $image_name;
                }
            }
        }
        
        // Insert asset
        $sql = "INSERT INTO assets (asset_name, category, department, custodian_id, purchase_price, useful_life, purchase_date, current_value, asset_image, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$asset_name, $category, $department, $custodian_id, $purchase_price, $useful_life, $purchase_date, $current_value, $image_path, $notes, $_SESSION['user_id']]);
        
        $success = "✅ Asset registered successfully!";
        
        // NO REDIRECT - just like gallery.php
        // The form will stay, showing success message
    }
}
?>

<style>
    .image-preview {
        margin-top: 0.5rem;
        display: none;
    }
    .image-preview img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 3px;
    }
    .form-success {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border: 1px solid #c3e6cb;
    }
</style>

<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="form-card">
    <h3><i class="fas fa-plus-circle"></i> Register New Asset</h3>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="two-columns">
            <div class="form-group">
                <label>Asset Name *</label>
                <input type="text" name="asset_name" required placeholder="e.g., Toyota Hiace Bus, Epson Projector" value="<?php echo isset($_POST['asset_name']) && !$success ? htmlspecialchars($_POST['asset_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select name="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="Samani" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Samani' && !$success) ? 'selected' : ''; ?>>Samani (Furniture)</option>
                    <option value="Elektroniki" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Elektroniki' && !$success) ? 'selected' : ''; ?>>Elektroniki (Electronics)</option>
                    <option value="Magari" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Magari' && !$success) ? 'selected' : ''; ?>>Magari (Vehicles)</option>
                    <option value="Majengo" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Majengo' && !$success) ? 'selected' : ''; ?>>Majengo (Buildings)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Department *</label>
                <select name="department" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['dept_name']); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] == $dept['dept_name'] && !$success) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Custodian (Responsible Person)</label>
                <select name="custodian_id">
                    <option value="">-- Not Assigned --</option>
                    <?php foreach($staff as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo (isset($_POST['custodian_id']) && $_POST['custodian_id'] == $s['id'] && !$success) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['role']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Purchase Price (TZS) *</label>
                <input type="number" step="0.01" name="purchase_price" required placeholder="0.00" value="<?php echo isset($_POST['purchase_price']) && !$success ? htmlspecialchars($_POST['purchase_price']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Useful Life (Years) *</label>
                <input type="number" name="useful_life" required placeholder="e.g., 5" value="<?php echo isset($_POST['useful_life']) && !$success ? htmlspecialchars($_POST['useful_life']) : ''; ?>">
                <small style="color: #666;">Estimated years the asset will last</small>
            </div>
            
            <div class="form-group">
                <label>Purchase Date *</label>
                <input type="date" name="purchase_date" required value="<?php echo isset($_POST['purchase_date']) && !$success ? htmlspecialchars($_POST['purchase_date']) : date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label>Asset Image</label>
                <input type="file" name="asset_image" accept="image/*" id="asset_image">
                <div class="image-preview" id="imagePreview">
                    <img src="" alt="Preview">
                </div>
            </div>
            
            <div class="form-group" style="grid-column: span 2;">
                <label>Notes (Optional)</label>
                <textarea name="notes" rows="3" placeholder="Additional information about this asset..."><?php echo isset($_POST['notes']) && !$success ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
        </div>
        
        <div style="margin-top: 1rem; text-align: right;">
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-success">✅ Register Asset</button>
        </div>
    </form>
</div>

<script>
    // Image preview
    const imageInput = document.getElementById('asset_image');
    const imagePreview = document.getElementById('imagePreview');
    
    if(imageInput) {
        imageInput.addEventListener('change', function(e) {
            if(e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(loadEvent) {
                    imagePreview.style.display = 'block';
                    imagePreview.querySelector('img').src = loadEvent.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }
</script>

<?php include 'includes/asset_footer.php'; ?>