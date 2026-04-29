<?php
session_start();
require_once 'config/database.php';

echo "<h1>Step 3: Session Test</h1>";

// Clear old session
session_destroy();
session_start();

echo "<p>Session started successfully!</p>";

// Test login simulation
$username = 'elia';
$password = 'password123';

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && $password == 'password123') {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    
    echo "<p style='color:green'>✅ Session created successfully!</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<p><a href='index.php'>Try to go to Accountant Dashboard →</a></p>";
} else {
    echo "<p style='color:red'>❌ Failed to create session</p>";
}
?>