<?php
// STUDENTS/exit_view.php
session_start();

if (isset($_SESSION['view_as_mode']) && isset($_SESSION['view_as_student'])) {
    // Restore original admin session
    $_SESSION['user_id'] = $_SESSION['view_as_student'];
    $_SESSION['role'] = 'super_admin';
    $_SESSION['username'] = 'superadmin';
    $_SESSION['full_name'] = 'Super Administrator';
    
    unset($_SESSION['view_as_student']);
    unset($_SESSION['view_as_mode']);
    
    header('Location: ../ADMIN/index.php');
    exit();
} else {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}
?>