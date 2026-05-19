<?php
require_once '../../config/config.php';
requireLogin();

if (!isset($_GET['code'])) {
    die("Pass code is required.");
}

$code = $_GET['code'];
$stmt = $pdo->prepare("SELECT p.*, u.full_name as salesperson FROM passes p JOIN users u ON p.booked_by = u.id WHERE p.pass_code = ?");
$stmt->execute([$code]);
$pass = $stmt->fetch();

if (!$pass) {
    die("Invalid pass code.");
}

if (hasRole(['user/student'])) {
    die("Unauthorized: Students cannot print tickets");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Pass - <?php echo $pass['pass_code']; ?></title>
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --dark: #121212;
            --text-glow: 0 0 10px rgba(0, 123, 255, 0.5);
        }
        body { font-family: 'Courier New', Courier, monospace; background: #f4f4f4; margin: 0; padding: 20px; color: #000; }
        .ticket-container {
            width: 80mm; /* Standard POS printer size */
            background: #fff;
            margin: 0 auto;
            position: relative;
            box-sizing: border-box;
            border: 1px solid #ddd;
        }
        .header {
            background: linear-gradient(135deg, #1e1e1e, #333);
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .header h2 { margin: 0; letter-spacing: 2px; font-weight: 800; text-transform: uppercase; color: #00d4ff; }
        .header p { margin: 5px 0 0; font-size: 0.8rem; opacity: 0.8; }
        
        .type-banner {
            background: #ffc107;
            color: #000;
            text-align: center;
            padding: 8px;
            font-weight: bold;
            font-size: 1.1rem;
            text-transform: uppercase;
        }

        .content { padding: 25px; text-align: center; }
        
        .code-box {
            border: 2px dashed #007bff;
            background: #f0f7ff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 10px;
        }
        .code-box .label { display: block; font-size: 0.75rem; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .code-box .code { font-size: 1.8rem; font-weight: 900; color: #007bff; letter-spacing: 3px; }

        .details { margin: 15px 0; font-size: 0.95rem; line-height: 1.6; }
        .details strong { color: #000; }

        .price-tag {
            font-size: 1.4rem;
            font-weight: 800;
            color: #28a745;
            margin: 15px 0;
        }

        .footer {
            background: #f9f9f9;
            padding: 15px;
            text-align: center;
            font-size: 0.7rem;
            color: #777;
            border-top: 1px solid #eee;
        }
        
        .cut-line {
            height: 1px;
            border-top: 2px dashed #ccc;
            margin: 20px 0;
            position: relative;
        }
        .cut-line:before, .cut-line:after {
            content: '';
            width: 20px;
            height: 20px;
            background: #f4f4f4;
            border-radius: 50%;
            position: absolute;
            top: -11px;
        }
        .cut-line:before { left: -30px; }
        .cut-line:after { right: -30px; }

        @media print {
            .no-print { display: none; }
            @page { margin: 0; }
            body { background: #fff; padding: 0; }
            .ticket-container { width: 100%; padding: 5px; box-shadow: none; border: none; }
        }
    </style>
</head>
<body onafterprint="setTimeout(function(){ window.close(); }, 100);">
    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 25px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px; font-weight: bold;">Click to Print</button>
        <a href="index.php" style="margin-left: 15px; text-decoration: none; color: #666;">Back to System</a>
    </div>

    <div class="ticket-container">
        <div class="header">
            <h2>FUNFAIR ERP</h2>
            <p>Quality Family Entertainment</p>
        </div>
        
        <div class="type-banner">SPECIAL ACCESS PASS</div>
        
        <div class="content">
            <div class="code-box">
                <div id="qrcode" style="display: flex; justify-content: center; margin-bottom: 10px;"></div>
                <span class="label">Registration Code</span>
                <div class="code"><?php echo $pass['pass_code']; ?></div>
            </div>
            
            <div class="details">
                Valid From: <strong><?php echo $pass['valid_from'] ? date('M d, H:i', strtotime($pass['valid_from'])) : 'Upon First Scan'; ?></strong><br>
                Valid Until: <strong><?php echo $pass['valid_until'] ? date('M d, H:i', strtotime($pass['valid_until'])) : '3 Hours after first scan'; ?></strong>
            </div>
            
            <div class="price-tag">Rs. <?php echo number_format($pass['price'], 2); ?></div>
            
            <p style="font-size: 0.8rem; color: #666;">This pass allows <strong>3 RIDES PER SWING</strong> for 3 hours from issuance.</p>
        </div>
        
        <div class="footer">
            Issued By: <?php echo htmlspecialchars($pass['salesperson']); ?><br>
            Issue Date: <?php echo date('Y-m-d H:i'); ?><br>
            <p style="margin-top: 10px;">Please present this ticket at the ride entry. Non-refundable.</p>
        </div>
    </div>

    <div class="no-print cut-line" style="width: 380px; margin: 30px auto;"></div>

    <script src="<?php echo BASE_URL; ?>assets/js/qrcode.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo $pass['pass_code']; ?>",
                width: 150,
                height: 150,
                colorDark : "#007bff",
                colorLight : "#f0f7ff",
                correctLevel : QRCode.CorrectLevel.H
            });
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
