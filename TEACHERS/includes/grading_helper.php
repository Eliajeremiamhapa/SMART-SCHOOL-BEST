<?php
// includes/grading_helper.php
// Grading system helper functions for Primary and Secondary schools

// Prevent function redeclaration errors
if (!function_exists('calculateGrade')) {
    function calculateGrade($score, $school_level = 'primary') {
        if ($school_level == 'primary') {
            // Primary grading system (A-E)
            if ($score >= 80) return 'A';
            if ($score >= 70) return 'B';
            if ($score >= 60) return 'C';
            if ($score >= 50) return 'D';
            if ($score >= 40) return 'E';
            return 'F';
        } else {
            // Secondary grading system (A-F)
            if ($score >= 80) return 'A';
            if ($score >= 70) return 'B';
            if ($score >= 60) return 'C';
            if ($score >= 50) return 'D';
            if ($score >= 40) return 'E';
            return 'F';
        }
    }
}

if (!function_exists('calculatePoints')) {
    function calculatePoints($grade) {
        $points = [
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6
        ];
        return $points[$grade] ?? 6;
    }
}

// calculateDivision function removed from here - it exists in report_helper.php

if (!function_exists('calculateAverage')) {
    function calculateAverage($scores) {
        if (empty($scores)) return 0;
        return round(array_sum($scores) / count($scores), 2);
    }
}

if (!function_exists('getBestSubjects')) {
    function getBestSubjects($results, $n = 7) {
        usort($results, function($a, $b) {
            return $a['points'] - $b['points'];
        });
        return array_slice($results, 0, $n);
    }
}

if (!function_exists('calculateBest7Points')) {
    function calculateBest7Points($results) {
        $best7 = getBestSubjects($results, 7);
        $total = 0;
        foreach ($best7 as $subject) {
            $total += $subject['points'];
        }
        return $total;
    }
}

if (!function_exists('getGradeDescription')) {
    function getGradeDescription($grade) {
        $descriptions = [
            'A' => 'Outstanding',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Satisfactory',
            'E' => 'Pass',
            'F' => 'Fail'
        ];
        return $descriptions[$grade] ?? 'Unknown';
    }
}

if (!function_exists('getGradeRemarks')) {
    function getGradeRemarks($grade) {
        $remarks = [
            'A' => 'Excellent performance',
            'B' => 'Very good performance',
            'C' => 'Good performance',
            'D' => 'Satisfactory performance',
            'E' => 'Minimum pass',
            'F' => 'Needs improvement'
        ];
        return $remarks[$grade] ?? 'No remarks';
    }
}

if (!function_exists('calculateStudentRank')) {
    function calculateStudentRank($student_id, $class, $school_level, $pdo) {
        if ($school_level == 'primary') {
            $sql = "
                SELECT student_id, AVG(score) as avg_score
                FROM exam_results er
                JOIN students s ON er.student_id = s.id
                WHERE s.class = ? AND s.is_active = 1
                GROUP BY student_id
                ORDER BY avg_score DESC
            ";
        } else {
            $sql = "
                SELECT student_id, SUM(CASE 
                    WHEN score >= 80 THEN 1
                    WHEN score >= 70 THEN 2
                    WHEN score >= 60 THEN 3
                    WHEN score >= 50 THEN 4
                    WHEN score >= 40 THEN 5
                    ELSE 6
                END) as total_points
                FROM exam_results er
                JOIN students s ON er.student_id = s.id
                WHERE s.class = ? AND s.is_active = 1
                GROUP BY student_id
                ORDER BY total_points ASC
            ";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$class]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $index => $row) {
            if ($row['student_id'] == $student_id) {
                return $index + 1;
            }
        }
        return 0;
    }
}

if (!function_exists('getStudentPerformanceSummary')) {
    function getStudentPerformanceSummary($student_id, $pdo) {
        $stmt = $pdo->prepare("SELECT school_level, class FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return ['error' => 'Student not found'];
        }
        
        $school_level = $student['school_level'] ?? 'primary';
        $class = $student['class'];
        
        $stmt = $pdo->prepare("
            SELECT subject, score, exam_type, exam_date 
            FROM exam_results 
            WHERE student_id = ? 
            ORDER BY exam_date DESC, subject
        ");
        $stmt->execute([$student_id]);
        $results = $stmt->fetchAll();
        
        if (empty($results)) {
            return [
                'has_results' => false,
                'message' => 'No results available yet'
            ];
        }
        
        $summary = [
            'has_results' => true,
            'school_level' => $school_level,
            'class' => $class,
            'subjects' => []
        ];
        
        $scores = [];
        $subject_results = [];
        
        foreach ($results as $result) {
            $grade = calculateGrade($result['score'], $school_level);
            $points = ($school_level == 'secondary') ? calculatePoints($grade) : null;
            
            $subject_results[] = [
                'subject' => $result['subject'],
                'score' => $result['score'],
                'grade' => $grade,
                'points' => $points,
                'exam_type' => $result['exam_type'],
                'exam_date' => $result['exam_date']
            ];
            
            $scores[] = $result['score'];
        }
        
        if ($school_level == 'primary') {
            $summary['average'] = calculateAverage($scores);
            $summary['overall_grade'] = calculateGrade($summary['average'], 'primary');
            $summary['overall_description'] = getGradeDescription($summary['overall_grade']);
        } else {
            $summary['best_7_points'] = calculateBest7Points($subject_results);
            // Use calculateDivision from report_helper.php
            if (function_exists('calculateDivision')) {
                $summary['division'] = calculateDivision($summary['best_7_points']);
            } else {
                // Fallback division calculation
                $points = $summary['best_7_points'];
                if ($points <= 17) $summary['division'] = 'I';
                elseif ($points <= 25) $summary['division'] = 'II';
                elseif ($points <= 33) $summary['division'] = 'III';
                else $summary['division'] = 'IV';
            }
            $summary['total_subjects'] = count($subject_results);
        }
        
        $summary['subjects'] = $subject_results;
        $summary['rank'] = calculateStudentRank($student_id, $class, $school_level, $pdo);
        
        return $summary;
    }
}

if (!function_exists('saveExamResult')) {
    function saveExamResult($student_id, $subject, $score, $exam_type, $pdo) {
        if ($score < 0 || $score > 100) {
            return ['success' => false, 'message' => 'Score must be between 0 and 100'];
        }
        
        $stmt = $pdo->prepare("SELECT school_level FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return ['success' => false, 'message' => 'Student not found'];
        }
        
        $school_level = $student['school_level'] ?? 'primary';
        $grade = calculateGrade($score, $school_level);
        $term = getCurrentTerm($pdo);
        $academic_year = getCurrentAcademicYear($pdo);
        
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM exam_results 
                WHERE student_id = ? AND subject = ? AND exam_type = ? AND term = ? AND academic_year = ?
            ");
            $stmt->execute([$student_id, $subject, $exam_type, $term, $academic_year]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("
                    UPDATE exam_results 
                    SET score = ?, grade = ?, exam_date = CURDATE()
                    WHERE student_id = ? AND subject = ? AND exam_type = ? AND term = ? AND academic_year = ?
                ");
                $stmt->execute([$score, $grade, $student_id, $subject, $exam_type, $term, $academic_year]);
                return ['success' => true, 'message' => 'Result updated successfully'];
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO exam_results (student_id, subject, score, grade, exam_type, term, academic_year, exam_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $stmt->execute([$student_id, $subject, $score, $grade, $exam_type, $term, $academic_year]);
                return ['success' => true, 'message' => 'Result saved successfully'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('getCurrentTerm')) {
    function getCurrentTerm($pdo) {
        $stmt = $pdo->query("SELECT current_term FROM school_settings LIMIT 1");
        $settings = $stmt->fetch();
        return $settings['current_term'] ?? 'Term 1';
    }
}

if (!function_exists('getCurrentAcademicYear')) {
    function getCurrentAcademicYear($pdo) {
        $stmt = $pdo->query("SELECT academic_year FROM school_settings LIMIT 1");
        $settings = $stmt->fetch();
        return $settings['academic_year'] ?? date('Y');
    }
}

if (!function_exists('getGradingScale')) {
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
}
?>