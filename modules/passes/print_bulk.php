<?php
require_once '../../config/config.php';
requireLogin();

if (!isset($_GET['codes'])) {
    die("Pass codes required");
}

$codes = explode(',', $_GET['codes']);
$placeholders = implode(',', array_fill(0, count($codes), '?'));

$stmt = $pdo->prepare("SELECT p.*, u.full_name as salesperson FROM passes p JOIN users u ON p.booked_by = u.id WHERE p.pass_code IN ($placeholders)");
$stmt->execute($codes);
$passes = $stmt->fetchAll();

if (!$passes) {
    die("Passes not found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Bulk Passes</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #fff; margin: 0; padding: 0; color: #000; }
        .ticket-container {
            width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 10px;
            box-sizing: border-box;
            border-bottom: 2px dashed #007bff;
            page-break-after: always;
        }
        .header { background: #1e1e1e; color: #00d4ff; padding: 10px; text-align: center; }
        .header h2 { margin: 0; font-size: 1.2rem; }
        .type-banner { background: #ffc107; color: #000; text-align: center; padding: 5px; font-weight: bold; font-size: 0.9rem; margin-top: 5px; }
        .content { padding: 15px; text-align: center; }
        .code-box { border: 1px dashed #007bff; padding: 10px; margin: 10px 0; border-radius: 8px; }
        .code-box .code { font-size: 1.6rem; font-weight: 900; color: #007bff; letter-spacing: 2px; }
        .details { font-size: 0.8rem; margin: 10px 0; }
        .price-tag { font-size: 1.2rem; font-weight: 800; color: #28a745; }
        .footer { padding: 10px; text-align: center; font-size: 0.7rem; color: #777; border-top: 1px solid #eee; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="padding: 20px; text-align: center; background: #f8f9fa;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print All Passes</button>
        <p>Total Passes: <?php echo count($passes); ?></p>
    </div>

    <?php foreach ($passes as $pass): ?>
    <div class="ticket-container">
        <div class="header">
            <h2>FUNFAIR ERP</h2>
        </div>
        
        <div class="type-banner">SPECIAL ACCESS PASS</div>
        
        <div class="content">
            <div class="code-box">
                <div class="qrcode-placeholder" data-code="<?php echo $pass['pass_code']; ?>" style="display: flex; justify-content: center; margin-bottom: 5px;"></div>
                <small style="font-size: 0.6rem; color: #666;">Registration Code</small>
                <div class="code"><?php echo $pass['pass_code']; ?></div>
            </div>
            
            <div class="details">
                Validity: <strong>3 Hours</strong> (from activation)<br>
                Limit: <strong>3 rides per swing</strong>
            </div>
            
            <div class="price-tag">Rs. <?php echo number_format($pass['price'], 2); ?></div>
        </div>
        
        <div class="footer">
            Agent: <?php echo htmlspecialchars($pass['salesperson']); ?><br>
            Date: <?php echo date('Y-m-d H:i'); ?>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="<?php echo BASE_URL; ?>assets/js/qrcode.min.js"></script>
    <script>
        document.querySelectorAll(".qrcode-placeholder").forEach(div => {
            new QRCode(div, {
                text: div.getAttribute('data-code'),
                width: 100,
                height: 100,
                colorDark : "#007bff"
            });
        });
    </script>
</body>
</html>
