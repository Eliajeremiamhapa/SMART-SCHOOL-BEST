<?php
// STUDENTS/view_as.php
session_start();
require_once '../config/database.php';

$student_id = $_GET['id'] ?? 0;

if (!$student_id) {
    header('Location: ../ADMIN/students_list.php');
    exit();
}

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: ../ADMIN/students_list.php');
    exit();
}

// Store original user session (for returning back)
$_SESSION['view_as_student'] = $_SESSION['user_id'];
$_SESSION['view_as_mode'] = true;

// Set session as student
$_SESSION['user_id'] = $student['id'];
$_SESSION['username'] = $student['student_number'];
$_SESSION['role'] = 'student';
$_SESSION['full_name'] = $student['full_name'];

// Redirect to student dashboard
header('Location: index.php');
exit();
?>