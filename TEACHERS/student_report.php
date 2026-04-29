<?php
// TEACHERS/student_report.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

require_once __DIR__ . '/includes/report_helper.php';
require_once __DIR__ . '/includes/grading_helper.php';

$student_id = $_GET['id'] ?? 0;
$print = isset($_GET['print']) ? true : false;

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found!");
}

$school_level = $student['school_level'] ?? 'primary';
$term = $_GET['term'] ?? getCurrentTerm($pdo);
$academic_year = $_GET['year'] ?? getCurrentAcademicYear($pdo);

// Get school settings
$stmt = $pdo->query("SELECT * FROM school_settings LIMIT 1");
$school = $stmt->fetch();

// Get exam results
$stmt = $pdo->prepare("
    SELECT * FROM exam_results 
    WHERE student_id = ? AND term = ? AND academic_year = ?
    ORDER BY subject
");
$stmt->execute([$student_id, $term, $academic_year]);
$results = $stmt->fetchAll();

// Calculate statistics
if ($school_level == 'primary') {
    $average = calculateStudentAverage($student_id, $pdo);
    $overall_grade = calculateGrade($average, 'primary');
    $rank = calculateRank($student_id, $student['class'], 'primary', $pdo);
    $total_students = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class = ?");
    $total_students->execute([$student['class']]);
    $total_students = $total_students->fetchColumn();
} else {
    $total_points = calculateStudentPoints($student_id, $pdo);
    $division = calculateDivision($total_points);
    $rank = calculateRank($student_id, $student['class'], 'secondary', $pdo);
    $total_students = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class = ?");
    $total_students->execute([$student['class']]);
    $total_students = $total_students->fetchColumn();
    $best_7 = getBest7Subjects($student_id, $pdo);
}

// Calculate total score
$total_score = 0;
foreach ($results as $result) {
    $total_score += $result['score'];
}

$page_title = "Report Card - " . $student['full_name'];

// If printing, use print layout without sidebar
if ($print) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Report Card - <?php echo $student['full_name']; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Times New Roman', Times, serif; background: white; padding: 20px; }
            .report-card { max-width: 1000px; margin: 0 auto; background: white; }
            .header { text-align: center; padding: 20px; border-bottom: 2px solid #1a3a5c; }
            .school-name { font-size: 24px; font-weight: bold; color: #1a3a5c; }
            .report-title h2 { font-size: 20px; border: 2px solid #1a3a5c; display: inline-block; padding: 5px 20px; }
            .student-info { padding: 15px; background: #f8f9fa; margin: 15px 0; }
            .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .results-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .results-table th, .results-table td { border: 1px solid #ddd; padding: 8px; text-align: center; }
            .results-table th { background: #1a3a5c; color: white; }
            .summary-section { background: #e8f4f8; padding: 15px; margin: 15px 0; border-radius: 8px; }
            .summary-grid { display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap; }
            .summary-item { flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px; }
            .footer { text-align: center; padding: 15px; font-size: 11px; border-top: 1px solid #ddd; margin-top: 20px; }
            .grade-A { color: #28a745; font-weight: bold; }
            .grade-B { color: #17a2b8; font-weight: bold; }
            .grade-C { color: #ffc107; font-weight: bold; }
            .grade-D, .grade-E, .grade-F { color: #dc3545; font-weight: bold; }
            @media print { body { padding: 0; } }
        </style>
    </head>
    <body>
    <div class="report-card">
        <div class="header">
            <div class="school-name"><?php echo htmlspecialchars($school['school_name'] ?? 'SSMS Tanzania'); ?></div>
            <div class="report-title"><h2>STUDENT REPORT CARD</h2></div>
            <div class="academic-info"><?php echo $term; ?> | <?php echo $academic_year; ?></div>
        </div>
        
        <div class="student-info">
            <div class="info-grid">
                <div><strong>Student Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                <div><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_number']); ?></div>
                <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></div>
                <div><strong>School Level:</strong> <?php echo ucfirst($school_level); ?></div>
                <div><strong>PREM Number:</strong> <?php echo htmlspecialchars($student['prem_number'] ?? 'Not assigned'); ?></div>
                <div><strong>Report Date:</strong> <?php echo date('d-m-Y'); ?></div>
            </div>
        </div>
        
        <table class="results-table">
            <thead><tr><th>#</th><th>Subject</th><th>Score (%)</th><th>Grade</th><th>Remarks</th></tr></thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="5" style="text-align:center;">No results available</small></td>
                <?php else: ?>
                    <?php $counter = 1; foreach ($results as $result): 
                        $grade = calculateGrade($result['score'], $school_level);
                        $grade_class = 'grade-' . $grade;
                        $remarks = ($result['score'] >= 80) ? 'Excellent' : (($result['score'] >= 70) ? 'Very Good' : (($result['score'] >= 60) ? 'Good' : (($result['score'] >= 50) ? 'Satisfactory' : (($result['score'] >= 40) ? 'Pass' : 'Fail'))));
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($result['subject']); ?></small></td>
                        <td><?php echo number_format($result['score'], 1); ?>%</small></td>
                        <td class="<?php echo $grade_class; ?>"><?php echo $grade; ?></small></td>
                        <td><?php echo $remarks; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="summary-section">
            <div class="summary-grid">
                <?php if ($school_level == 'primary'): ?>
                    <div class="summary-item"><strong>Total Score:</strong><br><?php echo number_format($total_score, 1); ?>%</div>
                    <div class="summary-item"><strong>Average:</strong><br><?php echo number_format($average, 2); ?>%</div>
                    <div class="summary-item"><strong>Overall Grade:</strong><br><?php echo $overall_grade; ?></div>
                    <div class="summary-item"><strong>Class Rank:</strong><br><?php echo $rank; ?> / <?php echo $total_students; ?></div>
                <?php else: ?>
                    <div class="summary-item"><strong>Total Points:</strong><br><?php echo $total_points; ?></div>
                    <div class="summary-item"><strong>Division:</strong><br><?php echo $division; ?></div>
                    <div class="summary-item"><strong>Class Rank:</strong><br><?php echo $rank; ?> / <?php echo $total_students; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer">
            <p>This is a computer-generated report card. It is valid without signature.</p>
            <p>&copy; <?php echo date('Y'); ?> SSMS Tanzania - Smart School Management System</p>
        </div>
    </div>
    <script>window.print();</script>
    </body>
    </html>
    <?php
    exit();
}

// Normal view with sidebar (inside system layout)
include 'includes/teacher_header.php';
?>

<div class="container">
    <h1>📄 Student Report Card</h1>
    
    <div class="form-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <strong>Student:</strong> <?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['student_number']); ?>)<br>
                <strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?> | 
                <strong>Term:</strong> <?php echo $term; ?> | 
                <strong>Year:</strong> <?php echo $academic_year; ?>
            </div>
            <div>
                <a href="?id=<?php echo $student_id; ?>&print=1" class="btn btn-primary" target="_blank">🖨️ Print Report Card</a>
                <a href="class_results.php?class=<?php echo urlencode($student['class']); ?>" class="btn btn-secondary">← Back</a>
            </div>
        </div>
    </div>
    
    <!-- Report Card Preview -->
    <div class="form-card" style="background: white; padding: 20px;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <!-- Header -->
            <div style="text-align: center; padding: 15px; border-bottom: 2px solid #1a3a5c;">
                <div style="font-size: 22px; font-weight: bold; color: #1a3a5c;"><?php echo htmlspecialchars($school['school_name'] ?? 'SSMS Tanzania'); ?></div>
                <!-- ✅ FIXED: Now using database value, not hardcoded -->
                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($school['school_address'] ?: 'Anwani haijasajiliwa'); ?></div>
                <div style="margin-top: 10px;">
                    <h3 style="border: 2px solid #1a3a5c; display: inline-block; padding: 5px 20px;">STUDENT REPORT CARD</h3>
                </div>
                <div><strong><?php echo $term; ?> | <?php echo $academic_year; ?></strong></div>
            </div>
            
            <!-- Student Info -->
            <div style="padding: 15px; background: #f8f9fa; margin: 15px 0; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <div><strong>Student Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                    <div><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_number']); ?></div>
                    <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class']); ?></div>
                    <div><strong>School Level:</strong> <?php echo ucfirst($school_level); ?></div>
                    <div><strong>PREM Number:</strong> <?php echo htmlspecialchars($student['prem_number'] ?? 'Not assigned'); ?></div>
                    <div><strong>Report Date:</strong> <?php echo date('d-m-Y'); ?></div>
                </div>
            </div>
            
            <!-- Results Table -->
            <table class="data-table" style="width: 100%; margin: 15px 0;">
                <thead>
                    <tr style="background: #1a3a5c; color: white;">
                        <th style="padding: 10px;">#</th>
                        <th style="padding: 10px; text-align: left;">Subject</th>
                        <th style="padding: 10px;">Score (%)</th>
                        <th style="padding: 10px;">Grade</th>
                        <th style="padding: 10px;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">No results available</small></td>
                    <?php else: ?>
                        <?php $counter = 1; foreach ($results as $result): 
                            $grade = calculateGrade($result['score'], $school_level);
                            $grade_class = '';
                            if ($grade == 'A') $grade_class = 'grade-A';
                            elseif ($grade == 'B') $grade_class = 'grade-B';
                            elseif ($grade == 'C') $grade_class = 'grade-C';
                            else $grade_class = 'grade-D';
                            $remarks = ($result['score'] >= 80) ? 'Excellent' : (($result['score'] >= 70) ? 'Very Good' : (($result['score'] >= 60) ? 'Good' : (($result['score'] >= 50) ? 'Satisfactory' : (($result['score'] >= 40) ? 'Pass' : 'Fail'))));
                        ?>
                        <tr>
                            <td style="padding: 8px; text-align: center;"><?php echo $counter++; ?></small></td>
                            <td style="padding: 8px;"><strong><?php echo htmlspecialchars($result['subject']); ?></strong></small></td>
                            <td style="padding: 8px; text-align: center;"><?php echo number_format($result['score'], 1); ?>%</small></td>
                            <td style="padding: 8px; text-align: center;" class="<?php echo $grade_class; ?>"><strong><?php echo $grade; ?></strong></small></td>
                            <td style="padding: 8px;"><?php echo $remarks; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Summary -->
            <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #1a3a5c;">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 15px;">
                    <?php if ($school_level == 'primary'): ?>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">TOTAL SCORE</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo number_format($total_score, 1); ?>%</div>
                        </div>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">AVERAGE</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo number_format($average, 2); ?>%</div>
                        </div>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">OVERALL GRADE</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo $overall_grade; ?></div>
                        </div>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">CLASS RANK</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo $rank; ?> / <?php echo $total_students; ?></div>
                        </div>
                    <?php else: ?>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">TOTAL POINTS</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo $total_points; ?></div>
                        </div>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">DIVISION</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo $division; ?></div>
                        </div>
                        <div style="flex: 1; text-align: center; background: white; padding: 10px; border-radius: 6px;">
                            <div style="font-size: 12px; color: #666;">CLASS RANK</div>
                            <div style="font-size: 20px; font-weight: bold;"><?php echo $rank; ?> / <?php echo $total_students; ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Best 7 for Secondary -->
            <?php if ($school_level == 'secondary' && !empty($best_7)): ?>
            <div style="background: #fff3cd; padding: 12px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #ffc107;">
                <strong>📊 BEST 7 SUBJECTS:</strong> 
                <?php 
                $best_list = [];
                foreach ($best_7 as $b) {
                    $best_list[] = $b['subject'] . ' (' . $b['points'] . ' pts)';
                }
                echo implode(' | ', $best_list);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Remarks -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0;">
                <div style="border-top: 2px solid #dee2e6; padding-top: 10px;">
                    <strong>📝 TEACHER'S REMARKS</strong>
                    <div style="border-bottom: 1px solid #dee2e6; padding: 8px 0; min-height: 50px;">
                        <?php if ($school_level == 'primary' && isset($overall_grade)) {
                            if ($overall_grade == 'A') echo 'Excellent performance. Keep it up!';
                            elseif ($overall_grade == 'B') echo 'Very good performance. Room for improvement.';
                            elseif ($overall_grade == 'C') echo 'Good performance. Work harder next term.';
                            elseif ($overall_grade == 'D') echo 'Satisfactory. Needs more effort.';
                            elseif ($overall_grade == 'E') echo 'Below average. See me for help.';
                            else echo 'Needs significant improvement. Parents consultation required.';
                        } else { echo '_________________________________'; } ?>
                    </div>
                </div>
                <div style="border-top: 2px solid #dee2e6; padding-top: 10px;">
                    <strong>👨‍🏫 HEAD TEACHER'S REMARKS</strong>
                    <div style="border-bottom: 1px solid #dee2e6; padding: 8px 0; min-height: 50px;">_________________________________</div>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="text-align: center; padding: 15px; font-size: 11px; color: #666; border-top: 1px solid #dee2e6; margin-top: 20px;">
                <p>This is a computer-generated report card. It is valid without signature.</p>
                <p>&copy; <?php echo date('Y'); ?> SSMS Tanzania - Smart School Management System</p>
            </div>
        </div>
    </div>
</div>

<style>
.grade-A { color: #28a745; font-weight: bold; }
.grade-B { color: #17a2b8; font-weight: bold; }
.grade-C { color: #ffc107; font-weight: bold; }
.grade-D, .grade-E, .grade-F { color: #dc3545; font-weight: bold; }
</style>

<?php include 'includes/teacher_footer.php'; ?>