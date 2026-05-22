<?php
require_once '../../config/config.php';
requireLogin();

if (hasRole(['user/student'])) {
    die("Unauthorized: Students cannot print tickets");
}

if (!isset($_GET['codes'])) {
    die("Ticket codes required");
}

$codes = explode(',', $_GET['codes']);
$placeholders = implode(',', array_fill(0, count($codes), '?'));

$stmt = $pdo->prepare("SELECT rt.*, s.name as swing_name, s.price, u.username as booked_by 
                       FROM ride_tickets rt 
                       JOIN swings s ON rt.swing_id = s.id 
                       JOIN users u ON rt.booked_by = u.id 
                       WHERE rt.ticket_code IN ($placeholders)");
$stmt->execute($codes);
$tickets = $stmt->fetchAll();

if (!$tickets) {
    die("Ride tickets not found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Bulk Ride Passes</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #fff; margin: 0; padding: 0; color: #000; }
        .ticket-card {
            width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 10px;
            box-sizing: border-box;
            border-bottom: 2px dashed #e83e8c;
            page-break-after: always;
        }
        .header { background: #e83e8c; color: white; padding: 10px; text-align: center; }
        .header h3 { margin: 0; font-size: 1.1rem; }
        .body { padding: 15px; text-align: center; }
        .ride-title { font-size: 1.2rem; font-weight: 700; color: #343a40; }
        .code-box { border: 1px dashed #e83e8c; padding: 10px; margin: 10px 0; border-radius: 8px; }
        .code-box h1 { margin: 0; font-size: 1.6rem; letter-spacing: 1px; }
        .price-info { font-size: 1.1rem; font-weight: 700; color: #28a745; margin: 5px 0; }
        .footer { padding: 10px; font-size: 0.7rem; color: #888; text-align: center; border-top: 1px solid #eee; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="padding: 20px; text-align: center; background: #f8f9fa;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Allpasses</button>
        <p>Total Passes: <?php echo count($tickets); ?></p>
    </div>

    <?php foreach ($tickets as $ticket): ?>
    <div class="ticket-card">
        <div class="header">
            <h3>RIDE PASS</h3>
        </div>
        <div class="body">
            <div class="ride-title"><?php echo $ticket['swing_name']; ?></div>
            <p style="font-size: 0.7rem; color: #666; margin: 0;">FunFair Entertainment Hub</p>

            <div class="code-box">
                <div class="qrcode-placeholder" data-code="<?php echo $ticket['ticket_code']; ?>" style="display: flex; justify-content: center; margin-bottom: 5px;"></div>
                <small style="font-size: 0.6rem; color: #d63384;">TICKET SERIAL</small>
                <h1><?php echo $ticket['ticket_code']; ?></h1>
            </div>

            <div class="price-info">Rs. <?php echo number_format($ticket['price'], 2); ?></div>
        </div>
        <div class="footer">
            Agent: <?php echo $ticket['booked_by']; ?> | <?php echo date('H:i'); ?><br>
            <strong>Non-refundable. One person/One ride.</strong>
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
                colorDark : "#e83e8c"
            });
        });
    </script>
</body>
</html>
