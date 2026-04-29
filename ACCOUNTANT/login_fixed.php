<?php
session_start();

// Direct database connection
$host = 'localhost';
$dbname = 'accountant';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);
    
    // Get user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$user]);
    $row = $stmt->fetch();
    
    if ($row) {
        // Password is 'password123' for all users
        if ($pass == 'password123') {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            
            // Redirect based on role
            if ($row['role'] == 'accountant') {
                header('Location: index.php');
                exit();
            } elseif ($row['role'] == 'super_admin') {
                header('Location: ../ADMIN/index.php');
                exit();
            } elseif ($row['role'] == 'student') {
                header('Location: ../STUDENTS/index.php');
                exit();
            } elseif ($row['role'] == 'teacher') {
                header('Location: ../TEACHERS/index.php');
                exit();
            } elseif ($row['role'] == 'parent') {
                header('Location: ../PARENTS/index.php');
                exit();
            } elseif ($row['role'] == 'store_keeper') {
                // NEW: Redirect Assets Officer to Asset Management Dashboard
                header('Location: ../ASSETS_OFFICER/dashboard.php');
                exit();
            } else {
                header('Location: index.php');
                exit();
            }
        } else {
            $error = "Invalid password! Use: password123";
        }
    } else {
        $error = "Username not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SSMS Tanzania - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .logo { text-align: center; font-size: 2rem; font-weight: 700; color: #1e3a8a; margin-bottom: 1rem; }
        .logo span { background: #1e3a8a; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem; }
        h2 { text-align: center; color: #333; margin-bottom: 0.5rem; }
        .error { background: #fee2e2; color: #dc2626; padding: 0.75rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; font-size: 0.85rem; }
        input { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem; }
        input:focus { outline: none; border-color: #1e3a8a; }
        button { width: 100%; padding: 0.75rem; background: #1e3a8a; color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 1rem; }
        button:hover { background: #0f172a; }
        .footer { text-align: center; margin-top: 1.5rem; font-size: 0.7rem; color: #999; }
        .credentials { background: #f0f2f5; padding: 0.75rem; border-radius: 10px; margin-top: 1rem; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🏫 SSMS <span>Tanzania</span></div>
        <h2>Welcome Back!</h2>
        <p style="text-align:center; color:#666; margin-bottom:1.5rem;">Login to your dashboard</p>
        
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login to Dashboard</button>
        </form>
        
        <div class="credentials">
            <strong>📋 Login Credentials:</strong><br>
            Accountant: elia / password123<br>
            Super Admin: superadmin / password123<br>
            Student: eliza / password123<br>
            Teacher: teacher.juma / password123<br>
            Parent: parent.juma / password123<br>
            <strong>🏢 Assets Officer: storekeeper / password123 (Add this user first)</strong>
        </div>
        
        <div class="footer">&copy; 2026 SSMS Tanzania - Smart School Management System</div>
    </div>
</body>
</html>