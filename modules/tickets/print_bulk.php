<?php
require_once '../../config/config.php';
requireLogin();

if (hasRole(['user/student'])) {
    die("Unauthorized: Students cannot print tickets");
}

if (!isset($_GET['ids'])) {
    die("Ticket IDs required");
}

$ids = explode(',', $_GET['ids']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare("SELECT t.*, e.title as event_title, e.event_date, u.username as booked_by 
                       FROM tickets t 
                       JOIN events e ON t.event_id = e.id 
                       JOIN users u ON t.booked_by = u.id 
                       WHERE t.id IN ($placeholders)");
$stmt->execute($ids);
$tickets = $stmt->fetchAll();

if (!$tickets) {
    die("Tickets not found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Bulk Tickets</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #fff; margin: 0; padding: 0; color: #000; }
        .ticket-wrapper {
            width: 80mm;
            margin: 0 auto;
            background: #fff;
            padding: 10px;
            box-sizing: border-box;
            border-bottom: 2px dashed #000;
            page-break-after: always;
        }
        .header { padding: 15px; text-align: center; border-bottom: 1px dashed #ddd; }
        .header h1 { margin: 0; font-size: 1.4rem; color: #333; }
        .type-banner { padding: 8px; text-align: center; font-weight: 800; font-size: 0.9rem; background: #eee; border: 1px solid #000; margin: 5px 0; }
        .content { padding: 15px; text-align: center; }
        .code-container { border: 1px solid #eee; padding: 10px; margin: 10px 0; }
        .code-container h2 { margin: 5px 0; font-size: 1.8rem; font-weight: 900; }
        .info-grid { display: flex; justify-content: space-between; text-align: left; font-size: 0.8rem; margin-bottom: 10px; }
        .footer { padding: 10px; text-align: center; font-size: 0.7rem; color: #777; border-top: 1px dashed #ddd; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="padding: 20px; text-align: center; background: #f8f9fa;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print All Tickets</button>
        <p>Total Tickets: <?php echo count($tickets); ?></p>
    </div>

    <?php foreach ($tickets as $ticket): ?>
    <div class="ticket-wrapper">
        <div class="header">
            <h1><?php echo strtoupper(SITE_NAME); ?></h1>
        </div>
        
        <div class="type-banner" style="<?php echo $ticket['event_title'] == 'General Entry' ? 'background: #28a745; color: #fff;' : 'background: #6610f2; color: #fff;'; ?>">
            <?php echo $ticket['event_title'] === 'General Entry' ? 'GATE ADMISSION ONLY' : 'EVENT TICKET'; ?>
        </div>
        
        <div class="content">
            <div class="info-grid">
                <div>
                    <small>Event:</small><br>
                    <strong><?php echo $ticket['event_title']; ?></strong>
                </div>
                <div style="text-align: right;">
                    <small>Date:</small><br>
                    <strong><?php echo date('M d, Y', strtotime($ticket['event_date'])); ?></strong>
                </div>
            </div>

            <div class="code-container">
                <div class="qrcode-placeholder" data-code="<?php echo $ticket['ticket_code']; ?>" style="display: flex; justify-content: center; margin-bottom: 5px;"></div>
                <small>TICKET CODE</small>
                <h2><?php echo $ticket['ticket_code']; ?></h2>
            </div>
        </div>
        
        <div class="footer">
            Agent: <?php echo htmlspecialchars($ticket['booked_by']); ?> | <?php echo date('H:i'); ?><br>
            <strong>Terms: Non-refundable. Single entry.</strong>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="<?php echo BASE_URL; ?>assets/js/qrcode.min.js"></script>
    <script>
        document.querySelectorAll(".qrcode-placeholder").forEach(div => {
            new QRCode(div, {
                text: div.getAttribute('data-code'),
                width: 100,
                height: 100
            });
        });
    </script>
</body>
</html>
