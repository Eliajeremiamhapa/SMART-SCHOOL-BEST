<?php
// includes/report_helper.php
// Helper functions for NECTA Reports

/**
 * Calculate student's position/rank in class
 * @param int $student_id - Student ID
 * @param string $class - Class name
 * @param string $school_level - 'primary' or 'secondary'
 * @param PDO $pdo - Database connection
 * @return int - Rank position (1 = highest)
 */
function calculateRank($student_id, $class, $school_level, $pdo) {
    if ($school_level == 'primary') {
        // Primary: rank by average score
        $sql = "
            SELECT er.student_id, AVG(er.score) as avg_score
            FROM exam_results er
            JOIN students s ON er.student_id = s.id
            WHERE s.class = ? AND s.is_active = 1 AND er.exam_type = 'Term Exam'
            GROUP BY er.student_id
            ORDER BY avg_score DESC
        ";
    } else {
        // Secondary: rank by total points (lower points is better)
        $sql = "
            SELECT er.student_id, SUM(er.points) as total_points
            FROM exam_results er
            JOIN students s ON er.student_id = s.id
            WHERE s.class = ? AND s.is_active = 1 AND er.exam_type = 'Term Exam'
            GROUP BY er.student_id
            ORDER BY total_points ASC
        ";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class]);
    $results = $stmt->fetchAll();
    
    $rank = 1;
    foreach ($results as $index => $row) {
        if ($row['student_id'] == $student_id) {
            return $index + 1;
        }
    }
    return count($results);
}

/**
 * Calculate student's average score (Primary)
 * @param int $student_id - Student ID
 * @param PDO $pdo - Database connection
 * @return float - Average score
 */
function calculateStudentAverage($student_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT AVG(score) as average 
        FROM exam_results 
        WHERE student_id = ? AND exam_type = 'Term Exam'
    ");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch();
    return round($result['average'] ?? 0, 2);
}

/**
 * Calculate student's total points (Secondary)
 * @param int $student_id - Student ID
 * @param PDO $pdo - Database connection
 * @return int - Total points
 */
function calculateStudentPoints($student_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT SUM(points) as total_points 
        FROM exam_results 
        WHERE student_id = ? AND exam_type = 'Term Exam'
    ");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch();
    return $result['total_points'] ?? 0;
}

/**
 * Get student's best 7 subjects (Secondary)
 * @param int $student_id - Student ID
 * @param PDO $pdo - Database connection
 * @return array - Best 7 subjects with points
 */
function getBest7Subjects($student_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT subject, score, points, grade
        FROM exam_results 
        WHERE student_id = ? AND exam_type = 'Term Exam'
        ORDER BY points ASC
        LIMIT 7
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

/**
 * Calculate division for secondary (based on total points)
 * Division I: 7-17 points
 * Division II: 18-25 points
 * Division III: 26-33 points
 * Division IV: 34+ points
 * @param int $total_points - Total points
 * @return string - Division
 */
function calculateDivision($total_points) {
    if ($total_points <= 17) return 'I';
    if ($total_points <= 25) return 'II';
    if ($total_points <= 33) return 'III';
    return 'IV';
}

/**
 * Get all students in a class with their results
 * @param string $class - Class name
 * @param string $school_level - 'primary' or 'secondary'
 * @param PDO $pdo - Database connection
 * @return array - Students with results
 */
function getClassResults($class, $school_level, $pdo) {
    // Get all students in class
    $stmt = $pdo->prepare("
        SELECT id, student_number, full_name, school_level
        FROM students 
        WHERE class = ? AND is_active = 1
        ORDER BY full_name
    ");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll();
    
    $results = [];
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        if ($school_level == 'primary') {
            $average = calculateStudentAverage($student_id, $pdo);
            $grade = calculateGrade($average, 'primary');
            $results[] = [
                'student_id' => $student_id,
                'student_number' => $student['student_number'],
                'full_name' => $student['full_name'],
                'average' => $average,
                'grade' => $grade,
                'rank' => 0 // Will be calculated after sorting
            ];
        } else {
            $total_points = calculateStudentPoints($student_id, $pdo);
            $division = calculateDivision($total_points);
            $best_7 = getBest7Subjects($student_id, $pdo);
            $results[] = [
                'student_id' => $student_id,
                'student_number' => $student['student_number'],
                'full_name' => $student['full_name'],
                'total_points' => $total_points,
                'division' => $division,
                'best_7' => $best_7,
                'rank' => 0
            ];
        }
    }
    
    // Sort and assign ranks
    if ($school_level == 'primary') {
        usort($results, function($a, $b) {
            return $b['average'] <=> $a['average'];
        });
    } else {
        usort($results, function($a, $b) {
            return $a['total_points'] <=> $b['total_points'];
        });
    }
    
    foreach ($results as $index => &$result) {
        $result['rank'] = $index + 1;
    }
    
    return $results;
}

/**
 * Get grading scale for display
 * @param string $school_level - 'primary' or 'secondary'
 * @return array
 */
function getGradingScale($school_level = 'primary') {
    if ($school_level == 'primary') {
        return [
            ['grade' => 'A', 'min' => 80, 'max' => 100, 'description' => 'Outstanding'],
            ['grade' => 'B', 'min' => 70, 'max' => 79, 'description' => 'Very Good'],
            ['grade' => 'C', 'min' => 60, 'max' => 69, 'description' => 'Good'],
            ['grade' => 'D', 'min' => 50, 'max' => 59, 'description' => 'Satisfactory'],
            ['grade' => 'E', 'min' => 40, 'max' => 49, 'description' => 'Pass'],
            ['grade' => 'F', 'min' => 0, 'max' => 39, 'description' => 'Fail']
        ];
    } else {
        return [
            ['grade' => 'A', 'min' => 80, 'max' => 100, 'points' => 1, 'description' => 'Outstanding'],
            ['grade' => 'B', 'min' => 70, 'max' => 79, 'points' => 2, 'description' => 'Very Good'],
            ['grade' => 'C', 'min' => 60, 'max' => 69, 'points' => 3, 'description' => 'Good'],
            ['grade' => 'D', 'min' => 50, 'max' => 59, 'points' => 4, 'description' => 'Satisfactory'],
            ['grade' => 'E', 'min' => 40, 'max' => 49, 'points' => 5, 'description' => 'Pass'],
            ['grade' => 'F', 'min' => 0, 'max' => 39, 'points' => 6, 'description' => 'Fail']
        ];
    }
}
?>