<?php
// ADMIN/school_settings.php
require_once '../config/database.php';

// Only super_admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "School Settings";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Get current settings
$stmt = $pdo->query("SELECT * FROM school_settings LIMIT 1");
$settings = $stmt->fetch();

if (!$settings) {
    // Insert default settings
    $pdo->query("INSERT INTO school_settings (school_name) VALUES ('SSMS Tanzania')");
    $stmt = $pdo->query("SELECT * FROM school_settings LIMIT 1");
    $settings = $stmt->fetch();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $school_name = $_POST['school_name'];
    $school_address = $_POST['school_address'];
    $school_phone = $_POST['school_phone'];
    $school_email = $_POST['school_email'];
    $school_website = $_POST['school_website'];
    $tin_number = $_POST['tin_number'];
    $registration_number = $_POST['registration_number'];
    $motto = $_POST['motto'];
    $academic_year = $_POST['academic_year'];
    $current_term = $_POST['current_term'];
    $term_start_date = $_POST['term_start_date'];
    $term_end_date = $_POST['term_end_date'];
    $currency = $_POST['currency'];
    
    // NEW FIELDS FOR PRIMARY/SECONDARY
    $school_type = $_POST['school_type'];
    $enable_period_attendance = isset($_POST['enable_period_attendance']) ? 1 : 0;
    $default_grading_system = $_POST['default_grading_system'];
    
    $stmt = $pdo->prepare("
        UPDATE school_settings SET 
            school_name = ?, school_address = ?, school_phone = ?, 
            school_email = ?, school_website = ?, tin_number = ?,
            registration_number = ?, motto = ?, academic_year = ?,
            current_term = ?, term_start_date = ?, term_end_date = ?,
            currency = ?, school_type = ?, enable_period_attendance = ?,
            default_grading_system = ?
        WHERE id = 1
    ");
    $stmt->execute([
        $school_name, $school_address, $school_phone, $school_email, 
        $school_website, $tin_number, $registration_number, $motto,
        $academic_year, $current_term, $term_start_date, $term_end_date,
        $currency, $school_type, $enable_period_attendance, $default_grading_system
    ]);
    
    $success = "✅ School settings updated successfully!";
    
    // Refresh settings
    $stmt = $pdo->query("SELECT * FROM school_settings LIMIT 1");
    $settings = $stmt->fetch();
}
?>

<div class="container">
    <h1>🏫 School Settings</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST">
            <div class="two-columns">
                <div class="form-group">
                    <label>School Name</label>
                    <input type="text" name="school_name" value="<?php echo htmlspecialchars($settings['school_name']); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>School Phone</label>
                    <input type="text" name="school_phone" value="<?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>School Email</label>
                    <input type="email" name="school_email" value="<?php echo htmlspecialchars($settings['school_email'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>School Website</label>
                    <input type="text" name="school_website" value="<?php echo htmlspecialchars($settings['school_website'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>TIN Number</label>
                    <input type="text" name="tin_number" value="<?php echo htmlspecialchars($settings['tin_number'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="registration_number" value="<?php echo htmlspecialchars($settings['registration_number'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Motto / Slogan</label>
                    <input type="text" name="motto" value="<?php echo htmlspecialchars($settings['motto'] ?? ''); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" value="<?php echo htmlspecialchars($settings['academic_year'] ?? '2025'); ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Current Term</label>
                    <select name="current_term" class="form-control">
                        <option value="Term 1" <?php echo ($settings['current_term'] ?? '') == 'Term 1' ? 'selected' : ''; ?>>Term 1</option>
                        <option value="Term 2" <?php echo ($settings['current_term'] ?? '') == 'Term 2' ? 'selected' : ''; ?>>Term 2</option>
                        <option value="Term 3" <?php echo ($settings['current_term'] ?? '') == 'Term 3' ? 'selected' : ''; ?>>Term 3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Term Start Date</label>
                    <input type="date" name="term_start_date" value="<?php echo $settings['term_start_date'] ?? ''; ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Term End Date</label>
                    <input type="date" name="term_end_date" value="<?php echo $settings['term_end_date'] ?? ''; ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency" class="form-control">
                        <option value="TZS" <?php echo ($settings['currency'] ?? '') == 'TZS' ? 'selected' : ''; ?>>Tanzania Shilling (TZS)</option>
                        <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>School Address</label>
                <textarea name="school_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></textarea>
            </div>
            
            <!-- NEW SECTION: SCHOOL TYPE SETTINGS (Primary/Secondary) -->
            <div class="form-card" style="margin-top: 1.5rem; background: #f8f9fa;">
                <h3>🏫 School Level Settings</h3>
                <div class="two-columns">
                    <div class="form-group">
                        <label>School Type</label>
                        <select name="school_type" class="form-control" id="school_type">
                            <option value="primary" <?php echo ($settings['school_type'] ?? 'both') == 'primary' ? 'selected' : ''; ?>>Primary School Only</option>
                            <option value="secondary" <?php echo ($settings['school_type'] ?? 'both') == 'secondary' ? 'selected' : ''; ?>>Secondary School Only</option>
                            <option value="both" <?php echo ($settings['school_type'] ?? 'both') == 'both' ? 'selected' : ''; ?>>Both (Primary & Secondary)</option>
                        </select>
                        <small>Select if your school has primary, secondary, or both levels</small>
                    </div>
                    <div class="form-group" id="grading_system_group">
                        <label>Default Grading System</label>
                        <select name="default_grading_system" class="form-control">
                            <option value="primary" <?php echo ($settings['default_grading_system'] ?? 'primary') == 'primary' ? 'selected' : ''; ?>>Primary (A, B, C, D, E - Average)</option>
                            <option value="secondary" <?php echo ($settings['default_grading_system'] ?? 'primary') == 'secondary' ? 'selected' : ''; ?>>Secondary (Points A=1 to F=6 + Divisions)</option>
                        </select>
                        <small>This determines how results are calculated</small>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enable_period_attendance" value="1" <?php echo ($settings['enable_period_attendance'] ?? 0) ? 'checked' : ''; ?>>
                            Enable Period-wise Attendance (Secondary)
                        </label>
                        <small>For secondary schools: track attendance per period/subject</small>
                    </div>
                </div>
                
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Note:</strong> Changes to school type will affect:
                    <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                        <li>Grading system (Average vs Points)</li>
                        <li>Attendance tracking (Daily vs Period-wise)</li>
                        <li>Student labels (Pupil vs Student)</li>
                        <li>Subject mapping and curriculum</li>
                    </ul>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Save All Settings</button>
        </form>
    </div>
</div>

<script>
// Show/hide grading system based on school type
document.getElementById('school_type').addEventListener('change', function() {
    var gradingGroup = document.getElementById('grading_system_group');
    if (this.value === 'both') {
        gradingGroup.style.display = 'block';
    } else if (this.value === 'primary') {
        gradingGroup.style.display = 'block';
        document.querySelector('select[name="default_grading_system"]').value = 'primary';
    } else if (this.value === 'secondary') {
        gradingGroup.style.display = 'block';
        document.querySelector('select[name="default_grading_system"]').value = 'secondary';
    }
});
</script>

<style>
.two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 768px) {
    .two-columns {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/admin_footer.php'; ?>