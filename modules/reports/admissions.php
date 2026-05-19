<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('view_reports');

if (!hasRole(['superadmin', 'admin', 'event manager'])) {
    die("<h2>Unauthorized Access.</h2>");
}

$pageTitle = "Validation & Entry Logs";

// Combined Admission and Ride Usage Logs
$stmt = $pdo->query("SELECT 'Event Entry' as log_type, al.entry_time as log_time, t.ticket_code, e.title as item_name, u.username as operator 
                     FROM admission_logs al 
                     JOIN tickets t ON al.ticket_id = t.id 
                     JOIN events e ON t.event_id = e.id 
                     JOIN users u ON al.operator_id = u.id 
                     UNION ALL
                     SELECT 'Ride Usage' as log_type, rul.usage_time as log_time, rt.ticket_code, s.name as item_name, u.username as operator 
                     FROM ride_usage_logs rul 
                     JOIN ride_tickets rt ON rul.ride_ticket_id = rt.id 
                     JOIN swings s ON rt.swing_id = s.id 
                     JOIN users u ON rul.operator_id = u.id 
                     ORDER BY log_time DESC");
$logs = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Validation & Entry Logs</h1>
            <p class="text-muted text-sm">Combined records of all event admissions and ride validations.</p>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark">
            <div class="card-body p-0">
                <table class="table table-dark-custom datatable m-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Ticket Code</th>
                            <th>Item Name</th>
                            <th>Validated By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['log_time'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $log['log_type'] == 'Event Entry' ? 'info' : 'warning'; ?>">
                                    <?php echo $log['log_type']; ?>
                                </span>
                            </td>
                            <td><strong class="text-glow"><?php echo $log['ticket_code']; ?></strong></td>
                            <td><?php echo $log['item_name']; ?></td>
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
