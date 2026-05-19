<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('view_reports');

$pageTitle = "Payments List";

$stmt = $pdo->query("SELECT p.*, t.ticket_code as evtCode, rt.ticket_code as ridCode 
                     FROM payments p 
                     LEFT JOIN tickets t ON p.ticket_id = t.id 
                     LEFT JOIN ride_tickets rt ON p.ride_ticket_id = rt.id 
                     ORDER BY p.created_at DESC");
$payments = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>All Payments</h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark">
            <div class="card-body">
                <table class="table table-dark-custom table-dark-custom datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reference Code</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td>
                                <?php 
                                if ($p['evtCode']) echo "<span class='badge badge-info'>Event: " . $p['evtCode'] . "</span>";
                                if ($p['ridCode']) echo "<span class='badge badge-primary'>Ride: " . $p['ridCode'] . "</span>";
                                ?>
                            </td>
                            <td>Rs. <?php echo number_format($p['amount'], 2); ?></td>
                            <td><?php echo $p['payment_method']; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $p['status'] == 'completed' ? 'success' : ($p['status'] == 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo strtoupper($p['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $p['transaction_id'] ?: 'N/A'; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($p['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
