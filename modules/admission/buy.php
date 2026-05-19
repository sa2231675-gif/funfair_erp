<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('book_tickets');

$pageTitle = "Sell Entry Ticket";
$message = "";

// Ensure General Entry event exists and get its ID
$stmt = $pdo->prepare("SELECT id, price FROM events WHERE title = 'General Entry' LIMIT 1");
$stmt->execute();
$entry_event = $stmt->fetch();

if (!$entry_event) {
    die("Error: General Entry event not found in database.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;
    if ($quantity > 100) $quantity = 100; // Limit per transaction

    try {
        $pdo->beginTransaction();
        
        $generated_tickets = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            // 1. Create Ticket (temp code first to secure ID)
            $temp_code = uniqid('TMP-');
            $stmt = $pdo->prepare("INSERT INTO tickets (event_id, ticket_code, status, booked_by) VALUES (?, ?, 'paid', ?)");
            $stmt->execute([$entry_event['id'], $temp_code, $_SESSION['user_id']]);
            $ticket_id = $pdo->lastInsertId();
            
            // Update with sequential code
            $ticket_code = 'ENT-' . str_pad($ticket_id, 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("UPDATE tickets SET ticket_code = ? WHERE id = ?");
            $stmt->execute([$ticket_code, $ticket_id]);
            
            // 2. Record Payment
            $stmt = $pdo->prepare("INSERT INTO payments (ticket_id, amount, payment_method, status) VALUES (?, ?, ?, 'completed')");
            $stmt->execute([$ticket_id, $entry_event['price'], $payment_method]);
            
            $generated_tickets[] = ['id' => $ticket_id, 'code' => $ticket_code];
        }
        
        $pdo->commit();
        
        if ($quantity == 1) {
            $ticket = $generated_tickets[0];
            $message = "<div class='alert alert-success'>Entry Ticket Generated! Code: <strong>{$ticket['code']}</strong> <br> <a href='../tickets/print.php?id={$ticket['id']}' target='_blank' class='btn btn-dark-custom btn-sm mt-2'><i class='fas fa-print'></i> Print Ticket</a></div>";
        } else {
            $ids = implode(',', array_column($generated_tickets, 'id'));
            $message = "<div class='alert alert-success'><strong>$quantity</strong> Entry Tickets Generated Successfully! <br> <a href='../tickets/print_bulk.php?ids=$ids' target='_blank' class='btn btn-dark-custom btn-sm mt-2'><i class='fas fa-print'></i> Print All $quantity Tickets</a></div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Sell Entry-Only Ticket</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-dark card-success">
                    <div class="card-header border-0">
                        <h3 class="card-title">Admission Counter</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <div class="text-center mb-4">
                            <h2 class="text-neon-success">Rs. <?php echo number_format($entry_event['price'], 2); ?></h2>
                            <p class="text-muted">Per Person Entry Fee</p>
                        </div>

                        <form action="" method="post">
                            <div class="row">
                                <div class="col-md-8">
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
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Quantity</label>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="100" required>
                                    </div>
                                </div>
                            </div>
                            <div class="callout callout-warning mt-4">
                                <h5><i class="fas fa-info-circle"></i> Ground Access Only</h5>
                                <p>This ticket ONLY allows entry through the main gate. No swings or rides are included.</p>
                            </div>
                            <button type="submit" class="btn btn-success btn-block btn-lg">Generate Entry Ticket</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-dark">
                    <div class="card-header border-0">
                        <h3 class="card-title">Recent Admissions</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-dark-custom table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT ticket_code, created_at, status FROM tickets WHERE event_id = ? ORDER BY created_at DESC LIMIT 5");
                                $stmt->execute([$entry_event['id']]);
                                while($row = $stmt->fetch()) {
                                    $badge = $row['status'] == 'paid' ? 'success' : 'secondary';
                                    echo "<tr>
                                            <td class='font-weight-bold'>{$row['ticket_code']}</td>
                                            <td>".date('H:i', strtotime($row['created_at']))."</td>
                                            <td><span class='badge badge-$badge'>".strtoupper($row['status'])."</span></td>
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

<?php include '../../includes/footer.php'; ?>
