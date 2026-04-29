<?php
require_once 'config/database.php';
$page_title = "Student Management";
include 'includes/header.php';

$error = '';
$success = '';

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $student_number = trim($_POST['student_number']);
    $full_name = trim($_POST['full_name']);
    $class = trim($_POST['class']);
    $parent_phone = trim($_POST['parent_phone']);
    
    // Validation
    if (empty($student_number) || empty($full_name) || empty($class) || empty($parent_phone)) {
        $error = "❌ Tafadhali jaza sehemu zote!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class, parent_phone, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$student_number, $full_name, $class, $parent_phone]);
            $success = "✅ Mwanafunzi " . htmlspecialchars($full_name) . " ameongezwa kikamilifu!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "❌ Namba ya mwanafunzi " . htmlspecialchars($student_number) . " tayari ipo kwenye mfumo! Tafadhali tumia namba tofauti.";
            } else {
                $error = "❌ Kuna tatizo la kiufundi. Tafadhali wasiliana na msimamizi.";
            }
        }
    }
}

// Handle Edit Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $student_id = $_POST['student_id'];
    $student_number = trim($_POST['student_number']);
    $full_name = trim($_POST['full_name']);
    $class = trim($_POST['class']);
    $parent_phone = trim($_POST['parent_phone']);
    
    if (empty($student_number) || empty($full_name) || empty($class) || empty($parent_phone)) {
        $error = "❌ Tafadhali jaza sehemu zote!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE students SET student_number = ?, full_name = ?, class = ?, parent_phone = ? WHERE id = ?");
            $stmt->execute([$student_number, $full_name, $class, $parent_phone, $student_id]);
            $success = "✅ Taarifa za mwanafunzi " . htmlspecialchars($full_name) . " zimebadilishwa kikamilifu!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "❌ Namba ya mwanafunzi " . htmlspecialchars($student_number) . " tayari inatumiwa na mwanafunzi mwingine!";
            } else {
                $error = "❌ Kuna tatizo la kiufundi. Tafadhali wasiliana na msimamizi.";
            }
        }
    }
}

// Handle Deactivate Student
if (isset($_GET['deactivate'])) {
    $student_id = $_GET['deactivate'];
    $stmt = $pdo->prepare("UPDATE students SET is_active = 0 WHERE id = ?");
    $stmt->execute([$student_id]);
    $success = "✅ Mwanafunzi amezimwa (hataonekana kwenye orodha ya wanafunzi wanaolipia ada).";
}

// Handle Activate Student
if (isset($_GET['activate'])) {
    $student_id = $_GET['activate'];
    $stmt = $pdo->prepare("UPDATE students SET is_active = 1 WHERE id = ?");
    $stmt->execute([$student_id]);
    $success = "✅ Mwanafunzi ameanzishwa tena (ataonekana kwenye orodha).";
}

// Get all students
$students = $pdo->query("SELECT * FROM students ORDER BY student_number")->fetchAll();

// Get student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_student = $stmt->fetch();
    if (!$edit_student) {
        $error = "❌ Mwanafunzi hatafutika kwenye mfumo.";
    }
}
?>

<div class="container">
    <h1>👨‍🎓 Usimamizi wa Wanafunzi</h1>
    
    <!-- Error Message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="background:#f8d7da; color:#721c24; padding:1rem; border-radius:5px; margin-bottom:1rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Success Message -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="background:#d4edda; color:#155724; padding:1rem; border-radius:5px; margin-bottom:1rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <!-- Add/Edit Student Form -->
    <div class="form-card">
        <h3><?php echo $edit_student ? '✏️ Badilisha Taarifa za Mwanafunzi' : '➕ Ongeza Mwanafunzi Mpya'; ?></h3>
        
        <?php if ($edit_student): ?>
        <p style="color:#666; margin-bottom:1rem;">Unabadilisha taarifa za: <strong><?php echo htmlspecialchars($edit_student['full_name']); ?></strong></p>
        <?php endif; ?>
        
        <form method="POST">
            <?php if ($edit_student): ?>
                <input type="hidden" name="student_id" value="<?php echo $edit_student['id']; ?>">
            <?php endif; ?>
            
            <div class="two-columns">
                <div class="form-group">
                    <label>Namba ya Mwanafunzi *</label>
                    <input type="text" name="student_number" required 
                           value="<?php echo $edit_student ? htmlspecialchars($edit_student['student_number']) : ''; ?>"
                           placeholder="Mfano: SSMS001 au 2025001">
                    <small style="color:#666;">Namba hii lazima iwe ya kipekee (haitumiki na mwanafunzi mwingine)</small>
                </div>
                <div class="form-group">
                    <label>Jina Kamili *</label>
                    <input type="text" name="full_name" required 
                           value="<?php echo $edit_student ? htmlspecialchars($edit_student['full_name']) : ''; ?>"
                           placeholder="Mfano: Juma Hassan">
                </div>
                <div class="form-group">
                    <label>Darasa *</label>
                    <input type="text" name="class" required 
                           value="<?php echo $edit_student ? htmlspecialchars($edit_student['class']) : ''; ?>"
                           placeholder="Mfano: Form 1A, Standard 5, Darasa la 3">
                </div>
                <div class="form-group">
                    <label>Namba ya Simu ya Mzazi *</label>
                    <input type="tel" name="parent_phone" required 
                           value="<?php echo $edit_student ? htmlspecialchars($edit_student['parent_phone']) : ''; ?>"
                           placeholder="Mfano: 0712345678">
                    <small style="color:#666;">Tumia namba inayotumika kwa ajili ya kupokea taarifa</small>
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="<?php echo $edit_student ? 'edit_student' : 'add_student'; ?>" class="btn btn-primary">
                    <?php echo $edit_student ? '💾 Hifadhi Mabadiliko' : '➕ Ongeza Mwanafunzi'; ?>
                </button>
                <?php if ($edit_student): ?>
                    <a href="students.php" class="btn btn-secondary">❌ Ghairi</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Search Box -->
    <div class="search-box" style="margin-bottom: 1rem;">
        <input type="text" id="searchStudent" placeholder="🔍 Tafuta kwa jina, namba, au darasa..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;">
    </div>
    
    <!-- Students List -->
    <div class="section">
        <h3>📋 Orodha ya Wanafunzi</h3>
        
        <?php if (count($students) == 0): ?>
            <div class="alert alert-info" style="background:#d1ecf1; color:#0c5460; padding:1rem; border-radius:5px;">
                📌 Hakuna mwanafunzi bado. Bonyeza "Ongeza Mwanafunzi Mpya" kuanza.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Namba</th>
                            <th>Jina Kamili</th>
                            <th>Darasa</th>
                            <th>Simu ya Mzazi</th>
                            <th>Hali</th>
                            <th>Kadi</th>
                            <th>Vitendo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): 
                            // Check if student has a smart card
                            $stmt = $pdo->prepare("SELECT card_uid, balance FROM smart_cards WHERE student_id = ? AND is_active = 1");
                            $stmt->execute([$s['id']]);
                            $card = $stmt->fetch();
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['class']); ?></td>
                            <td><?php echo htmlspecialchars($s['parent_phone']); ?></td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                    <span style="color:green;">✅ Anatumika</span>
                                <?php else: ?>
                                    <span style="color:red;">❌ Amezimwa</span>
                                <?php endif; ?>
                              </td>
                            <td>
                                <?php if ($card): ?>
                                    <span style="color:green;">✅ Ipo</span><br>
                                    <small><strong>Card UID:</strong> <?php echo htmlspecialchars($card['card_uid']); ?></small><br>
                                    <small><strong>Salio:</strong> TZS <?php echo number_format($card['balance']); ?></small>
                                <?php else: ?>
                                    <span style="color:orange;">⚠️ Haina Kadi</span><br>
                                    <a href="issue_card.php?student_id=<?php echo $s['id']; ?>" class="btn-small">Toa Kadi</a>
                                <?php endif; ?>
                              </td>
                            <td>
                                <a href="?edit=<?php echo $s['id']; ?>" class="btn-small">✏️ Badilisha</a>
                                <?php if ($s['is_active']): ?>
                                    <a href="?deactivate=<?php echo $s['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('Una hakika unataka kumzuia mwanafunzi huyu? Hataonekana kwenye orodha ya kulipia ada.')">🔴 Zima</a>
                                <?php else: ?>
                                    <a href="?activate=<?php echo $s['id']; ?>" class="btn-small" style="background:#28a745;" onclick="return confirm('Una hakika unataka kumrejesha mwanafunzi huyu?')">🟢 Washa</a>
                                <?php endif; ?>
                                <a href="fee_management.php?student_id=<?php echo $s['id']; ?>" class="btn-small">💰 Lipia</a>
                              </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.6rem 1.2rem;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
}
.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}
.search-box {
    margin: 1rem 0;
}
@media (max-width: 768px) {
    .two-columns {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Search Script -->
<script>
document.getElementById('searchStudent').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#studentsTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>