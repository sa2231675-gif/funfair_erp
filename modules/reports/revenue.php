<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(['superadmin', 'admin', 'event manager'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$pageTitle = "Revenue Summary";

// Daily revenue for last 30 days
$stmt = $pdo->query("SELECT DATE(created_at) as date, SUM(amount) as total 
                     FROM payments 
                     WHERE status = 'completed' 
                     GROUP BY DATE(created_at) 
                     ORDER BY date DESC LIMIT 30");
$revenue_data = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Revenue Summary</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Today's Revenue</span>
                        <span class="info-box-number">
                            <?php 
                            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = CURDATE()");
                            echo "Rs. " . number_format($stmt->fetchColumn() ?: 0, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-calendar-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">This Month</span>
                        <span class="info-box-number">
                            <?php 
                            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
                            echo "Rs. " . number_format($stmt->fetchColumn() ?: 0, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-chart-bar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total All Time</span>
                        <span class="info-box-number">
                            <?php 
                            $stmt = $pdo->query("SELECT SUM(amount) FROM payments");
                            echo "Rs. " . number_format($stmt->fetchColumn() ?: 0, 2);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-dark mt-4">
            <div class="card-header">
                <h3 class="card-title">Daily Revenue (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <table class="table table-dark-custom table-striped datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenue_data as $row): ?>
                        <tr>
                            <td><?php echo $row['date']; ?></td>
                            <td>Rs. <?php echo number_format($row['total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
