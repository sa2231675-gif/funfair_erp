<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('view_reports');

$pageTitle = "Ride Usage Report";

$stmt = $pdo->query("SELECT rul.usage_time, rt.ticket_code, s.name as ride_name, u.username as operator 
                     FROM ride_usage_logs rul 
                     JOIN ride_tickets rt ON rul.ride_ticket_id = rt.id 
                     JOIN swings s ON rt.swing_id = s.id 
                     JOIN users u ON rul.operator_id = u.id 
                     ORDER BY rul.usage_time DESC");
$logs = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Ride Usage History</h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark">
            <div class="card-body">
                <table class="table table-dark-custom table-dark-custom datatable">
                    <thead>
                        <tr>
                            <th>Usage Time</th>
                            <th>Ride Ticket Code</th>
                            <th>Ride Name</th>
                            <th>Validated By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['usage_time'])); ?></td>
                            <td><strong><?php echo $log['ticket_code']; ?></strong></td>
                            <td><?php echo $log['ride_name']; ?></td>
                            <td><?php echo $log['operator']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
