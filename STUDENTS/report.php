<?php
// STUDENT/report.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

// Get student details
$stmt = $pdo->prepare("SELECT s.* FROM students s JOIN users u ON s.user_id = u.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
    $stmt->execute([$_SESSION['username']]);
    $student = $stmt->fetch();
}

// Get school settings
$settings = $pdo->query("SELECT * FROM school_settings LIMIT 1")->fetch();

// Get results for report
$stmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ? ORDER BY exam_date DESC LIMIT 20");
$stmt->execute([$student['id']]);
$results = $stmt->fetchAll();

// Get grading system
$grades = $pdo->query("SELECT * FROM grading_system ORDER BY min_score DESC")->fetchAll();

function getGrade($score, $grades) {
    foreach ($grades as $g) {
        if ($score >= $g['min_score'] && $score <= $g['max_score']) {
            return $g['grade'];