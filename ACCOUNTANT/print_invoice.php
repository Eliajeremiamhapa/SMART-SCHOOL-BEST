<?php
require_once 'config/database.php';

$invoice_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT i.*, s.full_name, s.student_number, s.class, rc.category_name
    FROM invoices i
    JOIN students s ON i.student_id = s.id
    JOIN revenue_categories rc ON i.category_id = rc.id
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die('Invoice not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo $invoice['invoice_number']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 2rem;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .school-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .invoice-title {
            font-size: 1.2rem;
            margin-top: 0.5rem;
        }
        .details {
            margin: 1rem 0;
        }
        .details table {
            width: 100%;
        }
        .amounts {
            margin-top: 1rem;
            border-top: 1px solid #ddd;
            padding-top: 1rem;
        }
        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #666;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        button {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="school-name">SSMS TANZANIA</div>
            <div class="invoice-title">TAX INVOICE / RECEIPT</div>
        </div>
        
        <div class="details">
            <table>
                <tr><td width="50%"><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?></td>
                    <td><strong>Date:</strong> <?php echo $invoice['issue_date']; ?></td></tr>
                <tr><td><strong>Student Name:</strong> <?php echo $invoice['full_name']; ?></td>
                    <td><strong>Student #:</strong> <?php echo $invoice['student_number']; ?></td></tr>
                <tr><td><strong>Class:</strong> <?php echo $invoice['class']; ?></td>
                    <td><strong>Due Date:</strong> <?php echo $invoice['due_date']; ?></td></tr>
                <tr><td><strong>Category:</strong> <?php echo $invoice['category_name']; ?></td>
                    <td><strong>Term:</strong> <?php echo $invoice['term']; ?> - <?php echo $invoice['academic_year']; ?></td></tr>
            </table>
        </div>
        
        <div class="amounts">
            <table width="100%">
                <tr><td><strong>Total Amount:</strong></td><td align="right">TZS <?php echo number_format($invoice['amount']); ?></td></tr>
                <tr><td><strong>Amount Paid:</strong></td><td align="right">TZS <?php echo number_format($invoice['amount_paid']); ?></td></tr>
                <tr style="border-top:1px solid #000;"><td><strong>Balance Due:</strong></td><td align="right"><strong>TZS <?php echo number_format($invoice['balance']); ?></strong></td></tr>
            </table>
        </div>
        
        <div class="footer">
            <p>Thank you for your payment! This is a computer generated receipt.</p>
            <p>SSMS Tanzania - Smart School Management System</p>
        </div>
    </div>
    
    <div class="no-print" style="text-align:center;">
        <button onclick="window.print()">🖨️ Print Invoice</button>
        <button onclick="window.close()">Close</button>
    </div>
    
    <script>
        window.onload = function() {
            // Auto-print if needed
            // window.print();
        }
    </script>
</body>
</html>