<?php
// ASSETS_OFFICER/maintenance_report.php
$page_title = "Maintenance Report";
include 'includes/asset_header.php';

// Get filter parameters
$asset_filter = $_GET['asset_id'] ?? '';
$year_filter = $_GET['year'] ?? date('Y');
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Build query for maintenance records
$sql = "SELECT m.*, a.asset_name, a.category, a.department, a.asset_status, u.full_name as custodian_name
        FROM asset_maintenance m
        JOIN assets a ON m.asset_id = a.asset_id
        LEFT JOIN users u ON a.custodian_id = u.id
        WHERE 1=1";
$params = [];

if(!empty($asset_filter)) {
    $sql .= " AND m.asset_id = ?";
    $params[] = $asset_filter;
}
if(!empty($year_filter)) {
    $sql .= " AND YEAR(m.maintenance_date) = ?";
    $params[] = $year_filter;
}
if(!empty($from_date) && !empty($to_date)) {
    $sql .= " AND m.maintenance_date BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date;
}

$sql .= " ORDER BY m.maintenance_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$maintenance_records = $stmt->fetchAll();

// Get all assets for dropdown filter
$assets = $pdo->query("SELECT asset_id, asset_name FROM assets ORDER BY asset_name")->fetchAll();

// Get available years for filter
$years = $pdo->query("SELECT DISTINCT YEAR(maintenance_date) as yr FROM asset_maintenance ORDER BY yr DESC")->fetchAll();

// Calculate totals
$total_cost = array_sum(array_column($maintenance_records, 'cost'));
$total_records = count($maintenance_records);

// Get top 5 assets by maintenance cost
$top_assets = $pdo->query("
    SELECT a.asset_id, a.asset_name, COUNT(m.maintenance_id) as count, SUM(m.cost) as total_cost
    FROM asset_maintenance m
    JOIN assets a ON m.asset_id = a.asset_id
    GROUP BY a.asset_id
    ORDER BY total_cost DESC
    LIMIT 5
")->fetchAll();

// Get maintenance by month
$monthly_data = [];
if(empty($asset_filter) && empty($from_date)) {
    $monthly = $pdo->prepare("
        SELECT MONTH(maintenance_date) as month, SUM(cost) as total, COUNT(*) as count
        FROM asset_maintenance
        WHERE YEAR(maintenance_date) = ?
        GROUP BY MONTH(maintenance_date)
        ORDER BY month
    ");
    $monthly->execute([$year_filter]);
    $monthly_data = $monthly->fetchAll();
}
?>

<style>
    .report-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .summary-card {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        text-align: center;
    }
    .summary-card .label {
        font-size: 0.7rem;
        color: #666;
        text-transform: uppercase;
    }
    .summary-card .value {
        font-size: 1.3rem;
        font-weight: bold;
        margin-top: 0.3rem;
    }
    .summary-card .value.warning {
        color: #f39c12;
    }
    .month-bar {
        background: #e9ecef;
        border-radius: 10px;
        height: 6px;
        overflow: hidden;
    }
    .month-bar-fill {
        background: #f39c12;
        height: 100%;
        border-radius: 10px;
    }
</style>

<div class="page-header">
    <h1><i class="fas fa-wrench"></i> Maintenance Report</h1>
    <p style="color: #666; margin-top: 0.3rem;">Track all service and maintenance costs</p>
</div>

<!-- Filter Bar -->
<div class="form-card">
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Select Asset</label>
            <select name="asset_id">
                <option value="">-- All Assets --</option>
                <?php foreach($assets as $asset): ?>
                    <option value="<?php echo $asset['asset_id']; ?>" <?php echo $asset_filter == $asset['asset_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($asset['asset_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Year</label>
            <select name="year">
                <option value="">All Years</option>
                <?php foreach($years as $year): ?>
                    <option value="<?php echo $year['yr']; ?>" <?php echo $year_filter == $year['yr'] ? 'selected' : ''; ?>>
                        <?php echo $year['yr']; ?>
                    </option>
                <?php endforeach; ?>
                <option value="<?php echo date('Y'); ?>" <?php echo $year_filter == date('Y') && !$from_date ? 'selected' : ''; ?>><?php echo date('Y'); ?></option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
        </div>
        
        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
        </div>
        
        <div class="filter-group" style="flex: 0 0 auto;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="maintenance_report.php" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="report-summary">
    <div class="summary-card">
        <div class="label">Total Maintenance Records</div>
        <div class="value"><?php echo $total_records; ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Total Maintenance Cost</div>
        <div class="value warning">TZS <?php echo number_format($total_cost, 0); ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Average Cost per Service</div>
        <div class="value">TZS <?php echo $total_records > 0 ? number_format($total_cost / $total_records, 0) : 0; ?></div>
    </div>
</div>

<!-- Top Assets by Maintenance Cost -->
<?php if(count($top_assets) > 0 && empty($asset_filter)): ?>
<div class="form-card">
    <h4><i class="fas fa-trophy"></i> Top 5 Assets by Maintenance Cost</h4>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Asset Name</th><th>Number of Services</th><th>Total Cost (TZS)</th><th>Average Cost (TZS)</th></tr>
            </thead>
            <tbody>
                <?php foreach($top_assets as $top): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($top['asset_name']); ?></strong></small></td>
                    <td><?php echo $top['count']; ?> times</small></td>
                    <td>TZS <?php echo number_format($top['total_cost'], 0); ?></small></td>
                    <td>TZS <?php echo number_format($top['total_cost'] / $top['count'], 0); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Monthly Breakdown -->
<?php if(count($monthly_data) > 0 && empty($asset_filter) && empty($from_date)): ?>
<div class="form-card">
    <h4><i class="fas fa-calendar-alt"></i> Monthly Maintenance Cost - <?php echo $year_filter; ?></h4>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Month</th><th>Number of Services</th><th>Total Cost (TZS)</th><th>Cost Distribution</th></tr>
            </thead>
            <tbody>
                <?php 
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $max_cost = max(array_column($monthly_data, 'total'));
                foreach($monthly_data as $month):
                    $percent = $max_cost > 0 ? ($month['total'] / $max_cost) * 100 : 0;
                ?>
                <tr>
                    <td><strong><?php echo $months[$month['month'] - 1]; ?></strong></small></td>
                    <td><?php echo $month['count']; ?></small></td>
                    <td>TZS <?php echo number_format($month['total'], 0); ?></small></td>
                    <td>
                        <div class="month-bar" style="width: 150px;">
                            <div class="month-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                     </small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Maintenance Records Table -->
<div class="form-card">
    <h4><i class="fas fa-list"></i> Maintenance Records</h4>
    
    <?php if(count($maintenance_records) > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Asset</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Cost (TZS)</th>
                        <th>Performed By</th>
                        <th>Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($maintenance_records as $record): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($record['maintenance_date'])); ?></small></td>
                        <td><strong><?php echo htmlspecialchars($record['asset_name']); ?></strong></small></td>
                        <td><?php echo $record['category']; ?></small></td>
                        <td><?php echo $record['department']; ?></small></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($record['description'], 0, 60))); if(strlen($record['description']) > 60) echo '...'; ?></small></td>
                        <td><span style="color: #f39c12; font-weight: bold;">TZS <?php echo number_format($record['cost'], 0); ?></span></small></td>
                        <td><?php echo htmlspecialchars($record['performed_by'] ?? '-'); ?></small></td>
                        <td><?php echo htmlspecialchars($record['invoice_number'] ?? '-'); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: #f8f9fa; font-weight: bold;">
                    <tr>
                        <td colspan="5"><strong>TOTAL</strong></td>
                        <td><strong style="color: #f39c12;">TZS <?php echo number_format($total_cost, 0); ?></strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No maintenance records found. <a href="assets_list.php">Go to assets and record service</a></div>
    <?php endif; ?>
</div>

<div class="form-card" style="text-align: center;">
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="assets_list.php" class="btn btn-primary"><i class="fas fa-boxes"></i> View All Assets</a>
        <a href="register.php" class="btn btn-success"><i class="fas fa-plus"></i> Register New Asset</a>
        <a href="javascript:void(0);" onclick="window.print();" class="btn btn-secondary"><i class="fas fa-print"></i> Print Report</a>
    </div>
</div>

<?php include 'includes/asset_footer.php'; ?>