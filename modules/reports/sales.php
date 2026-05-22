<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('view_reports');

if (!hasRole(['superadmin', 'admin', 'event manager'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit;
}

$date = $_GET['date'] ?? '';
$pageTitle = $date ? "Sales Details: " . date('d M, Y', strtotime($date)) : "Revenue & Sales Summary";

if ($date) {
    // Show Details
    $query = "SELECT 'Event' as type, e.title as item, p.amount, p.payment_method, p.status, p.transaction_id, p.created_at, u.username as sold_by 
              FROM payments p 
              JOIN tickets t ON p.ticket_id = t.id 
              JOIN events e ON t.event_id = e.id 
              JOIN users u ON t.booked_by = u.id
              WHERE DATE(p.created_at) = '$date'
              UNION ALL
              SELECT 'Ride' as type, s.name as item, p.amount, p.payment_method, p.status, p.transaction_id, p.created_at, u.username as sold_by 
              FROM payments p 
              JOIN ride_tickets rt ON p.ride_ticket_id = rt.id 
              JOIN swings s ON rt.swing_id = s.id 
              JOIN users u ON rt.booked_by = u.id
              WHERE DATE(p.created_at) = '$date'
              ORDER BY created_at DESC";
    $stmt = $pdo->query($query);
    $sales = $stmt->fetchAll();
} else {
    // Show Daily Summary
    $stmt = $pdo->query("SELECT 
                            DATE(created_at) as sale_date,
                            COUNT(*) as total_transactions,
                            SUM(amount) as total_amount,
                            SUM(CASE WHEN ticket_id IS NOT NULL THEN amount ELSE 0 END) as event_sales,
                            SUM(CASE WHEN ride_ticket_id IS NOT NULL THEN amount ELSE 0 END) as ride_sales
                         FROM payments 
                         WHERE status = 'completed'
                         GROUP BY DATE(created_at)
                         ORDER BY sale_date DESC");
    $dailySales = $stmt->fetchAll();
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="text-glow"><?php echo $pageTitle; ?></h1>
                <?php if ($date): ?>
                    <a href="sales.php" class="btn btn-sm btn-outline-info"><i class="fas fa-arrow-left"></i> Back to Summary</a>
                <?php endif; ?>
            </div>
            <?php if (!$date): ?>
                <p class="text-muted text-sm mt-2">Combined comprehensive tracking of all revenues, including historical daily sales records.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="content">
        <?php if (!$date): ?>
        <div class="row">
            <div class="col-md-4">
                <div class="info-box stat-box-dark stat-glow-success">
                    <span class="info-box-icon"><i class="fas fa-dollar-sign text-neon-success"></i></span>
                    <div class="info-box-content inner">
                        <span class="info-box-text">Today's Revenue</span>
                        <span class="info-box-number text-neon-success" style="font-size: 1.5rem;">
                            <?php 
                            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = CURDATE() AND status='completed'");
                            echo "Rs. " . number_format($stmt->fetchColumn() ?: 0, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box stat-box-dark stat-glow-info">
                    <span class="info-box-icon"><i class="fas fa-calendar-alt text-neon-info"></i></span>
                    <div class="info-box-content inner">
                        <span class="info-box-text">This Month</span>
                        <span class="info-box-number text-neon-info" style="font-size: 1.5rem;">
                            <?php 
                            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status='completed'");
                            echo "Rs. " . number_format($stmt->fetchColumn() ?: 0, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box stat-box-dark stat-glow-warning">
                    <span class="info-box-icon"><i class="fas fa-chart-bar text-neon-warning"></i></span>
                    <div class="info-box-content inner">
                        <span class="info-box-text">Total All Time</span>
                        <span class="info-box-number text-neon-warning" style="font-size: 1.5rem;">
                            <?php 
                            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status='completed'");
                            echo "Rs. " . number_format($stmt->fetchColumn() ?: 0, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card card-dark">
            <div class="card-header border-0">
                <h3 class="card-title text-neon-primary">
                    <i class="fas <?php echo $date ? 'fa-list-ul' : 'fa-calendar-day'; ?> mr-2"></i> 
                    <?php echo $date ? "Transaction Log" : "Daily Records"; ?>
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (!$date): ?>
                <table class="table table-dark-custom datatable m-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transactions</th>
                            <th>Event Sales</th>
                            <th>Ride Sales</th>
                            <th>Total Revenue</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dailySales as $row): ?>
                        <tr>
                            <td>
                                <span class="text-glow font-weight-bold">
                                    <?php echo date('d M, Y', strtotime($row['sale_date'])); ?>
                                </span>
                            </td>
                            <td><?php echo $row['total_transactions']; ?></td>
                            <td><span class="text-neon-info">Rs. <?php echo number_format($row['event_sales'], 2); ?></span></td>
                            <td><span class="text-neon-warning">Rs. <?php echo number_format($row['ride_sales'], 2); ?></span></td>
                            <td><span class="text-neon-success font-weight-bold">Rs. <?php echo number_format($row['total_amount'], 2); ?></span></td>
                            <td>
                                <a href="sales.php?date=<?php echo $row['sale_date']; ?>" class="btn btn-xs btn-outline-info">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <table class="table table-dark-custom datatable m-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Item Name</th>
                            <th>Amount</th>
                            <th>Method / Ref</th>
                            <th>Status</th>
                            <th>Sold By</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><span class="badge badge-<?php echo $sale['type'] == 'Event' ? 'info' : 'primary'; ?>"><?php echo $sale['type']; ?></span></td>
                            <td><?php echo $sale['item']; ?></td>
                            <td>Rs. <?php echo number_format($sale['amount'], 2); ?></td>
                            <td>
                                <?php echo $sale['payment_method']; ?> 
                                <?php if($sale['transaction_id']) echo "<br><small class='text-muted'>ID: {$sale['transaction_id']}</small>"; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $sale['status'] == 'completed' ? 'success' : ($sale['status'] == 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo strtoupper($sale['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $sale['sold_by']; ?></td>
                            <td><?php echo date('H:i:s', strtotime($sale['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
