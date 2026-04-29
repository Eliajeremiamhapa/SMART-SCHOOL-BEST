<?php
// TEACHERS/behavior.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost'; $dbname = 'accountant'; $username = 'root'; $password = '';
try { $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); } catch(PDOException $e) { die("Database Error: " . $e->getMessage()); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') { header('Location: ../ACCOUNTANT/login.php'); exit(); }

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_behavior'])) {
    $student_id = $_POST['student_id'];
    $behavior_type = $_POST['behavior_type'];
    $description = $_POST['description'];
    $points = $_POST['points'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO behavior_records (student_id, teacher_id, behavior_type, description, points, record_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$student_id, $_SESSION['user_id'], $behavior_type, $description, $points]);
        $success = "✅ Behavior recorded!";
    } catch(Exception $e) { $error = "❌ Error: " . $e->getMessage(); }
}

$page_title = "Behavior Tracking";
include 'includes/teacher_header.php';

$stmt = $pdo->prepare("SELECT class FROM teacher_classes WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();
?>

<div class="container">
    <h1>📊 Student Behavior Tracking</h1>
    <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <div class="form-card">
        <form method="POST">
            <div class="form-group"><label>Select Class</label>
                <select name="class" id="class_select" required><option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?><option value="<?php echo $c['class']; ?>"><?php echo $c['class']; ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Select Student</label>
                <select name="student_id" id="student_select" required><option value="">-- First Select Class --</option></select>
            </div>
            <div class="form-group"><label>Behavior Type</label>
                <select name="behavior_type" required><option value="positive">✅ Positive (Good behavior)</option><option value="negative">❌ Negative (Misbehavior)</option><option value="warning">⚠️ Warning</option><option value="achievement">🏆 Achievement</option></select>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3" required placeholder="Describe the behavior..."></textarea></div>
            <div class="form-group"><label>Points</label><input type="number" name="points" value="0" placeholder="Points (positive or negative)"></div>
            <button type="submit" name="save_behavior" class="btn btn-primary">💾 Save Behavior Record</button>
        </form>
    </div>
</div>

<script>
document.getElementById('class_select').addEventListener('change', function() {
    fetch('get_students.php?class=' + encodeURIComponent(this.value))
        .then(r => r.json()).then(data => {
            var select = document.getElementById('student_select');
            select.innerHTML = '<option value="">-- Select Student --</option>';
            data.forEach(s => select.innerHTML += `<option value="${s.id}">${s.full_name} (${s.student_number})</option>`);
        });
});
</script>
<?php include 'includes/teacher_footer.php'; ?>