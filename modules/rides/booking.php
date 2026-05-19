<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = "Sell Ride Pass";
$swings = [];
$selected_swing = null;

if (isset($_GET['swing_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM swings WHERE id = ? AND status = 'active'");
    $stmt->execute([$_GET['swing_id']]);
    $selected_swing = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $swing_id = $_POST['swing_id'];
    $payment_method = $_POST['payment_method'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT price FROM swings WHERE id = ?");
        $stmt->execute([$swing_id]);
        $price = $stmt->fetchColumn();

        $generated_tickets = [];

        for ($i = 0; $i < $quantity; $i++) {
            // 1. Create Ride Ticket (temp code to secure ID)
            $temp_code = uniqid('TMP-');
            $stmt = $pdo->prepare("INSERT INTO ride_tickets (swing_id, ticket_code, status, booked_by) VALUES (?, ?, 'paid', ?)");
            $stmt->execute([$swing_id, $temp_code, $_SESSION['user_id']]);
            $ride_ticket_id = $pdo->lastInsertId();

            // Update with sequential code
            $ticket_code = 'RID-' . str_pad($ride_ticket_id, 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("UPDATE ride_tickets SET ticket_code = ? WHERE id = ?");
            $stmt->execute([$ticket_code, $ride_ticket_id]);

            // 2. Record Payment
            $stmt = $pdo->prepare("INSERT INTO payments (ride_ticket_id, amount, payment_method, status) VALUES (?, ?, ?, 'completed')");
            $stmt->execute([$ride_ticket_id, $price, $payment_method]);
            
            $generated_tickets[] = ['code' => $ticket_code];
        }

        $pdo->commit();
        
        if ($quantity == 1) {
            header("Location: booking.php?success=ticket_sold&code=" . $generated_tickets[0]['code']);
        } else {
            $codes = implode(',', array_column($generated_tickets, 'code'));
            header("Location: booking.php?success=bulk_sold&codes=" . $codes . "&qty=" . $quantity);
        }
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM swings WHERE status = 'active'");
$swings = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Sell Ride Pass</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-dark card-outline card-primary">
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success mt-3">
                                <?php if ($_GET['success'] == 'ticket_sold'): ?>
                                    <h5><i class="icon fas fa-check"></i> Ticket Sold!</h5>
                                    Code: <strong><?php echo $_GET['code']; ?></strong><br>
                                    <a href="print.php?code=<?php echo $_GET['code']; ?>" target="_blank" class="btn btn-dark-custom btn-sm mt-2"><i class="fas fa-print"></i> Print Ride Pass Now</a>
                                <?php else: ?>
                                    <h5><i class="icon fas fa-check"></i> <?php echo $_GET['qty']; ?> Tickets Sold!</h5>
                                    <a href="print_bulk.php?codes=<?php echo $_GET['codes']; ?>" target="_blank" class="btn btn-dark-custom btn-sm mt-2"><i class="fas fa-print"></i> Print All <?php echo $_GET['qty']; ?> Passes</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($message)): ?>
                            <div class="alert alert-danger"><?php echo $message; ?></div>
                        <?php endif; ?>

                        <form action="" method="post">
                            <div class="form-group">
                                <label>Select Ride/Swing</label>
                                <select name="swing_id" class="form-control" required id="swing_select">
                                    <option value="">-- Select Ride --</option>
                                    <?php foreach ($swings as $swing): ?>
                                        <option value="<?php echo $swing['id']; ?>" 
                                            data-price="<?php echo $swing['price']; ?>"
                                            data-capacity="<?php echo $swing['capacity']; ?>"
                                            <?php echo ($selected_swing && $selected_swing['id'] == $swing['id']) ? 'selected' : ''; ?>>
                                            <?php echo $swing['name']; ?> - Rs. <?php echo $swing['price']; ?> (Seats: <?php echo $swing['capacity'] ?? 'N/A'; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <select name="payment_method" class="form-control" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Credit/Debit Card">Credit/Debit Card</option>
                                            <option value="Online (Easypaisa/JazzCash)">Online (Easypaisa/JazzCash)</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Quantity</label>
                                        <div class="input-group">
                                            <input type="number" name="quantity" id="quantity_input" class="form-control" value="1" min="1" required>
                                            <div class="input-group-append">
                                                <button type="button" id="fill_full_btn" class="btn btn-outline-info" title="Fill Full Ride Seats"><i class="fas fa-users"></i> Full</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="callout callout-primary mt-4">
                                <h5>Total Price: <span id="swing_price">Rs. <?php echo $selected_swing ? number_format($selected_swing['price'], 2) : '0.00'; ?></span></h5>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">Generate Ride Passes</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-dark">
                    <div class="card-header border-0">
                        <h3 class="card-title">Recent Ride Passes</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-dark-custom table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Ride</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT rt.*, s.name FROM ride_tickets rt JOIN swings s ON rt.swing_id = s.id ORDER BY rt.created_at DESC LIMIT 5");
                                while($row = $stmt->fetch()) {
                                    echo "<tr>
                                            <td>{$row['ticket_code']}</td>
                                            <td>{$row['name']}</td>
                                            <td>".date('H:i', strtotime($row['created_at']))."</td>
                                            <td><a href='print.php?code={$row['ticket_code']}' target='_blank' class='btn btn-xs btn-outline-info'><i class='fas fa-print'></i></a></td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.getElementById('swing_select').addEventListener('change', updatePrice);
document.getElementById('quantity_input').addEventListener('input', updatePrice);

function updatePrice() {
    const selectedOption = document.getElementById('swing_select').options[document.getElementById('swing_select').selectedIndex];
    const price = selectedOption ? selectedOption.getAttribute('data-price') : 0;
    const qty = document.getElementById('quantity_input').value || 0;
    const total = parseFloat(price) * parseInt(qty);
    document.getElementById('swing_price').innerText = isNaN(total) ? 'Rs. 0.00' : 'Rs. ' + total.toFixed(2);
}

document.getElementById('fill_full_btn').addEventListener('click', function() {
    const selectedOption = document.getElementById('swing_select').options[document.getElementById('swing_select').selectedIndex];
    const capacity = selectedOption ? selectedOption.getAttribute('data-capacity') : 0;
    if (capacity > 0) {
        document.getElementById('quantity_input').value = capacity;
        updatePrice();
    } else {
        alert("Capacity not defined for this ride.");
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
