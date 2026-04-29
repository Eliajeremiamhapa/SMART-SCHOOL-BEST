<?php
require_once 'config/database.php';
$page_title = "Import Bank Statement";
include 'includes/header.php';

$error = '';
$success = '';

// Create uploads directory if not exists
if (!is_dir('uploads/bank_statements')) {
    mkdir('uploads/bank_statements', 0777, true);
}

// Handle Delete Statement (Delete entire statement and its transactions)
if (isset($_GET['delete_statement'])) {
    $statement_id = $_GET['delete_statement'];
    
    try {
        $pdo->beginTransaction();
        
        // Get statement details
        $stmt = $pdo->prepare("SELECT * FROM bank_statements WHERE id = ?");
        $stmt->execute([$statement_id]);
        $statement = $stmt->fetch();
        
        if ($statement) {
            // Check if any transactions are already matched/reconciled
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bank_transactions WHERE bank_statement_id = ? AND match_status = 'matched'");
            $stmt->execute([$statement_id]);
            $matched_count = $stmt->fetchColumn();
            
            if ($matched_count > 0) {
                $error = "❌ Cannot delete this statement because $matched_count transaction(s) have already been reconciled. Please undo reconciliation first from Bank Reconciliation page.";
            } else {
                // Delete all bank transactions for this statement
                $stmt = $pdo->prepare("DELETE FROM bank_transactions WHERE bank_statement_id = ?");
                $stmt->execute([$statement_id]);
                
                // Delete the physical file
                $file_path = 'uploads/bank_statements/' . $statement['filename'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete statement record
                $stmt = $pdo->prepare("DELETE FROM bank_statements WHERE id = ?");
                $stmt->execute([$statement_id]);
                
                $pdo->commit();
                $success = "✅ Bank statement and all its transactions deleted successfully! You can now upload the correct file.";
            }
        } else {
            $error = "Statement not found!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete Single Transaction
if (isset($_GET['delete_transaction'])) {
    $transaction_id = $_GET['delete_transaction'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM bank_transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();
        
        if ($transaction && $transaction['match_status'] != 'matched') {
            $stmt = $pdo->prepare("DELETE FROM bank_transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            $success = "✅ Transaction deleted successfully!";
        } else {
            $error = "❌ Cannot delete a transaction that is already matched/reconciled!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Import
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['statement_file'])) {
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $file = $_FILES['statement_file'];
    
    // Validate file
    if ($file['error'] != 0) {
        $error = "❌ File upload error. Please try again.";
    } else {
        // Check file extension
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['csv', 'xlsx', 'xls', 'txt'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "❌ Invalid file format. Please upload CSV or Excel file only.";
        } else {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $upload_path = 'uploads/bank_statements/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Insert bank statement record
                $stmt = $pdo->prepare("INSERT INTO bank_statements (filename, bank_name, account_number, import_date, status) VALUES (?, ?, ?, NOW(), 'imported')");
                $stmt->execute([$filename, $bank_name, $account_number]);
                $statement_id = $pdo->lastInsertId();
                
                // Parse CSV file
                $row_count = 0;
                $error_rows = [];
                
                if (($handle = fopen($upload_path, "r")) !== FALSE) {
                    $row = 1;
                    $headers = fgetcsv($handle, 5000, ",");
                    
                    // Check if headers are valid
                    if (!$headers) {
                        $error = "❌ File is empty or invalid format.";
                    } else {
                        while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                            // Skip empty rows
                            if (empty($data[0]) && empty($data[1]) && empty($data[2])) {
                                $row++;
                                continue;
                            }
                            
                            // Ensure we have at least 4 columns
                            if (count($data) < 4) {
                                $error_rows[] = $row;
                                $row++;
                                continue;
                            }
                            
                            $trans_ref = trim($data[0]);
                            $trans_date = !empty($data[1]) ? date('Y-m-d', strtotime($data[1])) : date('Y-m-d');
                            $description = trim($data[2]);
                            $amount = floatval(str_replace(',', '', trim($data[3])));
                            
                            if (empty($trans_ref) && $amount == 0) {
                                $row++;
                                continue;
                            }
                            
                            $trans_type = $amount > 0 ? 'credit' : 'debit';
                            $abs_amount = abs($amount);
                            
                            try {
                                $stmt = $pdo->prepare("INSERT INTO bank_transactions (bank_statement_id, transaction_ref, transaction_date, description, amount, transaction_type, match_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                                $stmt->execute([$statement_id, $trans_ref, $trans_date, $description, $abs_amount, $trans_type]);
                                $row_count++;
                            } catch (PDOException $e) {
                                $error_rows[] = $row;
                            }
                            $row++;
                        }
                        fclose($handle);
                    }
                } else {
                    $error = "❌ Could not open the file.";
                }
                
                if ($row_count > 0) {
                    $success = "✅ Bank statement imported successfully! $row_count transactions loaded.";
                    if (count($error_rows) > 0) {
                        $success .= " (Skipped " . count($error_rows) . " invalid rows)";
                    }
                } else {
                    $error = "❌ No valid transactions found in the file. Please check the format.";
                }
            } else {
                $error = "❌ Failed to upload file. Please check folder permissions.";
            }
        }
    }
}

// Get all imports with transaction counts
$imports = $pdo->query("
    SELECT bs.*, 
           COUNT(bt.id) as transaction_count,
           SUM(CASE WHEN bt.match_status = 'matched' THEN 1 ELSE 0 END) as matched_count
    FROM bank_statements bs
    LEFT JOIN bank_transactions bt ON bs.id = bt.bank_statement_id
    GROUP BY bs.id
    ORDER BY bs.import_date DESC
")->fetchAll();
?>

<div class="container">
    <h1>📥 Import Bank Statement</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-card">
        <h3>Upload Bank Statement</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="two-columns">
                <div class="form-group">
                    <label>🏦 Bank Name</label>
                    <select name="bank_name" required>
                        <option value="">-- Select Bank --</option>
                        <option value="CRDB Bank">CRDB Bank</option>
                        <option value="NMB Bank">NMB Bank</option>
                        <option value="ABS Bank">ABS Bank</option>
                        <option value="KCB Bank">KCB Bank</option>
                        <option value="Exim Bank">Exim Bank</option>
                        <option value="Stanbic Bank">Stanbic Bank</option>
                        <option value="Barclays Bank">Barclays Bank</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>🔢 Account Number</label>
                    <input type="text" name="account_number" required placeholder="Enter bank account number">
                </div>
            </div>
            
            <div class="form-group">
                <label>📄 Statement File (CSV/Excel)</label>
                <input type="file" name="statement_file" accept=".csv, .xlsx, text/csv, application/csv, application/vnd.ms-excel" required>
                <small style="display: block; margin-top: 5px;">
                    <strong>📌 Required CSV format:</strong> 
                    <code>transaction_ref,date,description,amount</code>
                    <br>
                    <strong>Example:</strong> <code>TXN_001,2025-04-01,Payment from John,150000</code>
                    <br>
                    <strong>📱 Mobile users:</strong> Tap "Browse" then select "Files" or "Documents" (not Camera/Photos)
                </small>
            </div>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i> 
                <strong>Sample CSV file content:</strong>
                <pre style="background:#f5f5f5; padding:0.5rem; margin-top:0.5rem; border-radius:5px; font-size:0.75rem;">
transaction_ref,date,description,amount
TXN_001,2025-04-01,Payment from Juma Hassan,150000
TXN_002,2025-04-02,Payment from Asha Mushi,75000
MISM_001,2025-04-03,Unknown payment,50000</pre>
            </div>
            
            <button type="submit" class="btn btn-primary">📤 Import Statement</button>
        </form>
    </div>
    
    <div class="recent-imports">
        <h3>📋 Imported Statements</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bank</th>
                        <th>Account</th>
                        <th>File</th>
                        <th>Transactions</th>
                        <th>Matched</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($imports) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">No imports yet. Upload your first bank statement above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($imports as $imp): 
                            $can_delete = ($imp['matched_count'] == 0);
                        ?>
                        <tr>
                            <td><?php echo date('d-m-Y H:i', strtotime($imp['import_date'])); ?></small></td>
                            <td><?php echo htmlspecialchars($imp['bank_name']); ?></small></td>
                            <td><?php echo htmlspecialchars($imp['account_number']); ?></small></td>
                            <td><?php echo htmlspecialchars($imp['filename']); ?></small></td>
                            <td><?php echo $imp['transaction_count']; ?> transactions</small></td>
                            <td>
                                <?php if ($imp['matched_count'] > 0): ?>
                                    <span style="color:green;">✅ <?php echo $imp['matched_count']; ?> matched</span>
                                <?php else: ?>
                                    <span style="color:orange;">⏳ 0 matched</span>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <?php if ($imp['status'] == 'imported'): ?>
                                    <span style="color:green;">✅ Imported</span>
                                <?php else: ?>
                                    <span style="color:orange;">⏳ Processing</span>
                                <?php endif; ?>
                             </small></td>
                            <td>
                                <?php if ($can_delete): ?>
                                    <a href="?delete_statement=<?php echo $imp['id']; ?>" class="btn-small" style="background:#dc3545;" onclick="return confirm('⚠️ Delete this entire statement?\n\nAll <?php echo $imp['transaction_count']; ?> transactions will be deleted.\n\nProceed?')">🗑️ Delete Statement</a>
                                <?php else: ?>
                                    <span style="color:#999; font-size:0.7rem;">🔒 Has matched transactions</span>
                                    <br>
                                    <small><a href="bank_reconciliation.php">Undo matches first</a></small>
                                <?php endif; ?>
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="info-box">
        <h4>📌 How to correct mistakes:</h4>
        <ul>
            <li><strong>If you uploaded wrong file:</strong> Click "Delete Statement" (only if no transactions are matched yet)</li>
            <li><strong>If some transactions are already matched:</strong> Go to <a href="bank_reconciliation.php">Bank Reconciliation</a> → Click "Undo" on matched transactions → Then delete statement</li>
            <li><strong>To delete single transaction:</strong> Go to <a href="bank_reconciliation.php">Bank Reconciliation</a> → Find the transaction → Delete if not matched</li>
            <li><strong>After deleting wrong statement:</strong> Upload the correct CSV file</li>
        </ul>
    </div>
</div>

<style>
.two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.info-box {
    background: #e8f4fd;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1.5rem;
    border-left: 4px solid #2196f3;
}
.info-box ul {
    margin-left: 1.5rem;
    margin-top: 0.5rem;
}
.info-box li {
    margin-bottom: 0.5rem;
}
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
@media (max-width: 768px) {
    .two-columns {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>