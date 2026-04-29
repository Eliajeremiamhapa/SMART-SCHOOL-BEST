<?php
require_once 'config/database.php';
$page_title = "Inventory Management";
include 'includes/header.php';

$error = '';
$success = '';

// Add new stock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    $item_code = trim($_POST['item_code']);
    $item_name = trim($_POST['item_name']);
    $category_id = $_POST['category_id'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $reorder_level = $_POST['reorder_level'];
    
    // Validation
    if (empty($item_code) || empty($item_name) || empty($category_id) || empty($quantity) || empty($unit_price)) {
        $error = "❌ Tafadhali jaza sehemu zote!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory_items (item_code, item_name, category_id, current_stock, unit_price, reorder_level) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$item_code, $item_name, $category_id, $quantity, $unit_price, $reorder_level]);
            $success = "✅ Bidhaa '{$item_name}' imeongezwa kikamilifu!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "❌ Namba ya bidhaa '{$item_code}' tayari ipo kwenye mfumo! Tafadhali tumia namba tofauti.";
            } else {
                $error = "❌ Kuna tatizo la kiufundi. Tafadhali wasiliana na msimamizi.";
            }
        }
    }
}

// Update stock (manual adjustment)
if (isset($_POST['update_stock'])) {
    $item_id = $_POST['item_id'];
    $new_quantity = $_POST['new_quantity'];
    
    $stmt = $pdo->prepare("UPDATE inventory_items SET current_stock = ? WHERE id = ?");
    $stmt->execute([$new_quantity, $item_id]);
    $success = "✅ Stock imebadilishwa kikamilifu!";
}

// Delete item
if (isset($_GET['delete'])) {
    $item_id = $_GET['delete'];
    
    // Check if item has any stock transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_transactions WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $has_transactions = $stmt->fetchColumn() > 0;
    
    if ($has_transactions) {
        // Just deactivate instead of delete
        $stmt = $pdo->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
        $stmt->execute([$item_id]);
        $success = "✅ Bidhaa imezimwa (haionekani kwenye orodha). Kwa sababu imewahi kuuzwa, tunaweka kwenye kumbukumbu tu.";
    } else {
        // Delete completely if no transactions
        $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $success = "✅ Bidhaa imefutwa kabisa kwenye mfumo!";
    }
}

// Get all inventory items (only active ones)
$items = $pdo->query("
    SELECT i.*, rc.category_name 
    FROM inventory_items i 
    JOIN revenue_categories rc ON i.category_id = rc.id 
    WHERE i.is_active = 1
    ORDER BY i.current_stock ASC
")->fetchAll();

// Get low stock alerts
$low_stock = $pdo->query("
    SELECT lsa.*, i.item_name, i.current_stock, i.reorder_level 
    FROM low_stock_alerts lsa 
    JOIN inventory_items i ON lsa.item_id = i.id 
    WHERE lsa.status = 'pending'
")->fetchAll();

// Get categories
$categories = $pdo->query("SELECT id, category_name FROM revenue_categories WHERE is_active = 1")->fetchAll();
?>

<div class="container">
    <h1>📦 Inventory & Stock Management</h1>
    
    <!-- Error Message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Success Message -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Low Stock Alerts -->
    <?php if (count($low_stock) > 0): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Tahadhari: Bidhaa zinaisha!</strong>
        <ul>
            <?php foreach ($low_stock as $ls): ?>
                <li><?php echo htmlspecialchars($ls['item_name']); ?>: <?php echo $ls['current_stock']; ?> imebaki (Kiwango cha kuagiza ni <?php echo $ls['reorder_level']; ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="two-columns">
        <div class="form-card">
            <h3>➕ Ongeza Bidhaa Mpya</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Namba ya Bidhaa (Item Code) *</label>
                    <input type="text" name="item_code" required placeholder="Mfano: NB001, UNI001, FD001">
                    <small style="color:#666;">Namba hii lazima iwe ya kipekee (haitumiki kwa bidhaa nyingine)</small>
                </div>
                <div class="form-group">
                    <label>Jina la Bidhaa *</label>
                    <input type="text" name="item_name" required placeholder="Mfano: Exercise Book, School Uniform, Lunch Meal">
                </div>
                <div class="form-group">
                    <label>Aina ya Mapato (Category) *</label>
                    <select name="category_id" required>
                        <option value="">-- Chagua Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Idadi ya Awali (Quantity) *</label>
                    <input type="number" name="quantity" required placeholder="Mfano: 100">
                </div>
                <div class="form-group">
                    <label>Bei ya Kujulisha (TZS) *</label>
                    <input type="number" step="0.01" name="unit_price" required placeholder="Mfano: 2500">
                </div>
                <div class="form-group">
                    <label>Kiwango cha Kuagiza (Reorder Level)</label>
                    <input type="number" name="reorder_level" value="10" placeholder="Mfano: 10">
                    <small>Bidhaa ikifikia idadi hii, mfumo utaonyesha tahadhari</small>
                </div>
                <button type="submit" name="add_stock" class="btn btn-primary">➕ Ongeza Bidhaa</button>
            </form>
        </div>
        
        <div class="form-card">
            <h3>🔍 Ukaguzi wa Stock</h3>
            <p>Hapa unaweza kubadilisha idadi ya bidhaa kwa mkono</p>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Bidhaa</th><th>Idadi ya Sasa</th><th>Kitendo</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?> (<?php echo $item['item_code']; ?>)</td>
                            <td><?php echo $item['current_stock']; ?> <?php echo $item['unit_of_measure']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <input type="number" name="new_quantity" value="<?php echo $item['current_stock']; ?>" style="width:80px;" required>
                                    <button type="submit" name="update_stock" class="btn-small">Sahihisha</button>
                                </form>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h3>📋 Orodha ya Bidhaa Zote</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Namba</th>
                        <th>Jina la Bidhaa</th>
                        <th>Aina</th>
                        <th>Idadi</th>
                        <th>Bei (TZS)</th>
                        <th>Kiwango cha Kuagiza</th>
                        <th>Hali</th>
                        <th>Kitendo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">📌 Hakuna bidhaa bado. Bonyeza "Ongeza Bidhaa Mpya" kuanza.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr <?php echo $item['current_stock'] <= $item['reorder_level'] ? 'style="background-color:#fff3cd;"' : ''; ?>>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo $item['current_stock']; ?> <?php echo $item['unit_of_measure']; ?></td>
                            <td>TZS <?php echo number_format($item['unit_price']); ?></td>
                            <td><?php echo $item['reorder_level']; ?></td>
                            <td>
                                <?php if ($item['current_stock'] <= $item['reorder_level']): ?>
                                    <span style="color:red;">🔴 Inaisha</span>
                                <?php else: ?>
                                    <span style="color:green;">✅ Imetosha</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <a href="?delete=<?php echo $item['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('Una hakika unataka kufuta bidhaa hii?')">🗑️ Futa</a>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
</style>

<?php include 'includes/footer.php'; ?>