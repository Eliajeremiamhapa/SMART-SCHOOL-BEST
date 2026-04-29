<?php
// ADMIN/gallery.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../ACCOUNTANT/login_fixed.php');
    exit();
}

$page_title = "Gallery Management";
include 'includes/admin_header.php';

$error = '';
$success = '';

// Create uploads directory if not exists (in root folder)
$upload_dir = '../uploads/gallery/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $file = $_FILES['image'];
    
    if (empty($title)) {
        $error = "❌ Image title is required!";
    } elseif ($file['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            // Direct upload without compression (GD Library not required)
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO gallery (title, description, filename, original_filename, file_size, mime_type, is_public, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $filename, $file['name'], $file['size'], $file['type'], $is_public, $_SESSION['user_id']]);
                $success = "✅ Image uploaded successfully!";
            } else {
                $error = "❌ Failed to upload image.";
            }
        } else {
            $error = "❌ Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
        }
    } else {
        $error = "❌ File upload error.";
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT filename FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    
    if ($image) {
        $filepath = $upload_dir . $image['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $success = "✅ Image deleted successfully!";
    }
}

// Toggle visibility (make public/private)
if (isset($_GET['toggle_id'])) {
    $id = $_GET['toggle_id'];
    $stmt = $pdo->prepare("UPDATE gallery SET is_public = NOT is_public WHERE id = ?");
    $stmt->execute([$id]);
    $success = "✅ Visibility updated!";
}

// Get all images
$images = $pdo->query("SELECT g.*, u.full_name FROM gallery g LEFT JOIN users u ON g.uploaded_by = u.id ORDER BY g.created_at DESC")->fetchAll();
?>

<div class="container">
    <h1>🖼️ Gallery Management</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="two-columns">
        <!-- Upload Form -->
        <div class="form-card">
            <h3>📤 Upload New Image</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Image Title *</label>
                    <input type="text" name="title" required placeholder="e.g., Graduation Day 2025">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe this image..."></textarea>
                </div>
                <div class="form-group">
                    <label>Select Image *</label>
                    <input type="file" name="image" accept="image/*" required>
                    <small>Allowed: JPG, PNG, GIF, WEBP</small>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_public" value="1" checked>
                        <i class="fas fa-globe"></i> Make Public (Visible to Parents)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">📤 Upload Image</button>
            </form>
        </div>
        
        <!-- Gallery Statistics -->
        <div class="form-card">
            <h3>📊 Gallery Statistics</h3>
            <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($images); ?></div>
                    <div class="stat-label">Total Images</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $public_count = 0;
                        foreach ($images as $img) {
                            if ($img['is_public']) $public_count++;
                        }
                        echo $public_count;
                        ?>
                    </div>
                    <div class="stat-label">Public Images</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gallery List -->
    <div class="form-card">
        <h3>📷 All Images (<?php echo count($images); ?>)</h3>
        <?php if (empty($images)): ?>
            <p style="text-align:center; padding:2rem;">No images uploaded yet. Use the form above to add images.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                            <th>Visibility</th>
                            <th>Actions</th>
                    　
                    </thead>
                    <tbody>
                        <?php foreach ($images as $img): ?>
                        <tr>
                            <td>
                                <img src="../uploads/gallery/<?php echo $img['filename']; ?>" 
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                             </small></td>
                            <td><strong><?php echo htmlspecialchars($img['title']); ?></strong></small></td>
                            <td><?php echo htmlspecialchars(substr($img['description'], 0, 50)); ?>...</small></td>
                            <td><?php echo htmlspecialchars($img['full_name'] ?? 'Unknown'); ?></small></td>
                            <td><?php echo date('d-m-Y', strtotime($img['created_at'])); ?></small></td>
                            <td>
                                <?php if ($img['is_public']): ?>
                                    <span style="color:green;">✅ Public</span>
                                <?php else: ?>
                                    <span style="color:orange;">🔒 Private</span>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <a href="?toggle_id=<?php echo $img['id']; ?>" class="btn-sm" style="background:#17a2b8;">
                                    <?php echo $img['is_public'] ? '🔒 Make Private' : '🌍 Make Public'; ?>
                                </a>
                                <a href="?delete_id=<?php echo $img['id']; ?>" class="btn-sm" style="background:#dc3545;" onclick="return confirm('Delete this image permanently?')">🗑️ Delete</a>
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    .stat-card {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #1e3c72;
    }
    .stat-card .stat-label {
        font-size: 0.8rem;
        color: #666;
    }
    .btn-sm {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        border-radius: 4px;
        text-decoration: none;
        color: white;
        margin: 0.1rem;
    }
</style>

<?php include 'includes/admin_footer.php'; ?>