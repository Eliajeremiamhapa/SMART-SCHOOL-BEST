<?php
// ADMIN/download_backup.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT filename FROM backup_records WHERE id = ?");
$stmt->execute([$id]);
$backup = $stmt->fetch();

if ($backup) {
    $filepath = '../backups/' . $backup['filename'];
    if (file_exists($filepath)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filepath);
        exit();
    } else {
        echo "Backup file not found!";
    }
} else {
    echo "Backup record not found!";
}
exit();
?>