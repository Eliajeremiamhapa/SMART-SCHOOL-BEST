<?php
// ADMIN/backup.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Backup Management";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Create backups directory if not exists
$backup_dir = '../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Function to create backup using PHP
function createBackup($pdo, $backup_dir) {
    $filename = 'ssms_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        return ['success' => false, 'error' => 'No tables found in database'];
    }
    
    $sql_content = "-- SSMS Tanzania Database Backup\n";
    $sql_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n\n";
    $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $create = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // FIX: Check for both possible keys 'Create Table' or 'Create Table'
        $create_table_sql = '';
        if (isset($create['Create Table'])) {
            $create_table_sql = $create['Create Table'];
        } elseif (isset($create['Create Table'])) {
            $create_table_sql = $create['Create Table'];
        } else {
            // If neither key exists, get the first value from the array
            $create_table_sql = reset($create);
        }
        
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql_content .= $create_table_sql . ";\n\n";
        
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, array_values($row));
                $sql_content .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $values) . ");\n";
            }
            $sql_content .= "\n";
        }
    }
    
    $sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    if (file_put_contents($filepath, $sql_content)) {
        $filesize = round(filesize($filepath) / 1024, 2);
        return ['success' => true, 'filename' => $filename, 'size' => $filesize . ' KB'];
    }
    
    return ['success' => false, 'error' => 'Failed to write backup file'];
}

// Handle Backup
if (isset($_GET['backup'])) {
    $result = createBackup($pdo, $backup_dir);
    
    if ($result['success']) {
        $stmt = $pdo->prepare("INSERT INTO backup_records (filename, file_size, backup_type, status, created_by) VALUES (?, ?, 'manual', 'success', ?)");
        $stmt->execute([$result['filename'], $result['size'], $_SESSION['user_id']]);
        $success = "✅ Backup created successfully!<br>File: <strong>{$result['filename']}</strong><br>Size: {$result['size']}";
    } else {
        $error = "❌ Backup failed: " . ($result['error'] ?? 'Unknown error');
    }
}

// Handle Delete Backup
if (isset($_GET['delete_backup'])) {
    $id = $_GET['delete_backup'];
    $stmt = $pdo->prepare("SELECT filename FROM backup_records WHERE id = ?");
    $stmt->execute([$id]);
    $backup = $stmt->fetch();
    
    if ($backup) {
        $filepath = $backup_dir . $backup['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $stmt = $pdo->prepare("DELETE FROM backup_records WHERE id = ?");
        $stmt->execute([$id]);
        $success = "✅ Backup deleted successfully!";
    } else {
        $error = "❌ Backup record not found!";
    }
}

// Get backups
$backups = $pdo->query("SELECT b.*, u.full_name FROM backup_records b LEFT JOIN users u ON b.created_by = u.id ORDER BY b.created_at DESC")->fetchAll();
?>

<div class="container">
    <h1>💾 Backup Management</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <div class="form-card">
            <h3>📦 Create New Backup</h3>
            <p>Create a full database backup of your system.</p>
            <a href="?backup=1" class="btn btn-primary" onclick="return confirm('Create a new database backup?')">
                <i class="fas fa-database"></i> 💾 Create Backup Now
            </a>
        </div>
        
        <div class="form-card">
            <h3>📋 Backup History</h3>
            <?php if (empty($backups)): ?>
                <p>No backups created yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Date</th><th>Filename</th><th>Size</th><th>Created By</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $b): ?>
                            <tr>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($b['created_at'])); ?></td>
                                <td><small><?php echo htmlspecialchars($b['filename']); ?></small></td>
                                <td><?php echo htmlspecialchars($b['file_size']); ?></td>
                                <td><?php echo htmlspecialchars($b['full_name'] ?? 'System'); ?></td>
                                <td>
                                    <a href="download_backup.php?id=<?php echo $b['id']; ?>" class="btn-sm" style="background:#28a745;">📥 Download</a>
                                    <a href="?delete_backup=<?php echo $b['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this backup?')">🗑️ Delete</a>
                                </small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>