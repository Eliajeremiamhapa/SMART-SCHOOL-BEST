<?php
// ASSETS/dashboard.php - Main Dashboard for Asset Management
$page_title = "Asset Dashboard";
include 'includes/asset_header.php';

// Get statistics
// Total assets
$stmt = $pdo->query("SELECT COUNT(*) as total FROM assets");
$total_assets = $stmt->fetch()['total'];

// Total by status
$stmt = $pdo->query("SELECT asset_status, COUNT(*) as count, SUM(current_value) as value FROM assets GROUP BY asset_status");
$status_stats = [];
while($row = $stmt->fetch()) {
    $status_stats[$row['asset_status']] = $row;
}

// Total maintenance cost this year
$stmt = $pdo->query("SELECT SUM(cost) as total FROM asset_maintenance WHERE YEAR(maintenance_date) = YEAR(CURDATE())");
$total_maintenance = $stmt->fetch()['total'] ?? 0;

// Assets needing service (status = 'Inahitaji Service')
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE asset_status = 'Inahitaji Service'");
$needs_service = $stmt->fetch()['count'] ?? 0;

// Damaged assets
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assets WHERE asset_status = 'Mbovu'");
$damaged = $stmt->fetch()['count'] ?? 0;

// Recent assets (last 5)
$recent_assets = $pdo->query("
    SELECT a.*, u.full_name as custodian_name 
    FROM assets a
    LEFT JOIN users u ON a.custodian_id = u.id
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent maintenance (last 5)
$recent_maintenance = $pdo->query("
    SELECT m.*, a.asset_name, a.asset_id
    FROM asset_maintenance m
    JOIN assets a ON m.asset_id = a.asset_id
    ORDER BY m.created_at DESC 
    LIMIT 5
")->fetchAll();

// Depreciation calculation example
$stmt = $pdo->query("
    SELECT 
        SUM(purchase_price) as total_purchase,
        SUM(current_value) as total_current,
        SUM(purchase_price) - SUM(current_value) as total_depreciation
    FROM assets
");
$depreciation_total = $stmt->fetch();
?>

<style>
    .dashboard-welcome {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    .dashboard-welcome h2 {
        margin-bottom: 0.5rem;
        font-size: 1.3rem;
    }
    .dashboard-welcome p {
        opacity: 0.9;
        font-size: 0.85rem;
    }
    .stat-card-danger {
        border-top-color: #dc3545;
    }
    .stat-card-warning {
        border-top-color: #ffc107;
    }
    .stat-card-success {
        border-top-color: #28a745;
    }
    .recent-table {
        margin-top: 0.5rem;
    }
    .recent-table td {
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
    }
</style>

<div class="dashboard-welcome">
    <h2><i class="fas fa-boxes"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Asset Manager'); ?>!</h2>
    <p>Manage school assets, track maintenance, and monitor depreciation all in one place.</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-box"></i>
        <div class="stat-value"><?php echo number_format($total_assets); ?></div>
        <div class="stat-label">Total Assets</div>
    </div>
    
    <div class="stat-card">
        <i class="fas fa-chart-line"></i>
        <div class="stat-value">TZS <?php echo number_format($depreciation_total['total_purchase'] ?? 0); ?></div>
        <div class="stat-label">Total Purchase Value</div>
    </div>
    
    <div class="stat-card stat-card-success">
        <i class="fas fa-check-circle"></i>
        <div class="stat-value"><?php echo number_format($status_stats['Nzima']['count'] ?? 0); ?></div>
        <div class="stat-label">✅ In Good Condition</div>
    </div>
    
    <div class="stat-card stat-card-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div class="stat-value"><?php echo number_format($needs_service); ?></div>
        <div class="stat-label">⚠️ Needs Service</div>
    </div>
    
    <div class="stat-card stat-card-danger">
        <i class="fas fa-times-circle"></i>
        <div class="stat-value"><?php echo number_format($damaged); ?></div>
        <div class="stat-label">❌ Damaged</div>
    </div>
    
    <div class="stat-card">
        <i class="fas fa-wrench"></i>
        <div class="stat-value">TZS <?php echo number_format($total_maintenance); ?></div>
        <div class="stat-label">Maintenance Cost (Year)</div>
    </div>
</div>

<!-- Depreciation Summary -->
<div class="form-card">
    <h4><i class="fas fa-chart-line"></i> Depreciation Summary</h4>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">
        <div>
            <div style="font-size: 0.7rem; color: #666;">Original Value</div>
            <div style="font-weight: bold; color: #1e3c72;">TZS <?php echo number_format($depreciation_total['total_purchase'] ?? 0); ?></div>
        </div>
        <div>
            <div style="font-size: 0.7rem; color: #666;">Current Value</div>
            <div style="font-weight: bold; color: #28a745;">TZS <?php echo number_format($depreciation_total['total_current'] ?? 0); ?></div>
        </div>
        <div>
            <div style="font-size: 0.7rem; color: #666;">Total Depreciation</div>
            <div style="font-weight: bold; color: #dc3545;">TZS <?php echo number_format($depreciation_total['total_depreciation'] ?? 0); ?></div>
        </div>
    </div>
</div>

<div class="two-columns">
    <!-- Recent Assets -->
    <div class="form-card">
        <h4><i class="fas fa-clock"></i> Recently Added Assets</h4>
        <?php if(count($recent_assets) > 0): ?>
            <table class="recent-table" style="width: 100%;">
                <?php foreach($recent_assets as $asset): ?>
                <tr>
                    <td style="width: 60%;">
                        <strong><?php echo htmlspecialchars($asset['asset_name']); ?></strong><br>
                        <small style="color: #666;"><?php echo $asset['category']; ?> | <?php echo $asset['department']; ?></small>
                    </td>
                    <td style="text-align: right;">
                        <span class="status-badge status-<?php echo str_replace(' ', '', $asset['asset_status']); ?>">
                            <?php echo $asset['asset_status']; ?>
                        </span>
                        <br>
                        <small>TZS <?php echo number_format($asset['current_value'], 0); ?></small>
                    </td>
                    <td style="text-align: right;">
                        <a href="view_asset.php?id=<?php echo $asset['asset_id']; ?>" class="btn-sm btn-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center;">No assets registered yet.</p>
            <p style="text-align: center; margin-top: 1rem;">
                <a href="register.php" class="btn btn-primary btn-sm">+ Register First Asset</a>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Recent Maintenance -->
    <div class="form-card">
        <h4><i class="fas fa-wrench"></i> Recent Maintenance Records</h4>
        <?php if(count($recent_maintenance) > 0): ?>
            <table class="recent-table" style="width: 100%;">
                <?php foreach($recent_maintenance as $maintenance): ?>
                <tr>
                    <td style="width: 55%;">
                        <strong><?php echo htmlspecialchars($maintenance['asset_name']); ?></strong><br>
                        <small style="color: #666;"><?php echo date('d M Y', strtotime($maintenance['maintenance_date'])); ?></small>
                    </td>
                    <td style="text-align: right;">
                        <span style="color: #dc3545; font-weight: bold;">TZS <?php echo number_format($maintenance['cost'], 0); ?></span>
                    </td>
                    <td style="text-align: right;">
                        <a href="service.php?asset_id=<?php echo $maintenance['asset_id']; ?>" class="btn-sm btn-warning">Add Service</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center;">No maintenance records yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="form-card" style="text-align: center;">
    <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem;">
        <a href="register.php" class="btn btn-success"><i class="fas fa-plus"></i> Register New Asset</a>
        <a href="assets_list.php?status=Inahitaji Service" class="btn btn-warning"><i class="fas fa-tools"></i> View Needs Service</a>
        <a href="assets_list.php?status=Mbovu" class="btn btn-danger"><i class="fas fa-exclamation-triangle"></i> View Damaged</a>
        <a href="depreciation_report.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Depreciation Report</a>
    </div>
</div>

<?php include 'includes/asset_footer.php'; ?>