<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('book_tickets');

if (hasRole(['ride operator'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit;
}

$pageTitle = "Buy Special Pass (3-Hour)";

$message = "";
$success_code = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price = $_POST['price'] ?: 2000.00;
    $payment_method = $_POST['payment_method'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    $valid_until_display = 'Upon Activation (3 Hours)';
    
    try {
        $pdo->beginTransaction();
        $generated_passes = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            $temp_code = uniqid('TMP-');
            $stmt = $pdo->prepare("INSERT INTO passes (pass_code, price, valid_from, valid_until, status, booked_by) VALUES (?, ?, NULL, NULL, 'pending', ?)");
            $stmt->execute([$temp_code, $price, $_SESSION['user_id']]);
            $pass_id = $pdo->lastInsertId();
            
            $pass_code = 'PASS-' . str_pad($pass_id, 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("UPDATE passes SET pass_code = ? WHERE id = ?");
            $stmt->execute([$pass_code, $pass_id]);
            
            $stmt = $pdo->prepare("INSERT INTO payments (pass_id, amount, payment_method, status) VALUES (?, ?, ?, 'completed')");
            $stmt->execute([$pass_id, $price, $payment_method]);
            
            $generated_passes[] = ['code' => $pass_code];
        }
        
        $pdo->commit();
        
        if ($quantity == 1) {
            $success_code = $generated_passes[0]['code'];
        } else {
            $codes = implode(',', array_column($generated_passes, 'code'));
            $message_success = "<div class='alert alert-success mt-3'>
                                    <h5><i class='icon fas fa-check'></i> $quantity Passes Generated!</h5>
                                    <a href='print_bulk.php?codes=$codes' target='_blank' class='btn btn-dark-custom btn-sm mt-2'><i class='fas fa-print'></i> Print All $quantity Passes</a>
                                </div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Buy Special Event Pass</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-dark card-success">
                    <div class="card-header">
                        <h3 class="card-title">Pass Purchase Form</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message_success)) echo $message_success; ?>
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <?php if ($success_code): ?>
                            <div class="alert alert-success mt-3">
                                <h5><i class="icon fas fa-check"></i> Pass Generated!</h5>
                                Pass Code: <strong><?php echo $success_code; ?></strong><br>
                                Valid Until: <?php echo $valid_until_display; ?><br>
                                <a href="print.php?code=<?php echo $success_code; ?>" target="_blank" class="btn btn-dark-custom btn-sm mt-2"><i class="fas fa-print"></i> Print Pass Now</a>
                            </div>
                        <?php endif; ?>

                        <form action="" method="post">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Pass Price (Rs.)</label>
                                    <input type="number" name="price" class="form-control" value="2000" step="0.01" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit/Debit Card">Credit/Debit Card</option>
                                    <option value="Online (Easypaisa/JazzCash)">Online (Easypaisa/JazzCash)</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                            
                            <div class="callout callout-info mt-4">
                                <h5>Limit: 3 Rides Per Swing</h5>
                                <p>This pass allows up to 3 entries to every single swing or ride. It will expire after 3 hours.</p>
                            </div>

                            <button type="submit" class="btn btn-success btn-block">Generate & Sell Pass</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title">Pass Comparison</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box bg-dark-custom">
                            <span class="info-box-icon text-neon-info"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">3 Hour Access</span>
                                <span class="info-box-number">Unlimited Rides</span>
                            </div>
                        </div>
                        <p class="mt-3">A universal pass is better for customers who want to try multiple rides without buying individual tickets.</p>
                        <ul>
                            <li>No need to buy separate tickets for each ride.</li>
                            <li>Increases customer satisfaction.</li>
                            <li>Easy management for operators.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
