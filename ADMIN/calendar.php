<?php
// ADMIN/calendar.php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login.php');
    exit();
}

$page_title = "School Calendar";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Handle Add Event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $event_type = $_POST['event_type'];
    
    $stmt = $pdo->prepare("INSERT INTO school_calendar (title, description, event_date, event_type, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $event_date, $event_type, $_SESSION['user_id']]);
    $success = "✅ Event added successfully!";
}

// Handle Delete Event
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM school_calendar WHERE id = ?");
    $stmt->execute([$id]);
    $success = "✅ Event deleted successfully!";
}

// Get events
$events = $pdo->query("SELECT * FROM school_calendar ORDER BY event_date ASC")->fetchAll();
?>

<div class="container">
    <h1>📅 School Calendar</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Add Event Form -->
        <div class="form-card">
            <h3>➕ Add New Event</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Event Date</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>Event Type</label>
                    <select name="event_type">
                        <option value="event">General Event</option>
                        <option value="holiday">Holiday</option>
                        <option value="exam">Examination</option>
                        <option value="meeting">Meeting</option>
                    </select>
                </div>
                <button type="submit" name="add_event" class="btn btn-primary">➕ Add Event</button>
            </form>
        </div>
        
        <!-- Events List -->
        <div class="form-card">
            <h3>📋 Upcoming Events</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Title</th><th>Type</th><th>Description</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                        <tr style="<?php echo $e['event_date'] < date('Y-m-d') ? 'opacity:0.6;' : ''; ?>">
                            <td><?php echo date('d-m-Y', strtotime($e['event_date'])); ?></small></td>
                            <td><strong><?php echo $e['title']; ?></strong></small></td>
                            <td><?php echo ucfirst($e['event_type']); ?></small></td>
                            <td><?php echo substr($e['description'], 0, 50); ?></small></td>
                            <td>
                                <a href="?delete_id=<?php echo $e['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this event?')">🗑️</a>
                            </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>