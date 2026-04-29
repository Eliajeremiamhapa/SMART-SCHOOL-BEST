// Auto-refresh dashboard stats every 30 seconds
function refreshDashboardStats() {
    fetch('api/dashboard_stats.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.stat-value').forEach((el, index) => {
                    // Update based on index or use data attributes
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// Check low stock alerts
function checkLowStockAlerts() {
    fetch('api/dashboard_stats.php?action=get_low_stock')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                showNotification(`${data.data.length} item(s) are low in stock!`, 'warning');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'warning' ? '#f39c12' : '#27ae60'};
        color: white;
        border-radius: 5px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

// Validate form before submit
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value) {
            input.style.borderColor = 'red';
            isValid = false;
        } else {
            input.style.borderColor = '#ddd';
        }
    });
    
    if (!isValid) {
        showNotification('Please fill all required fields', 'warning');
    }
    return isValid;
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-TZ', {
        style: 'currency',
        currency: 'TZS',
        minimumFractionDigits: 0
    }).format(amount);
}

// Export table to CSV
function exportToCSV(tableId, filename = 'report.csv') {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => cell.innerText);
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

// Search/filter table
function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
    });
}

// Auto-match reconciliation
function runAutoMatch() {
    if (confirm('Run auto-reconciliation? This will match bank transactions with system records.')) {
        window.location.href = 'bank_reconciliation.php?auto_match=1';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .success { color: #27ae60; }
        .danger { color: #e74c3c; }
        .warning { color: #f39c12; }
        .info { color: #3498db; }
    `;
    document.head.appendChild(style);
    
    // Auto-refresh if on dashboard
    if (window.location.pathname.includes('index.php')) {
        setInterval(refreshDashboardStats, 30000);
        setInterval(checkLowStockAlerts, 60000);
    }
});