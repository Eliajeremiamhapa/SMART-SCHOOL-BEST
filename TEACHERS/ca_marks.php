<?php
// TEACHERS/ca_marks.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

// ✅ FIXED PATH - Now looking in the correct location
require_once __DIR__ . '/includes/grading_helper.php';

$success = '';
$error = '';

// Get teacher's classes
$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

$selected_class = $_GET['class'] ?? '';
$selected_subject = $_GET['subject'] ?? '';
$ca_type = $_GET['ca_type'] ?? 'CA 1';

// CA Types
$ca_types = ['CA 1', 'CA 2', 'CA 3', 'Assignment 1', 'Assignment 2', 'Quiz 1', 'Quiz 2', 'Project'];

// Get students for selected class
$students = [];
if ($selected_class) {
    $stmt = $pdo->prepare("
        SELECT id, student_number, full_name, school_level 
        FROM students 
        WHERE class = ? AND is_active = 1 
        ORDER BY full_name
    ");
    $stmt->execute([$selected_class]);
    $students = $stmt->fetchAll();
}

// Handle CA marks submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_ca_marks'])) {
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $ca_type = $_POST['ca_type'];
    $term = getCurrentTerm($pdo);
    $academic_year = getCurrentAcademicYear($pdo);
    
    $count_saved = 0;
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        $score = $_POST['score_' . $student_id] ?? '';
        
        if ($score !== '') {
            $school_level = $student['school_level'] ?? 'primary';
            $grade = calculateGrade($score, $school_level);
            
            // Check if CA mark already exists
            $stmt_check = $pdo->prepare("
                SELECT id FROM exam_results 
                WHERE student_id = ? AND subject = ? AND exam_type = ? AND term = ? AND academic_year = ?
            ");
            $stmt_check->execute([$student_id, $subject, $ca_type, $term, $academic_year]);
            
            if ($stmt_check->fetch()) {
                // Update existing
                $stmt_update = $pdo->prepare("
                    UPDATE exam_results 
                    SET score = ?, grade = ?, exam_date = CURDATE()
                    WHERE student_id = ? AND subject = ? AND exam_type = ? AND term = ? AND academic_year = ?
                ");
                $stmt_update->execute([$score, $grade, $student_id, $subject, $ca_type, $term, $academic_year]);
            } else {
                // Insert new
                $stmt_insert = $pdo->prepare("
                    INSERT INTO exam_results (student_id, subject, score, grade, exam_type, term, academic_year, exam_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $stmt_insert->execute([$student_id, $subject, $score, $grade, $ca_type, $term, $academic_year]);
            }
            $count_saved++;
        }
    }
    
    if ($count_saved > 0) {
        $success = "✅ CA marks saved for $count_saved student(s)!";
    }
}

$page_title = "Continuous Assessment (CA)";
include 'includes/teacher_header.php';
?>

<div class="container">
    <h1>📝 Continuous Assessment (CA)</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Selection Form -->
    <div class="form-card">
        <form method="GET" id="selectionForm">
            <div class="two-columns">
                <div class="form-group">
                    <label>Select Class</label>
                    <select name="class" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class']; ?>" <?php echo $selected_class == $c['class'] ? 'selected' : ''; ?>>
                                <?php echo $c['class']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g., Mathematics" value="<?php echo htmlspecialchars($selected_subject); ?>" required>
                </div>
                <div class="form-group">
                    <label>CA Type</label>
                    <select name="ca_type" class="form-control">
                        <?php foreach ($ca_types as $ct): ?>
                            <option value="<?php echo $ct; ?>" <?php echo $ca_type == $ct ? 'selected' : ''; ?>><?php echo $ct; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Load Students</button>
        </form>
    </div>
    
    <!-- CA Marks Form -->
    <?php if ($selected_class && $selected_subject && !empty($students)): ?>
    <div class="form-card">
        <h3>📋 Enter CA Marks for <?php echo htmlspecialchars($selected_class); ?> - <?php echo htmlspecialchars($selected_subject); ?></h3>
        <p><strong>CA Type:</strong> <?php echo htmlspecialchars($ca_type); ?></p>
        
        <form method="POST">
            <input type="hidden" name="class" value="<?php echo htmlspecialchars($selected_class); ?>">
            <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
            <input type="hidden" name="ca_type" value="<?php echo htmlspecialchars($ca_type); ?>">
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Score (0-100)</th>
                            <th>Grade (Auto)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; 
                        foreach ($students as $student): 
                            // Get existing CA mark
                            $stmt = $pdo->prepare("
                                SELECT score, grade FROM exam_results 
                                WHERE student_id = ? AND subject = ? AND exam_type = ? 
                                AND term = ? AND academic_year = ?
                            ");
                            $stmt->execute([$student['id'], $selected_subject, $ca_type, getCurrentTerm($pdo), getCurrentAcademicYear($pdo)]);
                            $existing = $stmt->fetch();
                            $current_score = $existing['score'] ?? '';
                            $current_grade = $existing['grade'] ?? '';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                            <td><?php echo $student['student_number']; ?></small></td>
                            <td>
                                <input type="number" name="score_<?php echo $student['id']; ?>" 
                                       class="form-control score-input" 
                                       value="<?php echo $current_score; ?>" 
                                       step="0.01" min="0" max="100" 
                                       style="width: 100px;"
                                       data-student="<?php echo $student['id']; ?>">
                             </small></td>
                            <td>
                                <span class="grade-display" id="grade_<?php echo $student['id']; ?>">
                                    <?php echo $current_grade; ?>
                                </span>
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons" style="margin-top: 1rem;">
                <button type="submit" name="save_ca_marks" class="btn btn-primary">💾 Save CA Marks</button>
                <a href="class_results.php?class=<?php echo urlencode($selected_class); ?>" class="btn btn-info">📊 View Class Results</a>
            </div>
        </form>
    </div>
    <?php elseif ($selected_class && $selected_subject && empty($students)): ?>
        <div class="alert alert-warning">No students found in <?php echo htmlspecialchars($selected_class); ?> class.</div>
    <?php endif; ?>
    
    <!-- Grading Scale Reference -->
    <div class="form-card">
        <h4>📊 Grading Scale Reference</h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Grade</th><th>Score Range</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT school_type FROM school_settings LIMIT 1");
                    $settings = $stmt->fetch();
                    $school_type = $settings['school_type'] ?? 'both';
                    
                    if ($school_type == 'secondary' || $school_type == 'both') {
                        $scale = getGradingScale('secondary');
                    } else {
                        $scale = getGradingScale('primary');
                    }
                    ?>
                    <?php foreach ($scale as $g): ?>
                    <tr>
                        <td><strong><?php echo $g['grade']; ?></strong></small></td>
                        <td><?php echo $g['min']; ?>% - <?php echo $g['max']; ?>%</small></small></td>
                        <td><?php echo $g['description']; ?></small></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-calculate grade when score is entered
document.querySelectorAll('.score-input').forEach(function(input) {
    input.addEventListener('input', function() {
        var score = parseFloat(this.value);
        var studentId = this.getAttribute('data-student');
        var gradeSpan = document.getElementById('grade_' + studentId);
        var schoolLevel = '<?php echo $school_type ?? "primary"; ?>';
        
        if (!isNaN(score) && score >= 0 && score <= 100) {
            var grade = '';
            if (schoolLevel === 'secondary' || schoolLevel === 'both') {
                if (score >= 80) grade = 'A';
                else if (score >= 70) grade = 'B';
                else if (score >= 60) grade = 'C';
                else if (score >= 50) grade = 'D';
                else if (score >= 40) grade = 'E';
                else grade = 'F';
            } else {
                if (score >= 80) grade = 'A';
                else if (score >= 70) grade = 'B';
                else if (score >= 60) grade = 'C';
                else if (score >= 50) grade = 'D';
                else if (score >= 40) grade = 'E';
                else grade = 'F';
            }
            gradeSpan.innerHTML = '<strong>' + grade + '</strong>';
            gradeSpan.style.color = '#28a745';
        } else {
            gradeSpan.innerHTML = '-';
        }
    });
});
</script>

<?php include 'includes/teacher_footer.php'; ?>