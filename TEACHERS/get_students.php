<?php
// TEACHERS/get_students.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die(json_encode([])); }

$class = $_GET['class'] ?? '';
if ($class) {
    $stmt = $pdo->prepare("SELECT id, full_name, student_number FROM students WHERE class = ? AND is_active = 1 ORDER BY full_name");
    $stmt->execute([$class]);
    echo json_encode($stmt->fetchAll());
} else {
    echo json_encode([]);
}
?>