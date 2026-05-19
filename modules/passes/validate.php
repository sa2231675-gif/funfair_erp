<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('validate_rides');

$pageTitle = "Validate Universal Pass";

$message = "";
$pass_info = null;

if (isset($_POST['validate_code'])) {
    $code = strtoupper(trim($_POST['validate_code']));
    $swing_id = $_POST['swing_id'] ?: null; // Currently associated swing
    
    $stmt = $pdo->prepare("SELECT * FROM passes WHERE pass_code = ? AND status = 'active'");
    $stmt->execute([$code]);
    $pass_info = $stmt->fetch();
    
    if (!$pass_info) {
        $message = "<div class='alert alert-danger'>Invalid or inactive pass code!</div>";
    } else {
        $now = time();
        $expiry = strtotime($pass_info['valid_until']);

        if ($now > $expiry) {
            $message = "<div class='alert alert-danger'>The pass has expired. It was valid until " . date('M d, H:i', $expiry) . ".</div>";
            $pdo->prepare("UPDATE passes SET status = 'expired' WHERE id = ?")->execute([$pass_info['id']]);
        } else if ($swing_id) {
            // Check usage for THIS SPECIFIC swing
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM ride_usage_logs WHERE pass_id = ? AND swing_id = ?");
            $stmt_check->execute([$pass_info['id'], $swing_id]);
            $usage_count = $stmt_check->fetchColumn();

            if ($usage_count >= 3) {
                $message = "<div class='alert alert-danger'>Limit reached! This pass has already been used 3 times for this ride.</div>";
            } else {
                // Valid pass! Record usage
                $stmt = $pdo->prepare("INSERT INTO ride_usage_logs (pass_id, swing_id, operator_id) VALUES (?, ?, ?)");
                $stmt->execute([$pass_info['id'], $swing_id, $_SESSION['user_id']]);
                
                $new_count = $usage_count + 1;
                $message = "<div class='alert alert-success'>Pass validated! Ride $new_count/3 recorded for this swing.</div>";
            }
        } else {
            $message = "<div class='alert alert-info'>Pass is active! Please select a ride to scan.</div>";
        }
    }
}

$swings = $pdo->query("SELECT * FROM swings WHERE status = 'active'")->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Special Pass Validation</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-dark card-info">
                    <div class="card-header">
                        <h3 class="card-title">Validate Pass</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form action="" method="post">
                            <div class="form-group">
                                <label>Current Ride/Swing</label>
                                <select name="swing_id" class="form-control" required>
                                    <option value="">-- Select Ride --</option>
                                    <?php foreach ($swings as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Enter Pass Code</label>
                                <input type="text" name="validate_code" class="form-control" placeholder="PASS-XXXXXX" required autofocus>
                            </div>
                            <button type="submit" class="btn btn-info btn-block">Scan / Validate Pass</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title">Pass Info</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($pass_info): ?>
                            <div class="p-3 bg-dark-custom rounded border border-info">
                                <h5>Code: <span class="text-neon-info"><?php echo $pass_info['pass_code']; ?></span></h5>
                                <p><strong>Status:</strong> <span class="badge badge-success">ACTIVE</span></p>
                                <p><strong>Valid Until:</strong> <?php echo date('M d, H:i', strtotime($pass_info['valid_until'])); ?></p>
                                <hr>
                                <h6>Usage History (Last 5):</h6>
                                <ul class="list-unstyled">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT rul.usage_time, s.name FROM ride_usage_logs rul JOIN swings s ON rul.swing_id = s.id WHERE rul.pass_id = ? ORDER BY rul.usage_time DESC LIMIT 5");
                                    $stmt->execute([$pass_info['id']]);
                                    while ($row = $stmt->fetch()) {
                                        echo "<li><i class='fas fa-check text-success'></i> " . $row['name'] . " - " . date('H:i', strtotime($row['usage_time'])) . "</li>";
                                    }
                                    if ($stmt->rowCount() == 0) echo "<li>No rides recorded yet.</li>";
                                    ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Enter a pass code to view details and usage history.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
