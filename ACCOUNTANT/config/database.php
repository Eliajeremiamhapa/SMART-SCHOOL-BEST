<?php
// config/database.php
 $host = 'localhost';
 $dbname = 'accountant';   // Jina la database yako
$username = 'root';
$password = '';     

// $host = 'sql100.infinityfree.com';
// $dbname = 'if0_41599581_accountant';   // Jina la database yako
// $username = 'if0_41599581';
// $password = 'kiNhlouxOQGKt'; 

// Acha tupu kama huna password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to check role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Redirect if not logged in (except for login page)
$current_file = basename($_SERVER['PHP_SELF']);
$allowed_files = ['login.php', 'process_login.php', 'test_db.php'];
if (!isLoggedIn() && !in_array($current_file, $allowed_files)) {
    header('Location: login.php');
    exit();
}
?>