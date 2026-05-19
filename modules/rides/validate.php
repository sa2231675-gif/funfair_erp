<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('validate_rides');

$pageTitle = "Ride Validation";
$message = '';
$alertType = 'info';
$ticket_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['ticket_code']);
    
    $stmt = $pdo->prepare("SELECT rt.*, s.name as swing_name FROM ride_tickets rt JOIN swings s ON rt.swing_id = s.id WHERE rt.ticket_code = ?");
    $stmt->execute([$code]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $message = "Invalid Ride Ticket Code!";
        $alertType = 'danger';
    } else if ($ticket['status'] === 'used') {
        $message = "Ride Ticket already USED!";
        $alertType = 'warning';
        $ticket_info = $ticket;
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE ride_tickets SET status = 'used' WHERE id = ?");
            $stmt->execute([$ticket['id']]);
            
            $stmt = $pdo->prepare("INSERT INTO ride_usage_logs (ride_ticket_id, operator_id) VALUES (?, ?)");
            $stmt->execute([$ticket['id'], $_SESSION['user_id']]);
            
            $pdo->commit();
            $message = "ENJOY THE RIDE! Ticket validated.";
            $alertType = 'success';
            $ticket_info = $ticket;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $alertType = 'danger';
        }
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Ride Operator Validation</h1>
        </div>
    </section>

    <section class="content">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-dark card-outline card-primary">
                    <div class="card-body">
                        <form action="" method="post" class="mb-4">
                            <div class="input-group input-group-lg">
                                <input type="text" name="ticket_code" class="form-control" placeholder="Scan Ride Ticket Code" required autofocus autocomplete="off">
                                <span class="input-group-append">
                                    <button type="submit" class="btn btn-primary btn-flat">Validate Ride</button>
                                </span>
                            </div>
                        </form>

                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $alertType; ?>">
                                <h5><?php echo $message; ?></h5>
                            </div>
                        <?php endif; ?>

                        <?php if ($ticket_info): ?>
                            <div class="callout callout-info mt-3">
                                <h5>Ride: <?php echo $ticket_info['swing_name']; ?></h5>
                                <p>Code: <?php echo $ticket_info['ticket_code']; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
