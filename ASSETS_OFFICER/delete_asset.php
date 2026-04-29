<?php
// ASSETS_OFFICER/delete_asset.php
$asset_id = $_GET['id'] ?? 0;

if(!$asset_id) {
    header('Location: assets_list.php');
    exit();
}

// Start session and connect
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname ='accountant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Only super_admin or store_keeper can delete
$allowed_roles = ['super_admin', 'store_keeper'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: assets_list.php');
    exit();
}

// Delete maintenance records first (foreign key constraint)
$pdo->prepare("DELETE FROM asset_maintenance WHERE asset_id = ?")->execute([$asset_id]);

// Then delete asset
$stmt = $pdo->prepare("DELETE FROM assets WHERE asset_id = ?");
$stmt->execute([$asset_id]);

header('Location: assets_list.php?deleted=1');
exit();
?>