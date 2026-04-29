<?php
// ASSETS_OFFICER/depreciation_report.php
$page_title = "Depreciation Report";
include 'includes/asset_header.php';

// Get filter parameters
$department_filter = $_GET['department'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build query
$sql = "SELECT a.*, u.full_name as custodian_name 
        FROM assets a
        LEFT JOIN users u ON a.custodian_id = u.id
        WHERE 1=1";
$params = [];

if(!empty($department_filter)) {
    $sql .= " AND a.department = ?";
    $params[] = $department_filter;
}
if(!empty($category_filter)) {
    $sql .= " AND a.category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY (a.purchase_price - a.current_value) DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();

// Get departments and categories for filters
$departments = $pdo->query("SELECT DISTINCT department FROM assets ORDER BY department")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM assets ORDER BY category")->fetchAll();

// Calculate totals
$total_purchase = array_sum(array_column($assets, 'purchase_price'));
$total_current = array_sum(array_column($assets, 'current_value'));
$total_depreciation = $total_purchase - $total_current;
$avg_depreciation_percent = $total_purchase > 0 ? round(($total_depreciation / $total_purchase) * 100, 2) : 0;
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
    .summary-card .value.positive {
        color: #dc3545;
    }
    .summary-card .value.negative {
        color: #28a745;
    }
    .depreciation-bar {
        background: #e9ecef;
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .depreciation-bar-fill {
        background: #dc3545;
        height: 100%;
        border-radius: 10px;
    }
</style>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Depreciation Report</h1>
    <p style="color: #666; margin-top: 0.3rem;">Asset value depreciation over time</p>
</div>

<!-- Filter Bar -->
<div class="form-card">
    <form method="GET" class="filter-bar">
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
        
        <div class="filter-group" style="flex: 0 0 auto;">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="depreciation_report.php" class="btn btn-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="report-summary">
    <div class="summary-card">
        <div class="label">Total Purchase Value</div>
        <div class="value">TZS <?php echo number_format($total_purchase, 0); ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Current Value</div>
        <div class="value negative">TZS <?php echo number_format($total_current, 0); ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Total Depreciation</div>
        <div class="value positive">TZS <?php echo number_format($total_depreciation, 0); ?></div>
        <div class="depreciation-bar">
            <div class="depreciation-bar-fill" style="width: <?php echo $avg_depreciation_percent; ?>%;"></div>
        </div>
        <small style="color: #666;"><?php echo $avg_depreciation_percent; ?>% of original value</small>
    </div>
    <div class="summary-card">
        <div class="label">Number of Assets</div>
        <div class="value"><?php echo count($assets); ?></div>
    </div>
</div>

<!-- Depreciation Table -->
<div class="form-card">
    <h4><i class="fas fa-table"></i> Asset Depreciation Details</h4>
    
    <?php if(count($assets) > 0): ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Asset Name</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Purchase Price (TZS)</th>
                        <th>Current Value (TZS)</th>
                        <th>Depreciation (TZS)</th>
                        <th>Depreciation %</th>
                        <th>Age (Years)</th>
                        <th>Remaining Life</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($assets as $asset): 
                        $depreciation = $asset['purchase_price'] - $asset['current_value'];
                        $depreciation_percent = $asset['purchase_price'] > 0 ? round(($depreciation / $asset['purchase_price']) * 100, 2) : 0;
                        $age = date('Y') - date('Y', strtotime($asset['purchase_date']));
                        $remaining_life = max(0, $asset['useful_life'] - $age);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($asset['asset_name']); ?></strong></td>
                        <td><?php echo $asset['category']; ?></small></td>
                        <td><?php echo $asset['department']; ?></small></td>
                        <td>TZS <?php echo number_format($asset['purchase_price'], 0); ?></small></td>
                        <td>TZS <?php echo number_format($asset['current_value'], 0); ?></small></td>
                        <td><span style="color: #dc3545;">TZS <?php echo number_format($depreciation, 0); ?></span></small></td>
                        <td>
                            <span style="color: <?php echo $depreciation_percent > 50 ? '#dc3545' : '#f39c12'; ?>;">
                                <?php echo $depreciation_percent; ?>%
                            </span>
                            <div class="depreciation-bar" style="width: 100%; margin-top: 0.2rem;">
                                <div class="depreciation-bar-fill" style="width: <?php echo $depreciation_percent; ?>%;"></div>
                            </div>
                         </small></td>
                        <td><?php echo $age; ?> years</small></td>
                        <td><span style="color: <?php echo $remaining_life < 2 ? '#dc3545' : '#28a745'; ?>;"><?php echo $remaining_life; ?> years</span></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: #f8f9fa; font-weight: bold;">
                    <tr>
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td><strong>TZS <?php echo number_format($total_purchase, 0); ?></strong></td>
                        <td><strong>TZS <?php echo number_format($total_current, 0); ?></strong></td>
                        <td><strong style="color: #dc3545;">TZS <?php echo number_format($total_depreciation, 0); ?></strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No assets found. <a href="register.php">Register your first asset</a></div>
    <?php endif; ?>
</div>

<!-- Depreciation by Category -->
<?php if(count($assets) > 0): 
    $category_data = [];
    foreach($assets as $asset) {
        if(!isset($category_data[$asset['category']])) {
            $category_data[$asset['category']] = ['purchase' => 0, 'current' => 0];
        }
        $category_data[$asset['category']]['purchase'] += $asset['purchase_price'];
        $category_data[$asset['category']]['current'] += $asset['current_value'];
    }
?>
<div class="form-card">
    <h4><i class="fas fa-chart-pie"></i> Depreciation by Category</h4>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Category</th><th>Total Purchase (TZS)</th><th>Current Value (TZS)</th><th>Depreciation (TZS)</th><th>Depreciation %</th></tr>
            </thead>
            <tbody>
                <?php foreach($category_data as $cat => $data): 
                    $dep = $data['purchase'] - $data['current'];
                    $percent = $data['purchase'] > 0 ? round(($dep / $data['purchase']) * 100, 2) : 0;
                ?>
                <tr>
                    <td><strong><?php echo $cat; ?></strong></td>
                    <td>TZS <?php echo number_format($data['purchase'], 0); ?></td>
                    <td>TZS <?php echo number_format($data['current'], 0); ?></small></td>
                    <td><span style="color: #dc3545;">TZS <?php echo number_format($dep, 0); ?></span></td>
                    <td><?php echo $percent; ?>%</small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="form-card" style="text-align: center;">
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="assets_list.php" class="btn btn-primary"><i class="fas fa-boxes"></i> View All Assets</a>
        <a href="register.php" class="btn btn-success"><i class="fas fa-plus"></i> Register New Asset</a>
        <a href="javascript:void(0);" onclick="window.print();" class="btn btn-secondary"><i class="fas fa-print"></i> Print Report</a>
    </div>
</div>

<?php include 'includes/asset_footer.php'; ?>