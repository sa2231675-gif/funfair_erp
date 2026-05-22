<?php
require_once '../../config/config.php';
requireLogin();

// Only superadmin, cashier, and eventmanager have access to this page
if (!hasRole(['superadmin', 'cashier', 'eventmanager'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit;
}

$pageTitle = "Shift Closing Report";
$user_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');
$selected_user = isset($_GET['user_id']) ? $_GET['user_id'] : $user_id;

// Only superadmin/eventmanager can view others' reports
if ($selected_user != $user_id && !hasRole(['superadmin', 'eventmanager'])) {
    $selected_user = $user_id;
}

// Cashier details
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$selected_user]);
$cashier_name = $stmt->fetchColumn();

// Base query for payments created by this user on this date
$query = "
    SELECT p.payment_method, SUM(p.amount) as total_amount, COUNT(p.id) as txn_count
    FROM payments p
    LEFT JOIN tickets t ON p.ticket_id = t.id
    LEFT JOIN ride_tickets rt ON p.ride_ticket_id = rt.id
    LEFT JOIN passes ps ON p.pass_id = ps.id
    WHERE (t.booked_by = ? OR rt.booked_by = ? OR ps.booked_by = ?)
    AND DATE(p.created_at) = ? AND p.status = 'completed'
    GROUP BY p.payment_method
";

$stmt = $pdo->prepare($query);
$stmt->execute([$selected_user, $selected_user, $selected_user, $date]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Detailed summary
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE booked_by = ? AND DATE(created_at) = ?");
$stmt->execute([$selected_user, $date]);
$total_entry_tickets = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ride_tickets WHERE booked_by = ? AND DATE(created_at) = ?");
$stmt->execute([$selected_user, $date]);
$total_ride_tickets = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM passes WHERE booked_by = ? AND DATE(created_at) = ?");
$stmt->execute([$selected_user, $date]);
$total_passes = $stmt->fetchColumn();

// Fetch users for admin dropdown
$users = [];
if (hasRole(['superadmin', 'eventmanager'])) {
    $users = $pdo->query("SELECT id, full_name FROM users WHERE status = 'active'")->fetchAll();
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow"><i class="fas fa-file-invoice-dollar text-neon-warning mr-2"></i> End of Shift / Cashier Report</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-dark card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Filter Report</h3>
                    </div>
                    <div class="card-body">
                        <form method="get">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                            </div>
                            <?php if (hasRole(['superadmin', 'eventmanager'])): ?>
                            <div class="form-group">
                                <label>Select Cashier</label>
                                <select name="user_id" class="form-control">
                                    <?php foreach($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $selected_user == $u['id'] ? 'selected' : ''; ?>>
                                        <?php echo $u['full_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i> Generate Report</button>
                        </form>
                    </div>
                </div>
                
                <div class="card card-dark">
                    <div class="card-body bg-dark-custom text-center py-4">
                        <i class="fas fa-print fa-3x text-neon-success mb-3"></i>
                        <h5>Ready to close drawer?</h5>
                        <p class="text-muted text-sm">Print this report and tally your cash before submitting it to the manager.</p>
                        <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print"></i> Print Z-Report</button>
                    </div>
                </div>
            </div>

            <div class="col-md-8 print-section">
                <div class="card card-dark" id="report-card">
                    <div class="card-header text-center py-3">
                        <h2 class="card-title text-glow mb-0" style="float:none; font-size: 1.5rem;"><?php echo SITE_NAME; ?> Z-Report</h2>
                        <div class="mt-2 text-muted">Shift Summary for <?php echo htmlspecialchars($cashier_name); ?></div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center mb-4">
                            <div class="col-sm-6">
                                <p class="mb-1 text-muted">Report Date</p>
                                <h4><?php echo date('M d, Y', strtotime($date)); ?></h4>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-1 text-muted">Generated On</p>
                                <h4><?php echo date('H:i'); ?></h4>
                            </div>
                        </div>

                        <h5 class="text-neon-info border-bottom border-secondary pb-2 mb-3">Sales Breakdown</h5>
                        <div class="row text-center mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-dark-custom rounded">
                                    <h3 class="text-white"><?php echo $total_entry_tickets; ?></h3>
                                    <span class="text-muted">Event Tickets</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-dark-custom rounded">
                                    <h3 class="text-white"><?php echo $total_ride_tickets; ?></h3>
                                    <span class="text-muted">Ride Passes</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-dark-custom rounded">
                                    <h3 class="text-white"><?php echo $total_passes; ?></h3>
                                    <span class="text-muted">Special Passes</span>
                                </div>
                            </div>
                        </div>

                        <h5 class="text-neon-warning border-bottom border-secondary pb-2 mb-3">Collection by Method</h5>
                        <div class="table-responsive">
                            <table class="table table-dark-custom table-striped">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th class="text-center">Transactions</th>
                                        <th class="text-right">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $grand_total = 0;
                                    $total_txns = 0;
                                    foreach ($payments as $p): 
                                        $grand_total += $p['total_amount'];
                                        $total_txns += $p['txn_count'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $p['payment_method'] ?? 'Unknown'; ?></strong></td>
                                        <td class="text-center"><?php echo $p['txn_count']; ?></td>
                                        <td class="text-right text-glow text-neon-success">Rs. <?php echo number_format($p['total_amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if(empty($payments)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No sales recorded for this date.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-dark" style="border-top: 2px solid #555;">
                                        <th>GRAND TOTAL</th>
                                        <th class="text-center"><?php echo $total_txns; ?></th>
                                        <th class="text-right text-white" style="font-size: 1.25rem;">Rs. <?php echo number_format($grand_total, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-5 text-center px-5 d-none d-print-block">
                            <hr style="border-color: #333; margin-bottom: 5px; width: 50%; margin-left: auto; margin-right: auto;">
                            Signature of Cashier / Operator
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #report-card, #report-card * { visibility: visible; }
    #report-card { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; border: none; }
    body { background: #fff !important; }
    .text-white, .text-neon-info, .text-neon-warning, .text-glow { color: #000 !important; text-shadow: none !important; }
    .bg-dark-custom, .card-dark, .table-dark-custom { background: #fff !important; color: #000 !important; }
    .border-secondary { border-color: #000 !important; }
    table, th, td { border-color: #ccc !important; }
    .d-print-block { display: block !important; }
}
</style>

<?php include '../../includes/footer.php'; ?>
