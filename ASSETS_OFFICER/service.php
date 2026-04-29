<?php
// ASSETS/service.php - Record maintenance/service for an asset
$page_title = "Record Service";
include 'includes/asset_header.php';

$asset_id = $_GET['asset_id'] ?? 0;
if(!$asset_id) {
    header('Location: assets_list.php');
    exit();
}

// Get asset details
$stmt = $pdo->prepare("SELECT * FROM assets WHERE asset_id = ?");
$stmt->execute([$asset_id]);
$asset = $stmt->fetch();

if(!$asset) {
    header('Location: assets_list.php');
    exit();
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance_date = $_POST['maintenance_date'];
    $cost = floatval($_POST['cost']);
    $description = trim($_POST['description']);
    $performed_by = trim($_POST['performed_by']);
    $invoice_number = trim($_POST['invoice_number']);
    $update_status = isset($_POST['update_status']) && $_POST['update_status'] == 'yes';
    $new_status = $_POST['new_status'] ?? $asset['asset_status'];
    
    if(empty($description) || $cost <= 0) {
        $error = "Please fill all required fields!";
    } else {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Insert maintenance record
            $sql = "INSERT INTO asset_maintenance (asset_id, maintenance_date, cost, description, performed_by, invoice_number, recorded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$asset_id, $maintenance_date, $cost, $description, $performed_by, $invoice_number, $_SESSION['user_id']]);
            
            // 2. Auto-integrate with Accounting Expenses
            $expense_sql = "INSERT INTO expenses (expense_number, category, description, amount, expense_date, payment_method, notes, approved_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $expense_number = 'EXP-MAINT-' . date('Ymd') . '-' . rand(100, 999);
            $expense_stmt = $pdo->prepare($expense_sql);
            $expense_stmt->execute([
                $expense_number,
                'Repairs & Maintenance',
                'Asset Maintenance: ' . $asset['asset_name'] . ' - ' . substr($description, 0, 200),
                $cost,
                $maintenance_date,
                'bank_transfer',
                'Auto-generated from asset maintenance | Asset ID: ' . $asset_id . ' | Invoice: ' . $invoice_number,
                $_SESSION['full_name'] ?? 'System'
            ]);
            
            // 3. Update asset status if requested
            if($update_status && $new_status != $asset['asset_status']) {
                $update_sql = "UPDATE assets SET asset_status = ? WHERE asset_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$new_status, $asset_id]);
            }
            
            $pdo->commit();
            $success = "Service recorded successfully! Expense has been added to Accounting module automatically.";
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Failed to record service: " . $e->getMessage();
        }
    }
}

// Get maintenance history for this asset
$history = $pdo->prepare("
    SELECT * FROM asset_maintenance 
    WHERE asset_id = ? 
    ORDER BY maintenance_date DESC 
    LIMIT 10
");
$history->execute([$asset_id]);
$maintenance_history = $history->fetchAll();
?>

<style>
    .asset-info-box {
        background: #e8f4fd;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        border-left: 4px solid #1e3c72;
    }
    .history-item {
        padding: 0.5rem;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child {
        border-bottom: none;
    }
</style>

<?php if($success): ?>
    <div class="alert alert-success">✅ <?php echo $success; ?></div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger">❌ <?php echo $error; ?></div>
<?php endif; ?>

<div class="two-columns">
    <!-- Service Form -->
    <div class="form-card">
        <h3><i class="fas fa-tools"></i> Record Service for: <?php echo htmlspecialchars($asset['asset_name']); ?></h3>
        
        <div class="asset-info-box">
            <strong>Asset Details:</strong><br>
            Category: <?php echo $asset['category']; ?> | Department: <?php echo $asset['department']; ?><br>
            Current Status: 
            <span class="status-badge status-<?php echo str_replace(' ', '', $asset['asset_status']); ?>">
                <?php echo $asset['asset_status']; ?>
            </span><br>
            Current Value: TZS <?php echo number_format($asset['current_value'], 0); ?>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Service Date *</label>
                <input type="date" name="maintenance_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label>Service Cost (TZS) *</label>
                <input type="number" step="0.01" name="cost" required placeholder="0.00">
                <small style="color: #666;">This will be auto-added to Accounting Expenses</small>
            </div>
            
            <div class="form-group">
                <label>Service Description *</label>
                <textarea name="description" rows="3" required placeholder="e.g., Oil change, New parts, Cleaning, Repairs..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Performed By</label>
                <input type="text" name="performed_by" placeholder="Technician name or company">
            </div>
            
            <div class="form-group">
                <label>Invoice Number (Optional)</label>
                <input type="text" name="invoice_number" placeholder="Service invoice reference">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="update_status" value="yes" id="update_status">
                    Update asset status after this service
                </label>
            </div>
            
            <div class="form-group" id="status_field" style="display:none;">
                <label>New Status</label>
                <select name="new_status">
                    <option value="Nzima">Nzima (Good)</option>
                    <option value="Inahitaji Service">Inahitaji Service (Needs Service)</option>
                    <option value="Mbovu">Mbovu (Damaged)</option>
                    <option value="Imeuzwa">Imeuzwa (Sold)</option>
                </select>
            </div>
            
            <div style="margin-top: 1rem;">
                <a href="assets_list.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Record Service</button>
            </div>
        </form>
    </div>
    
    <!-- Maintenance History -->
    <div class="form-card">
        <h4><i class="fas fa-history"></i> Service History</h4>
        <?php if(count($maintenance_history) > 0): ?>
            <?php foreach($maintenance_history as $record): ?>
                <div class="history-item">
                    <div style="display: flex; justify-content: space-between;">
                        <strong><?php echo date('d M Y', strtotime($record['maintenance_date'])); ?></strong>
                        <span style="color: #dc3545; font-weight: bold;">TZS <?php echo number_format($record['cost'], 0); ?></span>
                    </div>
                    <div style="font-size: 0.8rem; color: #666; margin-top: 0.25rem;">
                        <?php echo nl2br(htmlspecialchars(substr($record['description'], 0, 100))); ?>
                        <?php if(strlen($record['description']) > 100) echo '...'; ?>
                    </div>
                    <?php if($record['performed_by']): ?>
                        <div style="font-size: 0.7rem; color: #999;">By: <?php echo htmlspecialchars($record['performed_by']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666; text-align: center;">No service records yet.</p>
        <?php endif; ?>
        
        <?php if(count($maintenance_history) >= 10): ?>
            <div style="text-align: center; margin-top: 0.5rem;">
                <a href="maintenance_report.php?asset_id=<?php echo $asset_id; ?>" class="btn-sm btn-primary">View Full History</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('update_status').addEventListener('change', function() {
        document.getElementById('status_field').style.display = this.checked ? 'block' : 'none';
    });
</script>

<?php include 'includes/asset_footer.php'; ?>