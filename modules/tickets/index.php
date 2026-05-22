<?php
require_once '../../config/config.php';
requireLogin();
requireAnyPermission(['book_tickets', 'view_reports']);

if (hasRole(['ride operator'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit;
}

$pageTitle = "Ticket History";

$date_filter = $_GET['date'] ?? date('Y-m-d');

// Event Tickets
$stmt = $pdo->prepare("SELECT t.*, e.title as event_title, e.price, COALESCE(u.full_name, t.customer_name) as customer_name, b.full_name as salesperson 
                     FROM tickets t 
                     JOIN events e ON t.event_id = e.id 
                     LEFT JOIN users u ON t.user_id = u.id 
                     LEFT JOIN users b ON t.booked_by = b.id 
                     WHERE e.title != 'General Entry' AND DATE(t.created_at) = ?
                     ORDER BY t.created_at DESC");
$stmt->execute([$date_filter]);
$event_tickets = $stmt->fetchAll();

// Gate Entry Tickets
$stmt = $pdo->prepare("SELECT t.*, e.title as event_title, e.price, COALESCE(u.full_name, t.customer_name) as customer_name, b.full_name as salesperson 
                     FROM tickets t 
                     JOIN events e ON t.event_id = e.id 
                     LEFT JOIN users u ON t.user_id = u.id 
                     LEFT JOIN users b ON t.booked_by = b.id 
                     WHERE e.title = 'General Entry' AND DATE(t.created_at) = ?
                     ORDER BY t.created_at DESC");
$stmt->execute([$date_filter]);
$entry_tickets = $stmt->fetchAll();

// Ride Tickets
$stmt = $pdo->prepare("SELECT rt.*, s.name as swing_name, s.price, b.full_name as salesperson 
                     FROM ride_tickets rt 
                     JOIN swings s ON rt.swing_id = s.id 
                     LEFT JOIN users b ON rt.booked_by = b.id 
                     WHERE DATE(rt.created_at) = ?
                     ORDER BY rt.created_at DESC");
$stmt->execute([$date_filter]);
$ride_tickets = $stmt->fetchAll();

// Special Passes
$stmt = $pdo->prepare("SELECT p.*, b.full_name as salesperson 
                     FROM passes p 
                     LEFT JOIN users b ON p.booked_by = b.id 
                     WHERE DATE(p.created_at) = ?
                     ORDER BY p.created_at DESC");
$stmt->execute([$date_filter]);
$passes = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="text-glow">Tickets History</h1>
                </div>
                <div class="col-sm-6">
                    <form action="" method="GET" class="form-inline float-sm-right">
                        <div class="input-group">
                            <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> View History
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary ml-1" title="Show Today">Today</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-outline card-info">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="ticketsTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="event-tab-link" data-toggle="pill" href="#event-tab" role="tab"><i class="fas fa-ticket-alt text-neon-info mr-1"></i> Event Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="entry-tab-link" data-toggle="pill" href="#entry-tab" role="tab"><i class="fas fa-door-open text-neon-success mr-1"></i> Gate Entry</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ride-tab-link" data-toggle="pill" href="#ride-tab" role="tab"><i class="fas fa-vr-cardboard text-neon-primary mr-1"></i> Ride Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pass-tab-link" data-toggle="pill" href="#pass-tab" role="tab"><i class="fas fa-id-card text-neon-warning mr-1"></i> Special Passes</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="ticketsTabContent">
                    
                    <!-- Events Tab -->
                    <div class="tab-pane fade show active" id="event-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-dark-custom datatable text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Ticket Code</th>
                                        <th>Event Name</th>
                                        <th>Price</th>
                                        <th>Customer</th>
                                        <th>Booked By</th>
                                        <th>Status</th>
                                        <th>Date/Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event_tickets as $ticket): ?>
                                    <tr>
                                        <td><strong><?php echo $ticket['ticket_code']; ?></strong></td>
                                        <td><?php echo $ticket['event_title']; ?></td>
                                        <td>Rs. <?php echo number_format($ticket['price'], 2); ?></td>
                                        <td><?php echo $ticket['customer_name'] ?? 'Walk-in'; ?></td>
                                        <td><?php echo $ticket['salesperson']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $ticket['status'] == 'paid' ? 'success' : ($ticket['status'] == 'used' ? 'secondary' : 'danger'); ?>">
                                                <?php echo strtoupper($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <a href="print.php?id=<?php echo $ticket['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-print"></i></a>
                                            <?php if ($ticket['status'] == 'paid'): ?>
                                            <a href="cancel.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this ticket?')"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Gate Entry Tab -->
                    <div class="tab-pane fade" id="entry-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-dark-custom datatable text-nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Ticket Code</th>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Customer</th>
                                        <th>Booked By</th>
                                        <th>Status</th>
                                        <th>Date/Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entry_tickets as $ticket): ?>
                                    <tr>
                                        <td><strong><?php echo $ticket['ticket_code']; ?></strong></td>
                                        <td><span class="badge badge-success">Gate Admission</span></td>
                                        <td>Rs. <?php echo number_format($ticket['price'], 2); ?></td>
                                        <td><?php echo $ticket['customer_name'] ?? 'Walk-in'; ?></td>
                                        <td><?php echo $ticket['salesperson']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $ticket['status'] == 'paid' ? 'success' : ($ticket['status'] == 'used' ? 'secondary' : 'danger'); ?>">
                                                <?php echo strtoupper($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <a href="print.php?id=<?php echo $ticket['id']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-print"></i></a>
                                            <?php if ($ticket['status'] == 'paid'): ?>
                                            <a href="cancel.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this ticket?')"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Ride Tickets Tab -->
                    <div class="tab-pane fade" id="ride-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-dark-custom datatable text-nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Ticket/Serial</th>
                                        <th>Swing/Ride Name</th>
                                        <th>Price</th>
                                        <th>Booked By</th>
                                        <th>Status</th>
                                        <th>Date/Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ride_tickets as $ticket): ?>
                                    <tr>
                                        <td><strong><?php echo $ticket['ticket_code']; ?></strong></td>
                                        <td><?php echo $ticket['swing_name']; ?></td>
                                        <td>Rs. <?php echo number_format($ticket['price'], 2); ?></td>
                                        <td><?php echo $ticket['salesperson']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $ticket['status'] == 'paid' ? 'primary' : ($ticket['status'] == 'used' ? 'secondary' : 'danger'); ?>">
                                                <?php echo strtoupper($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <a href="../rides/print.php?code=<?php echo $ticket['ticket_code']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-print"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Special Passes Tab -->
                    <div class="tab-pane fade" id="pass-tab" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-dark-custom datatable text-nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Pass Code</th>
                                        <th>Price</th>
                                        <th>Sold By</th>
                                        <th>Valid From</th>
                                        <th>Expires At</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($passes as $p): ?>
                                    <?php
                                        $is_pending = ($p['status'] == 'pending');
                                        $expiry = $is_pending ? 0 : strtotime($p['valid_until']);
                                        $is_expired = (!$is_pending && time() > $expiry) || $p['status'] == 'expired';
                                        
                                        $status_class = $is_expired ? 'danger' : ($is_pending ? 'info' : ($p['status'] == 'active' ? 'success' : 'warning'));
                                        $status_text = $is_expired ? 'EXPIRED' : strtoupper($p['status']);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo $p['pass_code']; ?></strong></td>
                                        <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                                        <td><?php echo $p['salesperson']; ?></td>
                                        <td><?php echo $p['valid_from'] ? date('M d, H:i', strtotime($p['valid_from'])) : '<span class="text-muted">-</span>'; ?></td>
                                        <td class="<?php echo $is_expired ? 'text-danger' : ($is_pending ? 'text-info' : 'text-neon-success'); ?>">
                                            <?php echo $p['valid_until'] ? date('M d, H:i', strtotime($p['valid_until'])) : '<span class="text-muted">TBD</span>'; ?>
                                        </td>
                                        <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                        <td>
                                            <a href="../passes/print.php?code=<?php echo $p['pass_code']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-print"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
