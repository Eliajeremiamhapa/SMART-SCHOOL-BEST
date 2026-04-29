<?php
// TEACHERS/class_results.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

// ============ REPORT HELPER FUNCTIONS (embedded) ============
function getClassResults($class_name, $school_level, $pdo) {
    $results = [];
    
    // ✅ FIXED: Using correct column names (id, full_name)
    $stmt = $pdo->prepare("
        SELECT id, full_name, student_number 
        FROM students 
        WHERE class = ? AND is_active = 1
        ORDER BY full_name
    ");
    $stmt->execute([$class_name]);
    $students = $stmt->fetchAll();
    
    foreach ($students as $student) {
        // ✅ FIXED: Using exam_results table (not marks table)
        $stmt = $pdo->prepare("
            SELECT er.*, s.subject_name 
            FROM exam_results er
            LEFT JOIN subjects s ON er.subject = s.subject_name
            WHERE er.student_id = ? 
            AND er.exam_type IN ('Term Exam', 'Final')
            ORDER BY er.subject
        ");
        $stmt->execute([$student['id']]);
        $marks = $stmt->fetchAll();
        
        if (empty($marks)) {
            // Include students with no marks but show 0
            if ($school_level == 'primary') {
                $results[] = [
                    'student_id' => $student['id'],
                    'full_name' => $student['full_name'],
                    'student_number' => $student['student_number'],
                    'average' => 0,
                    'grade' => 'F',
                    'total_points' => 0,
                    'division' => '',
                    'best_7' => []
                ];
            } else {
                $results[] = [
                    'student_id' => $student['id'],
                    'full_name' => $student['full_name'],
                    'student_number' => $student['student_number'],
                    'average' => 0,
                    'grade' => '',
                    'total_points' => 0,
                    'division' => '0',
                    'best_7' => []
                ];
            }
            continue;
        }
        
        if ($school_level == 'primary') {
            // Primary school calculation (Average based)
            $total_score = 0;
            $subject_count = 0;
            
            foreach ($marks as $mark) {
                if ($mark['score'] !== null) {
                    $total_score += floatval($mark['score']);
                    $subject_count++;
                }
            }
            
            $average = $subject_count > 0 ? round($total_score / $subject_count, 2) : 0;
            $grade = getGradeFromScore($average, $school_level, $pdo);
            
            $results[] = [
                'student_id' => $student['id'],
                'full_name' => $student['full_name'],
                'student_number' => $student['student_number'],
                'average' => $average,
                'grade' => $grade,
                'total_points' => 0,
                'division' => '',
                'best_7' => []
            ];
        } else {
            // Secondary school calculation (Points & Best 7)
            $subject_points = [];
            
            foreach ($marks as $mark) {
                if ($mark['score'] !== null) {
                    $points = getPointsFromScore($mark['score'], $pdo);
                    $subject_points[] = [
                        'subject' => $mark['subject'],
                        'score' => $mark['score'],
                        'points' => $points,
                        'grade' => $mark['grade']
                    ];
                }
            }
            
            // Sort by points (lower points are better for secondary)
            usort($subject_points, function($a, $b) {
                return $a['points'] - $b['points'];
            });
            
            // Take best 7 subjects
            $best_7 = array_slice($subject_points, 0, 7);
            $total_points = array_sum(array_column($best_7, 'points'));
            $division = getDivisionFromPoints($total_points);
            
            $results[] = [
                'student_id' => $student['id'],
                'full_name' => $student['full_name'],
                'student_number' => $student['student_number'],
                'average' => 0,
                'grade' => '',
                'total_points' => $total_points,
                'division' => $division,
                'best_7' => $best_7
            ];
        }
    }
    
    // Sort results
    if ($school_level == 'primary') {
        usort($results, function($a, $b) {
            return $b['average'] <=> $a['average'];
        });
    } else {
        usort($results, function($a, $b) {
            return $a['total_points'] <=> $b['total_points'];
        });
    }
    
    // Add ranks
    foreach ($results as $index => &$result) {
        $result['rank'] = $index + 1;
    }
    
    return $results;
}

function getGradeFromScore($score, $school_level, $pdo) {
    $scale = getGradingScale($school_level, $pdo);
    foreach ($scale as $grade) {
        if ($score >= $grade['min'] && $score <= $grade['max']) {
            return $grade['grade'];
        }
    }
    return 'F';
}

function getPointsFromScore($score, $pdo) {
    $scale = getGradingScale('secondary', $pdo);
    foreach ($scale as $grade) {
        if ($score >= $grade['min'] && $score <= $grade['max']) {
            return $grade['points'];
        }
    }
    return 7;
}

function getDivisionFromPoints($points) {
    if ($points <= 7) return 'I';
    if ($points <= 12) return 'II';
    if ($points <= 17) return 'III';
    if ($points <= 21) return 'IV';
    return '0';
}

function getGradingScale($school_level, $pdo) {
    if ($school_level == 'primary') {
        return [
            ['grade' => 'A', 'min' => 80, 'max' => 100, 'description' => 'Outstanding'],
            ['grade' => 'B', 'min' => 70, 'max' => 79, 'description' => 'Very Good'],
            ['grade' => 'C', 'min' => 60, 'max' => 69, 'description' => 'Good'],
            ['grade' => 'D', 'min' => 50, 'max' => 59, 'description' => 'Average'],
            ['grade' => 'E', 'min' => 40, 'max' => 49, 'description' => 'Below Average'],
            ['grade' => 'F', 'min' => 0, 'max' => 39, 'description' => 'Fail']
        ];
    } else {
        return [
            ['grade' => 'A', 'min' => 80, 'max' => 100, 'points' => 1, 'description' => 'Excellent'],
            ['grade' => 'B', 'min' => 70, 'max' => 79, 'points' => 2, 'description' => 'Very Good'],
            ['grade' => 'C', 'min' => 60, 'max' => 69, 'points' => 3, 'description' => 'Good'],
            ['grade' => 'D', 'min' => 50, 'max' => 59, 'points' => 4, 'description' => 'Average'],
            ['grade' => 'E', 'min' => 40, 'max' => 49, 'points' => 5, 'description' => 'Below Average'],
            ['grade' => 'F', 'min' => 0, 'max' => 39, 'points' => 7, 'description' => 'Fail']
        ];
    }
}
// ============ END OF HELPER FUNCTIONS ============

$page_title = "Class Results";
include 'includes/teacher_header.php';

// Get teacher's classes
$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

$selected_class = $_GET['class'] ?? '';
$school_level = $_GET['level'] ?? 'primary';
$results = [];

if ($selected_class) {
    // Get school level from class
    $stmt = $pdo->prepare("SELECT school_level FROM students WHERE class = ? LIMIT 1");
    $stmt->execute([$selected_class]);
    $student_level = $stmt->fetch();
    $school_level = $student_level['school_level'] ?? 'primary';
    
    $results = getClassResults($selected_class, $school_level, $pdo);
}

$grading_scale = getGradingScale($school_level, $pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Results - Teacher Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .form-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-sm { padding: 5px 10px; font-size: 12px; background: #27ae60; color: white; text-decoration: none; border-radius: 3px; display: inline-block; }
        .btn-sm:hover { background: #229954; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .data-table th { background: #34495e; color: white; font-weight: 600; }
        .data-table tr:hover { background: #f5f5f5; }
        .table-responsive { overflow-x: auto; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .alert-warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
    </style>
</head>
<body>
<div class="container">
    <h1>📊 Class Results & Ranking</h1>
    
    <div class="form-card">
        <form method="GET">
            <div class="two-columns">
                <div class="form-group">
                    <label>Select Class</label>
                    <select name="class" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['class']); ?>" <?php echo $selected_class == $c['class'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
    </div>
    
    <?php if ($selected_class && !empty($results)): ?>
    
    <!-- Grading Scale Reference -->
    <div class="form-card">
        <h4>📊 Grading Scale (<?php echo ucfirst($school_level); ?> School)</h4>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>Score Range</th>
                        <?php if ($school_level == 'secondary'): ?>
                        <th>Points</th>
                        <?php endif; ?>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grading_scale as $g): ?>
                    <tr>
                        <td><strong><?php echo $g['grade']; ?></strong></td>
                        <td><?php echo $g['min']; ?>% - <?php echo $g['max']; ?>%</small></td>
                        <?php if ($school_level == 'secondary'): ?>
                        <td><?php echo $g['points']; ?></td>
                        <?php endif; ?>
                        <td><?php echo $g['description']; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Class Results Table -->
    <div class="form-card">
        <h3>📋 <?php echo htmlspecialchars($selected_class); ?> - Term Results</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <?php if ($school_level == 'primary'): ?>
                        <th>Average (%)</th>
                        <th>Grade</th>
                        <?php else: ?>
                        <th>Total Points</th>
                        <th>Division</th>
                        <th>Best 7 Subjects</th>
                        <?php endif; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td><strong>#<?php echo $result['rank']; ?></strong></small></td>
                        <td><?php echo htmlspecialchars($result['full_name']); ?></small></small></td>
                        <td><?php echo htmlspecialchars($result['student_number']); ?></small></small></td>
                        <?php if ($school_level == 'primary'): ?>
                        <td><?php echo $result['average']; ?>%</small></td>
                        <td><strong><?php echo $result['grade']; ?></strong></small></td>
                        <?php else: ?>
                        <td><?php echo $result['total_points']; ?> points</small></small></td>
                        <td><strong>Division <?php echo $result['division']; ?></strong></small></small></td>
                        <td>
                            <?php 
                            if (!empty($result['best_7'])) {
                                $subjects = [];
                                foreach ($result['best_7'] as $sub) {
                                    $subjects[] = $sub['subject'] . ' (' . $sub['points'] . ' pts)';
                                }
                                echo implode(', ', $subjects);
                            } else {
                                echo '-';
                            }
                            ?>
                         </small></td>
                        <?php endif; ?>
                        <td>
                            <a href="student_report.php?id=<?php echo $result['student_id']; ?>" class="btn-sm" target="_blank">📄 Full Report</a>
                         </small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="alert alert-info" style="margin-top: 1rem;">
            <strong>Summary:</strong>
            Total Students: <?php echo count($results); ?> |
            <?php if ($school_level == 'primary'): ?>
            Class Average: <?php 
                $avg_sum = array_sum(array_column($results, 'average'));
                echo round($avg_sum / count($results), 2); ?>%
            <?php else: ?>
            Best Student: <?php echo htmlspecialchars($results[0]['full_name']); ?> (<?php echo $results[0]['total_points']; ?> points)
            <?php endif; ?>
        </div>
    </div>
    
    <?php elseif ($selected_class): ?>
        <div class="alert alert-warning">No results found for <?php echo htmlspecialchars($selected_class); ?>. Please enter marks first.</div>
    <?php endif; ?>
</div>
</body>
</html>

<?php include 'includes/teacher_footer.php'; ?>