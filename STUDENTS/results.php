<?php
// STUDENTS/results.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

// Get student details - NO JOIN (user_id doesn't exist in students table)
$student = null;

// Try by student_number (username)
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->execute([$_SESSION['username']]);
$student = $stmt->fetch();

// Try by full_name
if (!$student) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE full_name = ?");
    $stmt->execute([$_SESSION['full_name']]);
    $student = $stmt->fetch();
}

// If still not found, create basic record
if (!$student) {
    $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$_SESSION['username'], $_SESSION['full_name'], 'Not Assigned', '']);
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_number = ?");
    $stmt->execute([$_SESSION['username']]);
    $student = $stmt->fetch();
}

$page_title = "My Results";
include 'includes/student_header.php';

// Check if exam_results table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'exam_results'");
    $exam_table_exists = $stmt->rowCount() > 0;
} catch(PDOException $e) {
    $exam_table_exists = false;
}

// Get results if table exists
$results = [];
if ($exam_table_exists) {
    $stmt = $pdo->prepare("SELECT * FROM exam_results WHERE student_id = ? ORDER BY exam_date DESC");
    $stmt->execute([$student['id']]);
    $results = $stmt->fetchAll();
}

// Calculate overall statistics
$total_score = 0;
$subject_count = count($results);
foreach ($results as $r) {
    $total_score += $r['score'];
}
$overall_average = $subject_count > 0 ? round($total_score / $subject_count, 1) : 0;

// Get grading system
$grades = $pdo->query("SELECT * FROM grading_system ORDER BY min_score DESC")->fetchAll();

function getGrade($score, $grades) {
    foreach ($grades as $g) {
        if ($score >= $g['min_score'] && $score <= $g['max_score']) {
            return ['grade' => $g['grade'], 'points' => $g['points'], 'description' => $g['description']];
        }
    }
    return ['grade' => 'F', 'points' => 0, 'description' => 'Fail'];
}
?>

<div class="container">
    <h1>📊 My Academic Results</h1>
    
    <?php if (!$exam_table_exists): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Exam results module is coming soon. Please check back later.
        </div>
    <?php endif; ?>
    
    <!-- Overall Performance -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $overall_average; ?>%</div>
            <div class="stat-label">Overall Average</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $subject_count; ?></div>
            <div class="stat-label">Total Subjects</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo getGrade($overall_average, $grades)['grade']; ?></div>
            <div class="stat-label">Overall Grade</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo getGrade($overall_average, $grades)['points']; ?></div>
            <div class="stat-label">GPA</div>
        </div>
    </div>
    
    <!-- Results Table -->
    <div class="form-card">
        <h3>📋 Subject Results</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Exam Type</th>
                        <th>Score (%)</th>
                        <th>Grade</th>
                        <th>Points</th>
                        <th>Remarks</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr style="background-color: #f8f9fa;">
                            <td colspan="7" style="text-align:center; color: #666;">
                                <?php if (!$exam_table_exists): ?>
                                    📌 Exam results module is coming soon
                                <?php else: ?>
                                    📌 No results available yet
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $total_points = 0;
                        foreach ($results as $result):
                            $grade_info = getGrade($result['score'], $grades);
                            $total_points += $grade_info['points'];
                            $grade_class = $grade_info['grade'] == 'A' ? 'grade-A' : ($grade_info['grade'] == 'B' ? 'grade-B' : ($grade_info['grade'] == 'C' ? 'grade-C' : ($grade_info['grade'] == 'D' ? 'grade-D' : 'grade-F')));
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($result['subject']); ?></strong></td>
                            <td><?php echo $result['exam_type'] ?? 'Term Exam'; ?></td>
                            <td><?php echo $result['score']; ?>%</small></td>
                            <td class="<?php echo $grade_class; ?>"><strong><?php echo $grade_info['grade']; ?></strong></td>
                            <td><?php echo $grade_info['points']; ?></td>
                            <td><?php echo $grade_info['description']; ?></small></td>
                            <td><?php echo date('d-m-Y', strtotime($result['exam_date'])); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f0f2f5; font-weight: bold;">
                            <td> colspan="4" style="text-align:right;">TOTAL / AVERAGE:</small></td>
                            <td colspan="3">GPA: <?php echo $subject_count > 0 ? round($total_points / $subject_count, 2) : 0; ?></small></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if (!empty($results)): ?>
    <!-- Performance Chart -->
    <div class="form-card">
        <h3>📈 Performance by Subject</h3>
        <canvas id="performanceChart" style="max-height: 300px;"></canvas>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($results)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('performanceChart').getContext('2d');
const subjects = <?php echo json_encode(array_column($results, 'subject')); ?>;
const scores = <?php echo json_encode(array_column($results, 'score')); ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: subjects,
        datasets: [{
            label: 'Score (%)',
            data: scores,
            backgroundColor: '#1e3c72',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, max: 100 }
        }
    }
});
</script>
<?php endif; ?>

<?php include 'includes/student_footer.php'; ?>