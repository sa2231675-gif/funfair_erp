<?php
require_once '../../config/config.php';
requireLogin();

if (!isset($_GET['code'])) {
    die("Ticket code required");
}

$stmt = $pdo->prepare("SELECT rt.*, s.name as swing_name, s.price, u.username as booked_by 
                       FROM ride_tickets rt 
                       JOIN swings s ON rt.swing_id = s.id 
                       JOIN users u ON rt.booked_by = u.id 
                       WHERE rt.ticket_code = ?");
$stmt->execute([$_GET['code']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ride ticket not found");
}

if (hasRole(['user/student'])) {
    die("Unauthorized: Students cannot print tickets");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Ride Pass - <?php echo $ticket['ticket_code']; ?></title>
    <style>
        :root {
            --primary: #e83e8c;
            --dark: #343a40;
        }
        body { font-family: 'Courier New', Courier, monospace; background: #f8f9fa; margin: 0; padding: 20px; color: #000; }
        .ticket-card {
            width: 80mm; /* Standard POS printer size */
            margin: 0 auto;
            background: #fff;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ddd;
        }
        .header {
            background: var(--primary);
            color: white;
            padding: 15px;
            text-align: center;
        }
        .header h3 { margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        
        .body { padding: 20px; text-align: center; }
        .ride-title { font-size: 1.4rem; font-weight: 700; color: var(--dark); margin-bottom: 5px; }
        
        .code-box {
            background: #fff0f6;
            border: 2px dashed var(--primary);
            padding: 15px;
            margin: 15px 0;
            border-radius: 10px;
        }
        .code-box small { display: block; color: #d63384; font-size: 0.7rem; margin-bottom: 5px; font-weight: 600; }
        .code-box h1 { margin: 0; font-size: 1.8rem; color: var(--dark); letter-spacing: 2px; }

        .price-info { font-size: 1.2rem; font-weight: 700; color: #28a745; margin: 10px 0; }
        
        .footer {
            padding: 15px;
            background: #fdfdfd;
            border-top: 1px solid #eee;
            font-size: 0.7rem;
            color: #888;
            text-align: center;
        }

        .no-print { text-align: center; margin-bottom: 20px; }
        .btn {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }

        @media print {
            .no-print { display: none; }
            @page { margin: 0; }
            body { background: #fff; padding: 0; }
            .ticket-card { width: 100%; padding: 5px; box-shadow: none; border: none; }
        }
    </style>
</head>
<body onafterprint="setTimeout(function(){ window.close(); }, 100);">
    <div class="no-print">
        <button class="btn" onclick="window.print()">Print Ride Pass</button>
        <a href="booking.php" style="margin-left: 10px; color: #666; font-size: 0.8rem;">Back</a>
    </div>

    <div class="ticket-card">
        <div class="header">
            <h3>RIDE PASS</h3>
        </div>
        <div class="body">
            <div class="ride-title"><?php echo $ticket['swing_name']; ?></div>
            <p style="font-size: 0.8rem; color: #666; margin: 0;">FunFair Entertainment Hub</p>

            <div class="code-box">
                <div id="qrcode" style="display: flex; justify-content: center; margin-bottom: 10px;"></div>
                <small>TICKET SERIAL</small>
                <h1><?php echo $ticket['ticket_code']; ?></h1>
            </div>

            <div class="price-info">Rs. <?php echo number_format($ticket['price'], 2); ?></div>
            
            <p style="font-size: 0.75rem; color: #999;">This ticket is valid for one person/one ride only.</p>
        </div>
        <div class="footer">
            Issued By: <?php echo $ticket['booked_by']; ?><br>
            Time: <?php echo date('Y-m-d H:i'); ?><br>
            <p style="margin-top: 5px;"><strong>Note:</strong> Non-refundable. Keep until the ride ends.</p>
        </div>
    </div>
    <script src="<?php echo BASE_URL; ?>assets/js/qrcode.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo $ticket['ticket_code']; ?>",
                width: 150,
                height: 150,
                colorDark : "#e83e8c",
                colorLight : "#fff0f6",
                correctLevel : QRCode.CorrectLevel.H
            });
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
