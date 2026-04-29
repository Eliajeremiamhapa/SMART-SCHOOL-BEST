<?php
// ASSETS_OFFICER/view_asset.php
$page_title = "View Asset Details";
include 'includes/asset_header.php';

$asset_id = $_GET['id'] ?? 0;
if(!$asset_id) {
    header('Location: assets_list.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as custodian_name 
    FROM assets a
    LEFT JOIN users u ON a.custodian_id = u.id
    WHERE a.asset_id = ?
");
$stmt->execute([$asset_id]);
$asset = $stmt->fetch();

if(!$asset) {
    header('Location: assets_list.php');
    exit();
}

// Get maintenance history
$history = $pdo->prepare("
    SELECT * FROM asset_maintenance 
    WHERE asset_id = ? 
    ORDER BY maintenance_date DESC
");
$history->execute([$asset_id]);
$maintenance_history = $history->fetchAll();
?>

<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h3><i class="fas fa-box"></i> Asset Details: <?php echo htmlspecialchars($asset['asset_name']); ?></h3>
        <div>
            <a href="assets_list.php" class="btn btn-secondary btn-sm">← Back to List</a>
            <a href="service.php?asset_id=<?php echo $asset['asset_id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-tools"></i> Record Service</a>
        </div>
    </div>
    <hr>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div>
            <table style="width: 100%;">
                <tr><td style="padding: 8px 0;"><strong>Asset Name:</strong></td><td><?php echo htmlspecialchars($asset['asset_name']); ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Category:</strong></td><td><?php echo $asset['category']; ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Department:</strong></td><td><?php echo $asset['department']; ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Custodian:</strong></td><td><?php echo htmlspecialchars($asset['custodian_name'] ?? 'Not assigned'); ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Status:</strong></td><td><span class="status-badge status-<?php echo str_replace(' ', '', $asset['asset_status']); ?>"><?php echo $asset['asset_status']; ?></span></td></tr>
            </table>
        </div>
        <div>
            <table style="width: 100%;">
                <tr><td style="padding: 8px 0;"><strong>Purchase Price:</strong></td><td>TZS <?php echo number_format($asset['purchase_price'], 0); ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Current Value:</strong></td><td>TZS <?php echo number_format($asset['current_value'], 0); ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Useful Life:</strong></td><td><?php echo $asset['useful_life']; ?> years</td></tr>
                <tr><td style="padding: 8px 0;"><strong>Purchase Date:</strong></td><td><?php echo date('d M Y', strtotime($asset['purchase_date'])); ?></td></tr>
                <tr><td style="padding: 8px 0;"><strong>Registered:</strong></td><td><?php echo date('d M Y', strtotime($asset['created_at'])); ?></td></tr>
            </table>
        </div>
    </div>
    
    <?php if($asset['asset_image'] && file_exists('../' . $asset['asset_image'])): ?>
    <div style="margin-top: 1rem; text-align: center;">
        <img src="../<?php echo $asset['asset_image']; ?>" style="max-width: 300px; max-height: 300px; border-radius: 8px; border: 1px solid #ddd;">
    </div>
    <?php endif; ?>
    
    <?php if($asset['notes']): ?>
    <div style="margin-top: 1rem;">
        <strong>Notes:</strong>
        <p style="margin-top: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 6px;"><?php echo nl2br(htmlspecialchars($asset['notes'])); ?></p>
    </div>
    <?php endif; ?>
</div>

<div class="form-card">
    <h4><i class="fas fa-history"></i> Maintenance History</h4>
    <?php if(count($maintenance_history) > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Date</th><th>Description</th><th>Cost (TZS)</th><th>Performed By</th><th>Invoice</th></tr>
                </thead>
                <tbody>
                    <?php foreach($maintenance_history as $record): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($record['maintenance_date'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($record['description'], 0, 100))); if(strlen($record['description']) > 100) echo '...'; ?></td>
                        <td>TZS <?php echo number_format($record['cost'], 0); ?></td>
                        <td><?php echo htmlspecialchars($record['performed_by'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($record['invoice_number'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #666;">No maintenance records found. <a href="service.php?asset_id=<?php echo $asset['asset_id']; ?>">Record first service</a></p>
    <?php endif; ?>
</div>

<?php include 'includes/asset_footer.php'; ?>