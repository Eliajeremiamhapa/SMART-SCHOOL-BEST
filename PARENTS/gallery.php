<?php
// PARENTS/gallery.php
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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') { 
    header('Location: ../ACCOUNTANT/login_fixed.php'); 
    exit(); 
}

// Get only public images
$stmt = $pdo->query("SELECT * FROM gallery WHERE is_public = 1 ORDER BY created_at DESC");
$images = $stmt->fetchAll();

$page_title = "School Gallery";
include 'includes/parent_header.php';
?>

<div class="container">
    <h1>🖼️ School Gallery</h1>
    
    <?php if (empty($images)): ?>
        <div class="form-card" style="text-align:center;">
            <i class="fas fa-images" style="font-size:4rem; color:#ccc;"></i>
            <p style="margin-top:1rem;">No gallery images available yet.</p>
            <p>Check back later for school event photos.</p>
        </div>
    <?php else: ?>
        <div class="stats-grid" style="margin-bottom:1rem;">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($images); ?></div>
                <div class="stat-label">Total Images</div>
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:1.5rem;">
            <?php foreach ($images as $img): ?>
            <div class="form-card" style="padding:0; overflow:hidden; transition:transform 0.2s;">
                <div style="position:relative;">
                    <!-- FIXED PATH: ../uploads/gallery/ (nyuma moja, kisha uploads) -->
                    <img src="../uploads/gallery/<?php echo $img['filename']; ?>" 
                         style="width:100%; height:220px; object-fit:cover;" 
                         alt="<?php echo htmlspecialchars($img['title']); ?>"
                         onerror="this.src='../ACCOUNTANT/images/no-image.png'">
                </div>
                <div style="padding:1rem;">
                    <h4 style="margin-bottom:0.5rem;"><?php echo htmlspecialchars($img['title']); ?></h4>
                    <p style="color:#666; font-size:0.85rem; margin-bottom:0.5rem;"><?php echo htmlspecialchars($img['description']); ?></p>
                    <small style="color:#999;">📅 <?php echo date('d-m-Y', strtotime($img['created_at'])); ?></small>
                    <div style="margin-top:0.75rem;">
                        <a href="../uploads/gallery/<?php echo $img['filename']; ?>" class="btn-sm" download style="background:#28a745;">📥 Download</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
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
    .form-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .form-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    .btn-sm {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        font-size: 0.75rem;
        border-radius: 5px;
        text-decoration: none;
        color: white;
        background: #28a745;
    }
    .btn-sm:hover {
        background: #218838;
    }
</style>

<?php include 'includes/parent_footer.php'; ?>