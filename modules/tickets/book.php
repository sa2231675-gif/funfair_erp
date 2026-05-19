<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('book_tickets');

$pageTitle = "Book Ticket";
$events = [];
$selected_event = null;

if (isset($_GET['event_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status = 'active'");
    $stmt->execute([$_GET['event_id']]);
    $selected_event = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $customer_name = $_POST['customer_name'] ?? 'Walk-in';
    $payment_method = $_POST['payment_method'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;
    
    try {
        $pdo->beginTransaction();
        
        // Check capacity
        $stmt = $pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status != 'cancelled') as sold FROM events WHERE id = ?");
        $stmt->execute([$event_id, $event_id]);
        $event_info = $stmt->fetch();
        
        if (($event_info['sold'] + $quantity) > $event_info['capacity']) {
            $available = $event_info['capacity'] - $event_info['sold'];
            throw new Exception("Not enough capacity! Only $available tickets left.");
        }

        // Get event price
        $stmt = $pdo->prepare("SELECT price FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $price = $stmt->fetchColumn();

        $generated_tickets = [];

        for ($i = 0; $i < $quantity; $i++) {
            // 1. Create Ticket (temp code first to secure ID)
            $temp_code = uniqid('TMP-');
            $stmt = $pdo->prepare("INSERT INTO tickets (event_id, ticket_code, status, booked_by, customer_name) VALUES (?, ?, 'paid', ?, ?)");
            $stmt->execute([$event_id, $temp_code, $_SESSION['user_id'], $customer_name]);
            $ticket_id = $pdo->lastInsertId();

            // Update with sequential code
            $ticket_code = 'EVT-' . str_pad($ticket_id, 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("UPDATE tickets SET ticket_code = ? WHERE id = ?");
            $stmt->execute([$ticket_code, $ticket_id]);

            // 2. Record Payment
            $stmt = $pdo->prepare("INSERT INTO payments (ticket_id, amount, payment_method, status) VALUES (?, ?, ?, 'completed')");
            $stmt->execute([$ticket_id, $price, $payment_method]);
            
            $generated_tickets[] = ['id' => $ticket_id, 'code' => $ticket_code];
        }

        $pdo->commit();
        
        if ($quantity == 1) {
            $ticket = $generated_tickets[0];
            $message = "<div class='alert alert-success mt-3'>Ticket Booked Successfully! Code: <strong>{$ticket['code']}</strong> <br> <a href='print.php?id={$ticket['id']}' target='_blank' class='btn btn-dark-custom btn-sm mt-2'><i class='fas fa-print'></i> Print Ticket</a></div>";
        } else {
            $ids = implode(',', array_column($generated_tickets, 'id'));
            $message = "<div class='alert alert-success mt-3'><strong>$quantity</strong> Tickets Booked Successfully! <br> <a href='print_bulk.php?ids=$ids' target='_blank' class='btn btn-dark-custom btn-sm mt-2'><i class='fas fa-print'></i> Print All $quantity Tickets</a></div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger mt-3'>Error: " . $e->getMessage() . "</div>";
    }
}

$stmt = $pdo->query("SELECT * FROM events WHERE status = 'active' AND event_date >= CURDATE()");
$events = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Book Event Ticket</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-dark card-success">
                    <div class="card-header">
                        <h3 class="card-title">Booking Form</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>
                        <form action="" method="post">
                            <div class="form-group">
                                <label>Select Event</label>
                                <select name="event_id" class="form-control" required id="event_select">
                                    <option value="">-- Select Event --</option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?php echo $event['id']; ?>" 
                                            data-price="<?php echo $event['price']; ?>"
                                            <?php echo ($selected_event && $selected_event['id'] == $event['id']) ? 'selected' : ''; ?>>
                                            <?php echo $event['title']; ?> - Rs. <?php echo $event['price']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Customer Name</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="Optional">
                            </div>
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
                                        <input type="number" name="quantity" id="quantity_input" class="form-control" value="1" min="1" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="callout callout-info mt-4">
                                <h5>Total Amount: <span id="total_price">Rs. <?php echo $selected_event ? number_format($selected_event['price'], 2) : '0.00'; ?></span></h5>
                            </div>

                            <button type="submit" class="btn btn-success btn-block">Confirm Booking & Pay</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-dark">
                    <div class="card-header">
                        <h3 class="card-title">Event Instructions</h3>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Please verify the event date before booking.</li>
                            <li>Tickets once sold are non-refundable.</li>
                            <li>A unique ticket code will be generated upon successful payment.</li>
                            <li>Ticket must be validated at the entry gate.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.getElementById('event_select').addEventListener('change', updateTotalPrice);
document.getElementById('quantity_input').addEventListener('input', updateTotalPrice);

function updateTotalPrice() {
    const selectedOption = document.getElementById('event_select').options[document.getElementById('event_select').selectedIndex];
    const price = selectedOption ? selectedOption.getAttribute('data-price') : 0;
    const qty = document.getElementById('quantity_input').value || 0;
    const total = parseFloat(price) * parseInt(qty);
    document.getElementById('total_price').innerText = isNaN(total) ? 'Rs. 0.00' : 'Rs. ' + total.toFixed(2);
}
</script>

<?php include '../../includes/footer.php'; ?>
