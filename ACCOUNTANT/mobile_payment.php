<?php
require_once 'config/database.php';
$page_title = "Lipia Kwa M-Pesa";
include 'includes/header.php';

$students = $pdo->query("SELECT id, student_number, full_name, class, parent_phone FROM students WHERE is_active = 1")->fetchAll();
?>

<div class="container">
    <h1>📱 Lipia Kwa M-Pesa</h1>
    
    <div class="form-card">
        <div class="form-group">
            <label>Chagua Mwanafunzi</label>
            <select id="student_id" class="form-control">
                <option value="">-- Chagua --</option>
                <?php foreach ($students as $s): ?>
                <option value="<?php echo $s['id']; ?>" data-phone="<?php echo $s['parent_phone']; ?>">
                    <?php echo $s['full_name'] . ' (' . $s['class'] . ')'; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Kiasi (TZS)</label>
            <input type="number" id="amount" class="form-control" placeholder="Weka kiasi">
        </div>
        
        <div class="form-group">
            <label>Namba ya Simu ya Mzazi</label>
            <input type="tel" id="phone" class="form-control" placeholder="e.g., 0712345678">
        </div>
        
        <button onclick="payWithMpesa()" class="btn btn-success" style="background:#25D366; width:100%; padding:1rem;">💳 Lipa Kwa M-Pesa</button>
    </div>
    
    <div id="paymentStatus"></div>
</div>

<script>
document.getElementById('student_id').addEventListener('change', function() {
    var phone = this.options[this.selectedIndex].getAttribute('data-phone');
    document.getElementById('phone').value = phone;
});

function payWithMpesa() {
    var studentId = document.getElementById('student_id').value;
    var amount = document.getElementById('amount').value;
    var phone = document.getElementById('phone').value;
    
    if (!studentId || !amount || !phone) {
        alert('Tafadhali jaza taarifa zote');
        return;
    }
    
    // Convert phone to international format for test
    if (phone.length == 10 && phone.startsWith('07')) {
        phone = '254' + phone.substring(1);
    } else if (phone.length == 12 && phone.startsWith('254')) {
        // Already in correct format
    } else {
        // For sandbox testing, use test number
        if (confirm('Namba hii inaweza isifanye kazi kwa Sandbox. Tumia namba ya test?')) {
            phone = '254708374149';
        }
    }
    
    document.getElementById('paymentStatus').innerHTML = '<div class="alert alert-info">⏳ Inatuma ombi kwa namba: ' + phone + '...</div>';
    
    fetch('api/mpesa_stkpush.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: studentId, amount: amount, phone: phone })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('paymentStatus').innerHTML = `
                <div class="alert alert-success">
                    ✅ ${data.message}<br>
                    Checkout Request ID: ${data.checkout_request_id}<br>
                    Angalia simu yako na ingiza PIN (kwa test: 123456)
                </div>
            `;
        } else {
            document.getElementById('paymentStatus').innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
        }
    })
    .catch(error => {
        document.getElementById('paymentStatus').innerHTML = `<div class="alert alert-danger">❌ Error: ${error.message}</div>`;
    });
}
</script>

<style>
.form-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.form-group {
    margin-bottom: 1rem;
}
.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}
.btn-success {
    background: #25D366;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
}
.btn-success:hover {
    background: #128C7E;
}
</style>

<?php include 'includes/footer.php'; ?>