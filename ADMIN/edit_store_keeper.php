<?php
// ADMIN/edit_store_keeper.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$user_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get store keeper details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'store_keeper'");
$stmt->execute([$user_id]);
$store_keeper = $stmt->fetch();

if (!$store_keeper) {
    header('Location: store_keepers_list.php');
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$full_name, $username, $email, $phone, $is_active, $user_id]);
        $success = "✅ Assets Officer updated successfully!";
        
        // Refresh store keeper data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $store_keeper = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

$page_title = "Edit Assets Officer";
include 'includes/admin_header.php';
?>

<div class="container">
    <h1>✏️ Edit Assets Officer (Store Keeper)</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST">
            <div class="two-columns">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($store_keeper['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($store_keeper['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($store_keeper['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($store_keeper['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo $store_keeper['is_active'] ? 'checked' : ''; ?>>
                        Active Account
                    </label>
                </div>
            </div>
            <div class="action-buttons" style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="store_keepers_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <!-- Additional Info Card -->
    <div class="form-card" style="margin-top: 1.5rem;">
        <h3><i class="fas fa-boxes"></i> Assets Officer Information</h3>
        <?php
        // Get asset statistics
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM assets WHERE custodian_id = ?");
        $stmt->execute([$user_id]);
        $asset_count = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT SUM(current_value) as total_value FROM assets WHERE custodian_id = ?");
        $stmt->execute([$user_id]);
        $total_value = $stmt->fetch()['total_value'];
        
        $stmt = $pdo->prepare("
            SELECT asset_status, COUNT(*) as count 
            FROM assets 
            WHERE custodian_id = ? 
            GROUP BY asset_status
        ");
        $stmt->execute([$user_id]);
        $asset_statuses = $stmt->fetchAll();
        ?>
        <div class="two-columns" style="margin-top: 1rem;">
            <div>
                <p><strong>📦 Total Assets Managed:</strong> <?php echo $asset_count; ?></p>
                <p><strong>💰 Total Asset Value:</strong> TZS <?php echo number_format($total_value ?? 0, 0); ?></p>
            </div>
            <div>
                <?php if (!empty($asset_statuses)): ?>
                    <p><strong>Assets by Status:</strong></p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php foreach ($asset_statuses as $status): ?>
                            <span class="status-badge status-<?php echo str_replace(' ', '', $status['asset_status']); ?>">
                                <?php echo $status['asset_status']; ?>: <?php echo $status['count']; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666;">No assets currently assigned to this officer.</p>
                <?php endif; ?>
            </div>
        </div>
        <div style="margin-top: 1rem;">
            <a href="../ASSETS_OFFICER/dashboard.php?user_id=<?php echo $user_id; ?>" class="btn-sm" style="background: #17a2b8; color: white; display: inline-block;" target="_blank">
                <i class="fas fa-boxes"></i> Login as this Assets Officer
            </a>
            <a href="store_keepers_list.php" class="btn-sm" style="background: #6c757d; color: white; display: inline-block;">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<style>
    .status-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    .status-nzima {
        background: #d4edda;
        color: #155724;
    }
    .status-inahitaji-service {
        background: #fff3cd;
        color: #856404;
    }
    .status-mbovu {
        background: #f8d7da;
        color: #721c24;
    }
    .status-imeuzwa {
        background: #e2e3e5;
        color: #383d41;
    }
    .btn-sm {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        font-size: 0.75rem;
        border-radius: 4px;
        text-decoration: none;
        margin-right: 0.5rem;
    }
</style>

<?php include 'includes/admin_footer.php'; ?>