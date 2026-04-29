<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title ?? 'SSMS Tanzania - Accountant'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Mobile Hamburger Button -->
<div class="mobile-header">
    <button class="hamburger" id="hamburgerBtn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-logo">🏫 SSMS Tanzania</div>
    <div class="mobile-user">
        <i class="fas fa-user-circle"></i>
        <span class="user-name-mobile"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Accountant'); ?></span>
    </div>
</div>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🏫 SSMS Tanzania</div>
        <div class="sidebar-sub">Accountant Module</div>
    </div>
    
    <div class="sidebar-user">
        <i class="fas fa-user-circle"></i>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Accountant'); ?></div>
            <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Accountant'); ?></div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="index.php" class="nav-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- STUDENT MANAGEMENT -->
        <a href="students.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Students</span>
        </a>
        
        <div class="nav-divider">FINANCE & PAYMENTS</div>
        
        <a href="fee_management.php" class="nav-item">
            <i class="fas fa-money-bill-wave"></i>
            <span>Fee Collection</span>
        </a>
        <a href="invoices.php" class="nav-item">
            <i class="fas fa-file-invoice"></i>
            <span>Invoices</span>
        </a>
        <a href="expenses.php" class="nav-item">
            <i class="fas fa-receipt"></i>
            <span>Expenses</span>
        </a>
        <a href="debts.php" class="nav-item">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Debts</span>
        </a>
        
        <div class="nav-divider">BANK & RECONCILIATION</div>
        
        <a href="bank_reconciliation.php" class="nav-item">
            <i class="fas fa-exchange-alt"></i>
            <span>Reconciliation</span>
        </a>
        <a href="import_statement.php" class="nav-item">
            <i class="fas fa-upload"></i>
            <span>Import Statement</span>
        </a>
        <a href="smart_cards.php" class="nav-item">
            <i class="fas fa-id-card"></i>
            <span>Smart Cards</span>
        </a>
        
        <div class="nav-divider">INVENTORY</div>
        
        <a href="inventory.php" class="nav-item">
            <i class="fas fa-boxes"></i>
            <span>Inventory</span>
        </a>
        
        <!-- POS SIMULATION - TEST MODE (Inakuja) -->
        <a href="test_card_payment.php" class="nav-item" style="border-left: 3px solid #ffc107;">
            <i class="fas fa-credit-card"></i>
            <span>POS Simulation</span>
            <span style="background: #ffc107; color: #333; font-size: 0.65rem; padding: 0.15rem 0.4rem; border-radius: 10px; margin-left: auto;">Test</span>
        </a>
        
        <div class="nav-divider">REPORTS & ANALYSIS</div>
        
        <a href="reports.php" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="exceptions.php" class="nav-item">
            <i class="fas fa-bug"></i>
            <span>Exceptions</span>
        </a>
        <a href="financial_analysis.php" class="nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Analysis</span>
        </a>
        
        <div class="nav-divider">ACCOUNT</div>
        
        <!-- Profile Settings -->
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="logout.php" class="nav-item logout-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Main Content -->
<main class="main-content" id="mainContent">