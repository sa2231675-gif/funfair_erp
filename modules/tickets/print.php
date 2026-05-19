<?php
require_once '../../config/config.php';
requireLogin();

if (!isset($_GET['id'])) {
    die("Ticket ID required");
}

$stmt = $pdo->prepare("SELECT t.*, e.title as event_title, e.event_date, u.username as booked_by 
                       FROM tickets t 
                       JOIN events e ON t.event_id = e.id 
                       JOIN users u ON t.booked_by = u.id 
                       WHERE t.id = ?");
$stmt->execute([$_GET['id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket not found");
}

if (hasRole(['user/student'])) {
    die("Unauthorized: Students cannot print tickets");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Ticket - <?php echo $ticket['ticket_code']; ?></title>
    <style>
        :root {
            --primary: #6610f2;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        body { font-family: 'Courier New', Courier, monospace; background: #f0f2f5; margin: 0; padding: 20px; color: #000; }
        .ticket-wrapper {
            width: 80mm; /* Standard POS printer size */
            margin: 0 auto;
            background: #fff;
            padding: 10px;
            box-sizing: border-box;
        }
        .header {
            padding: 25px;
            text-align: center;
            background: #f8f9fa;
            border-bottom: 1px dashed #ddd;
        }
        .header h1 { margin: 0; font-size: 1.6rem; color: #333; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; color: #777; font-size: 0.9rem; }

        .type-banner {
            padding: 10px;
            text-align: center;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 1px;
            <?php if ($ticket['event_title'] === 'General Entry'): ?>
                background: #28a745;
                color: #fff;
            <?php else: ?>
                background: #6610f2;
                color: #fff;
            <?php endif; ?>
        }

        .content { padding: 30px; text-align: center; }
        
        .code-container {
            background: #fafafa;
            border: 2px solid #eee;
            padding: 20px;
            margin: 20px 0;
            border-radius: 12px;
        }
        .code-container span { display: block; font-size: 0.7rem; color: #999; text-transform: uppercase; margin-bottom: 8px; }
        .code-container h2 { margin: 0; font-size: 2.2rem; font-weight: 900; color: #333; letter-spacing: 2px; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 0.85rem;
        }
        .info-item label { display: block; color: #888; margin-bottom: 2px; }
        .info-item span { font-weight: 600; color: #444; }

        .price-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            color: #495057;
        }

        .footer {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
            font-size: 0.75rem;
            color: #777;
            border-top: 1px dashed #ddd;
        }
        
        .no-print { margin-top: 25px; text-align: center; }
        .btn-print {
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(102, 16, 242, 0.2);
            transition: transform 0.2s;
        }
        .btn-print:hover { transform: translateY(-2px); }

        @media print {
            .no-print { display: none; }
            @page { margin: 0; }
            body { padding: 0; background: #fff; }
            .ticket-wrapper { width: 100%; padding: 5px; box-shadow: none; border-radius: 0; border: none; }
        }
    </style>
</head>
<body onafterprint="setTimeout(function(){ window.close(); }, 100);">
    <div class="ticket-wrapper">
        <div class="header">
            <h1><?php echo strtoupper(SITE_NAME); ?></h1>
            <p>Magic & Joy for Everyone</p>
        </div>
        
        <div class="type-banner">
            <?php echo $ticket['event_title'] === 'General Entry' ? 'GATE ADMISSION ONLY' : 'EVENT TICKET'; ?>
        </div>
        
        <div class="content">
            <div class="info-grid">
                <div class="info-item">
                    <label>Event Name</label>
                    <span><?php echo $ticket['event_title']; ?></span>
                </div>
                <div class="info-item">
                    <label>Event Date</label>
                    <span><?php echo date('M d, Y', strtotime($ticket['event_date'])); ?></span>
                </div>
            </div>

            <div class="code-container">
                <div id="qrcode" style="display: flex; justify-content: center; margin-bottom: 10px;"></div>
                <span>TICKET CODE</span>
                <h2><?php echo $ticket['ticket_code']; ?></h2>
            </div>

            <?php if ($ticket['event_title'] === 'General Entry'): ?>
                <div style="color: #dc3545; font-size: 0.8rem; margin: 15px 0; font-weight: 700;">
                    <i class="fas fa-exclamation-triangle"></i> NO RIDES INCLUDED
                </div>
            <?php endif; ?>

            <p style="font-size: 0.8rem; color: #666; font-style: italic;">Scan this code at the entry gate.</p>
        </div>
        
        <div class="footer">
            Counter Agent: <?php echo htmlspecialchars($ticket['booked_by']); ?><br>
            Time: <?php echo date('Y-m-d H:i'); ?><br>
            <strong>Terms: Non-refundable. Single entry only.</strong>
        </div>
    </div>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Print Ticket</button>
        <a href="index.php" style="display:block; margin-top:15px; color:#666; text-decoration:none;">Return to List</a>
    </div>

    <script src="<?php echo BASE_URL; ?>assets/js/qrcode.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo $ticket['ticket_code']; ?>",
                width: 150,
                height: 150,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
