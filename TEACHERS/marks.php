<?php
// TEACHERS/marks.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

// Include grading helper functions - FIXED PATH
require_once 'includes/grading_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') { 
    header('Location: ../ACCOUNTANT/login_fixed.php'); 
    exit(); 
}

$success = '';
$error = '';
$student_info = null;

// Handle marks submission with grading
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $score = $_POST['score'];
    $exam_type = $_POST['exam_type'];
    
    // Get student's school level
    $stmt = $pdo->prepare("SELECT school_level, full_name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if ($student) {
        $school_level = $student['school_level'] ?? 'primary';
        
        // Calculate grade using helper function
        $grade = calculateGrade($score, $school_level);
        
        // Calculate points for secondary
        $points = ($school_level == 'secondary') ? calculatePoints($grade) : null;
        
        // Get current term and academic year
        $term = getCurrentTerm($pdo);
        $academic_year = getCurrentAcademicYear($pdo);
        
        try {
            // Check if result already exists
            $stmt = $pdo->prepare("
                SELECT id FROM exam_results 
                WHERE student_id = ? AND subject = ? AND exam_type = ? AND term = ? AND academic_year = ?
            ");
            $stmt->execute([$student_id, $subject, $exam_type, $term, $academic_year]);
            
            if ($stmt->fetch()) {
                // Update existing result
                $stmt = $pdo->prepare("
                    UPDATE exam_results 
                    SET score = ?, grade = ?, points = ?, exam_date = CURDATE()
                    WHERE student_id = ? AND subject = ? AND exam_type = ? AND term = ? AND academic_year = ?
                ");
                $stmt->execute([$score, $grade, $points, $student_id, $subject, $exam_type, $term, $academic_year]);
                $success = "✅ Marks updated successfully! Grade: $grade" . ($points ? " (Points: $points)" : "");
            } else {
                // Insert new result
                $stmt = $pdo->prepare("
                    INSERT INTO exam_results (student_id, subject, score, grade, points, exam_type, term, academic_year, exam_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $stmt->execute([$student_id, $subject, $score, $grade, $points, $exam_type, $term, $academic_year]);
                $success = "✅ Marks saved successfully! Grade: $grade" . ($points ? " (Points: $points)" : "");
            }
        } catch(Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    } else {
        $error = "❌ Student not found!";
    }
}

$page_title = "Enter Exam Marks";
include 'includes/teacher_header.php';

// Get teacher's classes
$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

// Get grading scales for display
$primary_scale = getGradingScale('primary');
$secondary_scale = getGradingScale('secondary');
?>

<div class="container">
    <h1>✏️ Enter Exam Marks</h1>
    
    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <!-- Grading Scale Reference -->
    <div class="two-columns" style="margin-bottom: 1.5rem;">
        <div class="form-card">
            <h4>🏫 Primary School Grading</h4>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Grade</th><th>Score Range</th><th>Description</th></tr></thead>
                    <tbody>
                        <?php foreach ($primary_scale as $g): ?>
                        <tr>
                            <td><strong><?php echo $g['grade']; ?></strong></td>
                            <td><?php echo $g['min']; ?>% - <?php echo $g['max']; ?>%</small></td>
                            <td><?php echo $g['description']; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="form-card">
            <h4>🏛️ Secondary School Grading</h4>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Grade</th><th>Score Range</th><th>Points</th><th>Description</th></tr></thead>
                    <tbody>
                        <?php foreach ($secondary_scale as $g): ?>
                        <tr>
                            <td><strong><?php echo $g['grade']; ?></strong></td>
                            <td><?php echo $g['min']; ?>% - <?php echo $g['max']; ?>%</small></td>
                            <td><?php echo $g['points']; ?></small></td>
                            <td><?php echo $g['description']; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="form-card">
        <form method="POST" id="marksForm">
            <div class="form-group">
                <label>Select Class</label>
                <select name="class" id="class_select" required>
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['class']); ?>"><?php echo htmlspecialchars($c['class']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" id="student_select" required>
                    <option value="">-- First Select Class --</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="e.g., Mathematics, English, Science">
            </div>
            
            <div class="form-group">
                <label>Exam Type</label>
                <select name="exam_type">
                    <option value="Term Exam">Term Exam</option>
                    <option value="Mid Term">Mid Term</option>
                    <option value="Quiz">Quiz</option>
                    <option value="Assignment">Assignment</option>
                    <option value="CAT 1">CAT 1</option>
                    <option value="CAT 2">CAT 2</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Score (%)</label>
                <input type="number" name="score" id="score" step="0.01" min="0" max="100" required>
                <small id="grade_preview" style="display: block; margin-top: 5px;"></small>
            </div>
            
            <button type="submit" name="save_marks" class="btn btn-primary">💾 Save Marks</button>
        </form>
    </div>
</div>

<script>
// Preview grade while typing
document.getElementById('score').addEventListener('input', function() {
    var score = this.value;
    var studentSelect = document.getElementById('student_select');
    var selectedOption = studentSelect.options[studentSelect.selectedIndex];
    var schoolLevel = selectedOption ? (selectedOption.getAttribute('data-school-level') || 'primary') : 'primary';
    
    if (score && score >= 0 && score <= 100) {
        var grade = '';
        if (schoolLevel === 'primary') {
            if (score >= 80) grade = 'A';
            else if (score >= 70) grade = 'B';
            else if (score >= 60) grade = 'C';
            else if (score >= 50) grade = 'D';
            else if (score >= 40) grade = 'E';
            else grade = 'F';
        } else {
            if (score >= 80) grade = 'A (1 point)';
            else if (score >= 70) grade = 'B (2 points)';
            else if (score >= 60) grade = 'C (3 points)';
            else if (score >= 50) grade = 'D (4 points)';
            else if (score >= 40) grade = 'E (5 points)';
            else grade = 'F (6 points)';
        }
        document.getElementById('grade_preview').innerHTML = '<span class="info-badge">📊 Predicted Grade: <strong>' + grade + '</strong></span>';
    } else {
        document.getElementById('grade_preview').innerHTML = '';
    }
});

// Get students when class is selected (include school level)
document.getElementById('class_select').addEventListener('change', function() {
    var class_name = this.value;
    if(class_name) {
        fetch('get_students.php?class=' + encodeURIComponent(class_name))
            .then(r => r.json()).then(data => {
                var select = document.getElementById('student_select');
                select.innerHTML = '<option value="">-- Select Student --</option>';
                if(data.length > 0) {
                    data.forEach(s => {
                        var levelText = (s.school_level === 'secondary') ? '🏛️ Secondary' : '🏫 Primary';
                        select.innerHTML += `<option value="${s.id}" data-school-level="${s.school_level || 'primary'}">${s.full_name} (${s.student_number}) - ${levelText}</option>`;
                    });
                } else {
                    select.innerHTML = '<option value="">-- No students found in this class --</option>';
                }
            });
    }
});
</script>

<style>
.info-badge {
    background: #e8f4fd;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    display: inline-block;
}
</style>

<?php include 'includes/teacher_footer.php'; ?>