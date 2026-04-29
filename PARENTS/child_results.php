<?php
// PARENTS/child_results.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$student_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT s.* FROM parent_students ps JOIN students s ON ps.student_id = s.id WHERE ps.parent_id = ? AND s.id = ?");
$stmt->execute([$_SESSION['user_id'], $student_id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ? ORDER BY exam_date DESC");
$stmt->execute([$student_id]);
$results = $stmt->fetchAll();

$total_score = 0;
foreach ($results as $r) { $total_score += $r['score']; }
$average = count($results) > 0 ? round($total_score / count($results), 1) : 0;

function getGrade($score) {
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B';
    if ($score >= 60) return 'C';
    if ($score >= 50) return 'D';
    if ($score >= 40) return 'E';
    return 'F';
}

$page_title = "Results - " . $student['full_name'];
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>📊 Results: <?php echo htmlspecialchars($student['full_name']); ?></h1>
    <p>Class: <?php echo htmlspecialchars($student['class']); ?> | Student ID: <?php echo htmlspecialchars($student['student_number']); ?></p>
    
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value"><?php echo $average; ?>%</div><div class="stat-label">Average Score</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo count($results); ?></div><div class="stat-label">Subjects</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo getGrade($average); ?></div><div class="stat-label">Overall Grade</div></div>
    </div>
    
    <div class="form-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Subject</th><th>Exam Type</th><th>Score</th><th>Grade</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="5" style="text-align:center;">No results available yet</small></td>
                    <?php else: ?>
                        <?php foreach ($results as $r): $grade = getGrade($r['score']); $grade_class = 'grade-' . $grade; ?>
                        <tr><td><strong><?php echo $r['subject']; ?></strong></small><td><?php echo $r['exam_type']; ?></small><td><?php echo $r['score']; ?>%</small><td class="<?php echo $grade_class; ?>"><?php echo $grade; ?></small><td><?php echo date('d-m-Y', strtotime($r['exam_date'])); ?></small></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/parent_footer.php'; ?>