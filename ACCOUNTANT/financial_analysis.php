<?php
require_once 'config/database.php';
$page_title = "Financial Analysis";
include 'includes/header.php';

// Get monthly trends (last 6 months)
$trends = $pdo->query("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(amount) as total_collection
    FROM transactions
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

// Get budget vs actual (simulated budget)
$budget_vs_actual = $pdo->query("
    SELECT 
        'Revenue' as type,
        COALESCE(SUM(amount), 0) as actual,
        COALESCE(SUM(amount), 0) * 1.1 as budget
    FROM transactions
    WHERE MONTH(transaction_date) = MONTH(CURDATE())
    UNION ALL
    SELECT 
        'Expenses' as type,
        COALESCE(SUM(amount), 0) as actual,
        COALESCE(SUM(amount), 0) * 0.9 as budget
    FROM expenses
    WHERE MONTH(expense_date) = MONTH(CURDATE())
")->fetchAll();

// Projection for next month (based on average)
$avg_collection = $pdo->query("SELECT AVG(monthly_total) as avg_monthly FROM (SELECT SUM(amount) as monthly_total FROM transactions GROUP BY MONTH(transaction_date)) as sub")->fetch();
$projection = $avg_collection['avg_monthly'] ?? 0;

// Top revenue categories
$top_categories = $pdo->query("
    SELECT rc.category_name, COALESCE(SUM(t.amount), 0) as total
    FROM revenue_categories rc
    LEFT JOIN invoices i ON i.category_id = rc.id
    LEFT JOIN transactions t ON t.invoice_id = i.id
    GROUP BY rc.id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// Debt aging
$debt_aging = $pdo->query("
    SELECT 
        CASE 
            WHEN DATEDIFF(CURDATE(), due_date) <= 30 THEN '0-30 days'
            WHEN DATEDIFF(CURDATE(), due_date) <= 60 THEN '31-60 days'
            WHEN DATEDIFF(CURDATE(), due_date) <= 90 THEN '61-90 days'
            ELSE '90+ days'
        END as aging_period,
        COALESCE(SUM(balance), 0) as total_debt
    FROM invoices
    WHERE status != 'paid'
    GROUP BY aging_period
    ORDER BY FIELD(aging_period, '0-30 days', '31-60 days', '61-90 days', '90+ days')
")->fetchAll();

// Ensure we have data for charts
$trends_labels = array_column($trends, 'month');
$trends_data = array_column($trends, 'total_collection');
if (empty($trends_labels)) {
    $trends_labels = ['No Data'];
    $trends_data = [0];
}

$category_labels = array_column($top_categories, 'category_name');
$category_data = array_column($top_categories, 'total');
if (empty($category_labels)) {
    $category_labels = ['No Data'];
    $category_data = [1];
}
?>

<div class="container">
    <h1 style="margin-bottom: 1.5rem; font-size: 1.5rem;">📈 Financial Analysis & Projections</h1>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value">TZS <?php echo number_format($projection); ?></div>
            <div class="stat-label">Projected Next Month Revenue</div>
        </div>
        <div class="stat-card <?php echo ($budget_vs_actual[0]['actual'] ?? 0) >= ($budget_vs_actual[0]['budget'] ?? 0) ? 'success' : 'danger'; ?>">
            <div class="stat-icon">🎯</div>
            <div class="stat-value"><?php echo round(($budget_vs_actual[0]['actual'] ?? 0) / max(($budget_vs_actual[0]['budget'] ?? 1), 1) * 100); ?>%</div>
            <div class="stat-label">Revenue Budget Achievement</div>
        </div>
    </div>
    
    <!-- Monthly Trends Chart - Responsive Container -->
    <div class="chart-container form-card">
        <h3>📉 Revenue Trends (Last 6 Months)</h3>
        <div class="chart-wrapper">
            <canvas id="trendsChart"></canvas>
        </div>
    </div>
    
    <!-- Two Columns for Budget and Category Chart -->
    <div class="two-columns">
        <!-- Budget vs Actual Table -->
        <div class="form-card">
            <h3>💰 Budget vs Actual (Current Month)</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Category</th><th>Actual</th><th>Budget</th><th>Variance</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($budget_vs_actual) || ($budget_vs_actual[0]['actual'] == 0 && $budget_vs_actual[1]['actual'] == 0)): ?>
                            <tr><td colspan="5" style="text-align:center;">No data available for current month</td></tr>
                        <?php else: ?>
                            <?php foreach ($budget_vs_actual as $bva): 
                                $variance = ($bva['actual'] ?? 0) - ($bva['budget'] ?? 0);
                                $status = $variance >= 0 ? ($bva['type'] == 'Revenue' ? '✅ Good' : '⚠️ Over budget') : ($bva['type'] == 'Revenue' ? '⚠️ Below target' : '✅ Under budget');
                                $statusClass = ($bva['type'] == 'Revenue' && $variance >= 0) || ($bva['type'] == 'Expenses' && $variance <= 0) ? 'success' : 'danger';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bva['type']); ?></td>
                                <td>TZS <?php echo number_format($bva['actual'] ?? 0); ?></td>
                                <td>TZS <?php echo number_format($bva['budget'] ?? 0); ?></td>
                                <td class="<?php echo $statusClass; ?>">TZS <?php echo number_format(abs($variance)); ?> (<?php echo $variance >= 0 ? '+' : '-'; ?>)</td>
                                <td class="<?php echo $statusClass; ?>"><?php echo $status; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Top Revenue Categories Chart -->
        <div class="form-card">
            <h3>🥧 Top Revenue Categories</h3>
            <div class="chart-wrapper">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Debt Aging Table -->
    <div class="form-card">
        <h3>⏰ Debt Aging Analysis</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Aging Period</th><th>Total Debt</th><th>Action Required</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $total_debt = 0;
                    if (empty($debt_aging) || $debt_aging[0]['total_debt'] == 0): 
                    ?>
                        <tr><td colspan="3" style="text-align:center;">No outstanding debts</td></tr>
                    <?php else: ?>
                        <?php foreach ($debt_aging as $da): 
                            $total_debt += $da['total_debt'];
                            $action = $da['aging_period'] == '0-30 days' ? 'Send reminder' : ($da['aging_period'] == '31-60 days' ? 'Call parent' : 'Escalate to headmaster');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($da['aging_period']); ?></td>
                            <td>TZS <?php echo number_format($da['total_debt']); ?></td>
                            <td><?php echo $action; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight:bold; background:#e6f7ff;">
                            <td>TOTAL OUTSTANDING DEBT</td>
                            <td>TZS <?php echo number_format($total_debt); ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Advisory Notes -->
    <div class="alert alert-info">
        <strong>💡 Budget Advisory:</strong>
        <?php if (($budget_vs_actual[0]['actual'] ?? 0) < ($budget_vs_actual[0]['budget'] ?? 0)): ?>
            Revenue is <?php echo number_format(($budget_vs_actual[0]['budget'] ?? 0) - ($budget_vs_actual[0]['actual'] ?? 0)); ?> TZS below target. 
            Consider accelerating fee collection or organizing a fundraising event.
        <?php else: ?>
            Revenue is on track! Consider allocating surplus to maintenance or student resources.
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Function to handle responsive charts
function initCharts() {
    // Trends Chart (Line)
    const ctx1 = document.getElementById('trendsChart');
    if (ctx1) {
        // Destroy existing chart if any
        if (window.trendsChartInstance) {
            window.trendsChartInstance.destroy();
        }
        
        window.trendsChartInstance = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trends_labels); ?>,
                datasets: [{
                    label: 'Monthly Revenue (TZS)',
                    data: <?php echo json_encode($trends_data); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: window.innerWidth <= 768 ? 1 : 1.5,
                plugins: {
                    legend: { 
                        position: 'top',
                        labels: { font: { size: window.innerWidth <= 768 ? 10 : 12 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'TZS ' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'TZS ' + value.toLocaleString();
                            },
                            font: { size: window.innerWidth <= 768 ? 9 : 11 }
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: window.innerWidth <= 768 ? 9 : 11 },
                            rotation: window.innerWidth <= 768 ? 45 : 0
                        }
                    }
                }
            }
        });
    }
    
    // Category Chart (Doughnut)
    const ctx2 = document.getElementById('categoryChart');
    if (ctx2) {
        if (window.categoryChartInstance) {
            window.categoryChartInstance.destroy();
        }
        
        window.categoryChartInstance = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: ['#667eea', '#764ba2', '#f39c12', '#27ae60', '#e74c3c', '#3498db', '#1abc9c'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,
                plugins: {
                    legend: { 
                        position: window.innerWidth <= 768 ? 'bottom' : 'right',
                        labels: { 
                            font: { size: window.innerWidth <= 768 ? 10 : 12 },
                            boxWidth: window.innerWidth <= 768 ? 10 : 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: TZS ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// Initialize charts on load
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
});

// Re-initialize charts on window resize (for responsive)
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
        initCharts();
    }, 250);
});
</script>

<style>
/* Additional responsive styles for charts */
.chart-container {
    margin-bottom: 1.5rem;
}

.chart-wrapper {
    position: relative;
    width: 100%;
    min-height: 300px;
}

.chart-wrapper canvas {
    width: 100% !important;
    height: auto !important;
    max-height: 400px;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Mobile specific chart adjustments */
@media (max-width: 768px) {
    .chart-wrapper {
        min-height: 250px;
    }
    
    .chart-wrapper canvas {
        max-height: 300px;
    }
    
    .form-card h3 {
        font-size: 1rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.2rem;
    }
}

@media (max-width: 480px) {
    .chart-wrapper {
        min-height: 200px;
    }
    
    .chart-wrapper canvas {
        max-height: 250px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>