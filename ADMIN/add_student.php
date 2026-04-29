<?php
// ADMIN/add_student.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Add New Student";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Generate a random password
function generatePassword($length = 8) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// Get school settings to determine available classes
$stmt = $pdo->query("SELECT school_type FROM school_settings LIMIT 1");
$school_settings = $stmt->fetch();
$school_type = $school_settings['school_type'] ?? 'both';

// Define classes based on school type
$primary_classes = ['Standard 1', 'Standard 2', 'Standard 3', 'Standard 4', 'Standard 5', 'Standard 6', 'Standard 7'];
$secondary_classes = ['Form 1A', 'Form 1B', 'Form 2A', 'Form 2B', 'Form 3A', 'Form 3B', 'Form 4A', 'Form 4B', 'Form 5A', 'Form 5B', 'Form 6A', 'Form 6B'];

if ($school_type == 'primary') {
    $classes = $primary_classes;
} elseif ($school_type == 'secondary') {
    $classes = $secondary_classes;
} else {
    $classes = array_merge($primary_classes, $secondary_classes);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_number = trim($_POST['student_number']);
    $full_name = trim($_POST['full_name']);
    $class = trim($_POST['class']);
    $parent_phone = trim($_POST['parent_phone']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $school_level = $_POST['school_level'];
    $prem_number = trim($_POST['prem_number']);
    $psle_number = trim($_POST['psle_number']);
    $index_number = trim($_POST['index_number']);
    
    // If username not provided, generate from student number
    if (empty($username)) {
        $username = strtolower(str_replace(' ', '_', $full_name));
    }
    
    // If password not provided, generate random
    if (empty($password)) {
        $password = generatePassword();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception("Username '{$username}' already exists. Please use a different username.");
        }
        
        // Check if student number already exists
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
        $stmt->execute([$student_number]);
        if ($stmt->fetch()) {
            throw new Exception("Student number '{$student_number}' already exists.");
        }
        
        // Check if PREM number already exists (if provided)
        if (!empty($prem_number)) {
            $stmt = $pdo->prepare("SELECT id FROM students WHERE prem_number = ?");
            $stmt->execute([$prem_number]);
            if ($stmt->fetch()) {
                throw new Exception("PREM Number '{$prem_number}' already exists. Each student must have a unique PREM number.");
            }
        }
        
        // Create user account
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, is_active) VALUES (?, ?, ?, ?, 'student', 1)");
        $stmt->execute([$username, $hashed_password, $full_name, $email]);
        
        // Create student profile - WITHOUT user_id and WITHOUT email (since columns don't exist)
        $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, school_level, prem_number, psle_number, index_number, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$student_number, $full_name, $class, $parent_phone, $school_level, $prem_number, $psle_number, $index_number]);
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'Created Student', "Created {$school_level} student account: {$full_name} ({$student_number})", $_SERVER['REMOTE_ADDR']]);
        
        $pdo->commit();
        
        $student_label = ($school_level == 'primary') ? 'Pupil' : 'Student';
        
        $success = "
            <strong>✅ {$student_label} account created successfully!</strong><br><br>
            <strong>Student Details:</strong><br>
            Name: {$full_name}<br>
            Student Number: {$student_number}<br>
            Class: {$class}<br>
            School Level: " . ucfirst($school_level) . "<br>
            Parent Phone: {$parent_phone}<br>
            PREM Number: " . (!empty($prem_number) ? $prem_number : 'Not provided') . "<br>";
        
        if ($school_level == 'secondary') {
            $success .= "PSLE Number: " . (!empty($psle_number) ? $psle_number : 'Not provided') . "<br>";
            $success .= "Index Number: " . (!empty($index_number) ? $index_number : 'Not provided') . "<br>";
        }
        
        $success .= "
            <br>
            <strong>Login Credentials:</strong><br>
            Username: <strong>{$username}</strong><br>
            Password: <strong>{$password}</strong><br>
            <br>
            <a href='../STUDENTS/index.php' target='_blank'>Click here to test login →</a>
        ";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ " . $e->getMessage();
    }
}
?>

<div class="container">
    <h1>➕ Add New Student</h1>
    <p>Create a new student account. The student will receive these credentials to login.</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php else: ?>
    
    <div class="form-card">
        <form method="POST" id="studentForm">
            <div class="two-columns">
                <div class="form-group">
                    <label>Student Number *</label>
                    <input type="text" name="student_number" required placeholder="e.g., SSMS001, 2025001">
                    <small>Unique identification number for the student</small>
                </div>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="e.g., Juma Hassan">
                </div>
                <div class="form-group">
                    <label>School Level *</label>
                    <select name="school_level" id="school_level" required>
                        <option value="">-- Select School Level --</option>
                        <option value="primary">Primary School (Pupil)</option>
                        <option value="secondary">Secondary School (Student)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class *</label>
                    <select name="class" id="class_select" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Parent Phone *</label>
                    <input type="tel" name="parent_phone" required placeholder="e.g., 0712345678">
                    <small>For SMS notifications and emergency contact</small>
                </div>
                <div class="form-group">
                    <label>PREM Number *</label>
                    <input type="text" name="prem_number" placeholder="e.g., 2024123456789">
                    <small>Primary Record Education Management Number (Unique ID for life)</small>
                </div>
                
                <!-- Secondary School Only Fields -->
                <div id="secondary_fields" style="display: none;">
                    <div class="form-group">
                        <label>PSLE Number</label>
                        <input type="text" name="psle_number" placeholder="e.g., PSLE2024-12345">
                        <small>Primary School Leaving Examination Number</small>
                    </div>
                    <div class="form-group">
                        <label>NECTA Index Number</label>
                        <input type="text" name="index_number" placeholder="e.g., S1234/2024/0001">
                        <small>National Examination Council Index Number</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email (Optional)</label>
                    <input type="email" name="email" placeholder="student@example.com">
                    <small>Email is stored in users table only</small>
                </div>
                <div class="form-group">
                    <label>Username (Optional)</label>
                    <input type="text" name="username" placeholder="Leave empty to auto-generate">
                    <small>If left empty, username will be generated from name</small>
                </div>
                <div class="form-group">
                    <label>Password (Optional)</label>
                    <input type="text" name="password" placeholder="Leave empty to auto-generate">
                    <small>If left empty, a random password will be generated</small>
                </div>
            </div>
            
            <div class="alert alert-info" style="margin-top: 1rem;">
                <i class="fas fa-info-circle"></i> 
                <strong>About PREM Number:</strong> This is a unique identification number assigned by the Tanzanian education system. Each student must have a unique PREM number that stays with them throughout their education journey.
            </div>
            
            <button type="submit" class="btn btn-primary">➕ Create Student Account</button>
            <a href="users.php?role=student" class="btn btn-secondary">View All Students</a>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// Show/hide secondary school fields based on school level selection
document.getElementById('school_level').addEventListener('change', function() {
    var secondaryFields = document.getElementById('secondary_fields');
    var classSelect = document.getElementById('class_select');
    
    if (this.value === 'secondary') {
        secondaryFields.style.display = 'block';
        // Update class options for secondary
        <?php if ($school_type == 'both'): ?>
        classSelect.innerHTML = '<option value="">-- Select Class --</option><?php foreach ($secondary_classes as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?>';
        <?php endif; ?>
    } else if (this.value === 'primary') {
        secondaryFields.style.display = 'none';
        // Update class options for primary
        <?php if ($school_type == 'both'): ?>
        classSelect.innerHTML = '<option value="">-- Select Class --</option><?php foreach ($primary_classes as $c): ?><option value="<?php echo $c; ?>"><?php echo $c; ?></option><?php endforeach; ?>';
        <?php endif; ?>
    } else {
        secondaryFields.style.display = 'none';
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>