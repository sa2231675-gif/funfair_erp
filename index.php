<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = "Dashboard";

// Fetch counts for dashboard
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'active'");
$eventCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT (SELECT COUNT(*) FROM tickets WHERE status IN ('paid', 'used')) + (SELECT COUNT(*) FROM ride_tickets WHERE status IN ('paid', 'used'))");
$ticketSales = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
$totalRevenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT SUM(amount) FROM expenses");
$totalExpenses = $stmt->fetchColumn() ?: 0;

$netProfit = $totalRevenue - $totalExpenses;

$stmt = $pdo->query("SELECT COUNT(*) FROM passes WHERE (status = 'active' AND valid_until > NOW()) OR status = 'pending'");
$activePasses = $stmt->fetchColumn();

include 'includes/header.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow">Dashboard Overview</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content pt-3">
        <div class="container-fluid px-4">
            <!-- Small boxes (Stat box) -->
            <div class="row">
                <?php if ((hasPermission('book_tickets') || hasPermission('view_reports')) && !hasRole(['ride operator'])): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/tickets/index.php" class="stat-box-dark stat-glow-primary">
                        <div class="inner">
                            <h3><?php echo $ticketSales; ?></h3>
                            <p>Tickets Sold</p>
                        </div>
                        <div class="icon text-neon-primary">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasRole') && hasRole(['superadmin', 'admin', 'event manager'])): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/reports/revenue.php" class="stat-box-dark stat-glow-success">
                        <div class="inner">
                            <h3>Rs. <?php echo number_format($netProfit, 2); ?></h3>
                            <p>Net Profit</p>
                            <small class="text-white-50" style="font-size: 0.75rem;">Rev: Rs. <?php echo number_format($totalRevenue, 2); ?> | Exp: Rs. <?php echo number_format($totalExpenses, 2); ?></small>
                        </div>
                        <div class="icon text-neon-success">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('manage_users') || hasPermission('view_reports')): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/users/index.php" class="stat-box-dark stat-glow-warning">
                        <div class="inner">
                            <h3><?php echo $userCount; ?></h3>
                            <p>Registered Users</p>
                        </div>
                        <div class="icon text-neon-warning">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (hasPermission('manage_events') || hasPermission('view_events') || hasPermission('view_reports')): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="<?php echo hasPermission('manage_events') ? 'modules/events/index.php' : 'modules/events/list.php'; ?>" class="stat-box-dark stat-glow-danger">
                        <div class="inner">
                            <h3><?php echo $eventCount; ?></h3>
                            <p>Active Events</p>
                        </div>
                        <div class="icon text-neon-danger">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Swings Tile for everyone with access to swings/rides -->
                <?php if (hasPermission('manage_swings') || hasPermission('view_swings') || hasPermission('validate_rides') || hasPermission('view_reports')): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/rides/index.php" class="stat-box-dark stat-glow-warning">
                        <div class="inner">
                            <h3>Swings</h3>
                            <p>Status & Details</p>
                        </div>
                        <div class="icon text-neon-warning">
                            <i class="fas fa-horse"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasRole') && hasRole(['superadmin', 'admin', 'cashier'])): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/expenses/index.php" class="stat-box-dark stat-glow-warning">
                        <div class="inner">
                            <h3>Expenses</h3>
                            <p>Daily operational costs</p>
                        </div>
                        <div class="icon text-neon-warning">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (hasRole(['superadmin', 'admin']) || hasPermission('view_reports')): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/passes/index.php" class="stat-box-dark stat-glow-warning">
                        <div class="inner">
                            <h3><?php echo $activePasses; ?></h3>
                            <p>Active Special Passes</p>
                        </div>
                        <div class="icon text-neon-warning">
                            <i class="fas fa-id-card"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <?php if (function_exists('hasRole') && hasRole(['superadmin', 'admin'])): ?>
                <div class="col-lg-4 col-6 mb-4">
                    <a href="modules/hr/index.php" class="stat-box-dark stat-glow-primary">
                        <div class="inner">
                            <h3>HR System</h3>
                            <p>Staff directory & payroll</p>
                        </div>
                        <div class="icon text-neon-primary">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (function_exists('hasRole') && hasRole(['superadmin', 'admin', 'event manager'])): ?>
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow"><i class="fas fa-chart-pie mr-2 text-neon-primary"></i> Event vs Ride Revenue</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="revenuePieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow"><i class="fas fa-chart-bar mr-2 text-neon-success"></i> Revenue (Last 7 Days)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueBarChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            $showEventTicketsChart = (hasPermission('book_tickets') || hasPermission('view_reports')) && !hasRole(['ride operator']);
            $showTopRidesChart = (hasPermission('validate_rides') || hasPermission('manage_swings') || hasPermission('view_swings') || hasPermission('view_reports')) && !hasRole(['user/student']);
            $chartsCount = ($showEventTicketsChart ? 1 : 0) + ($showTopRidesChart ? 1 : 0);
            $chartColClass = ($chartsCount === 1) ? 'col-md-12' : 'col-md-6';
            ?>
            <?php if ($chartsCount > 0): ?>
            <div class="row mt-4">
                <?php if ($showEventTicketsChart): ?>
                <div class="<?php echo $chartColClass; ?> mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow"><i class="fas fa-calendar-alt mr-2 text-neon-info"></i> Tickets Sold per Event</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="eventTicketsBarChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($showTopRidesChart): ?>
                <div class="<?php echo $chartColClass; ?> mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow"><i class="fas fa-horse mr-2 text-neon-warning"></i> Top 5 Used Rides</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="topRidesDoughnutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php
            $showRecentTickets = (hasPermission('book_tickets') || hasPermission('view_reports')) && !hasRole(['ride operator']);
            $showRecentRideUsage = (hasPermission('validate_rides') || hasPermission('manage_swings') || hasPermission('view_swings') || hasPermission('view_reports')) && !hasRole(['user/student']);
            $recentCount = ($showRecentTickets ? 1 : 0) + ($showRecentRideUsage ? 1 : 0);
            $recentColClass = ($recentCount === 1) ? 'col-md-12' : 'col-md-6';
            ?>
            <?php if ($recentCount > 0): ?>
            <div class="row mt-2">
                <?php if ($showRecentTickets): ?>
                <div class="<?php echo $recentColClass; ?> mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow"><i class="fas fa-ticket-alt mr-2 text-neon-info"></i> Recent Event Tickets</h3>
                        </div>
                        <div class="card-body p-0 mt-3 px-3">
                            <div class="table-responsive">
                                <table class="table-dark-custom text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Event</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT t.ticket_code, e.title, t.status, t.created_at 
                                                             FROM tickets t 
                                                             JOIN events e ON t.event_id = e.id 
                                                             ORDER BY t.created_at DESC LIMIT 5");
                                        while($row = $stmt->fetch()) {
                                            $badgeClass = $row['status'] == 'paid' ? 'success' : ($row['status'] == 'cancelled' ? 'danger' : 'warning');
                                            echo "<tr>
                                                    <td class='font-weight-bold'>{$row['ticket_code']}</td>
                                                    <td>{$row['title']}</td>
                                                    <td><span class='badge badge-{$badgeClass} px-2 py-1'>".strtoupper($row['status'])."</span></td>
                                                    <td style='color: #94a3b8;'><i class='far fa-clock mr-1'></i>".date('H:i', strtotime($row['created_at']))."</td>
                                                  </tr>";
                                        }
                                        if ($stmt->rowCount() == 0) {
                                            echo "<tr><td colspan='4' class='text-center py-4' style='color: #94a3b8;'>No tickets found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($showRecentRideUsage): ?>
                <div class="<?php echo $recentColClass; ?> mb-4">
                     <div class="card-dark h-100 p-2">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow"><i class="fas fa-horse mr-2 text-neon-warning"></i> Recent Ride Usage</h3>
                        </div>
                        <div class="card-body p-0 mt-3 px-3">
                            <div class="table-responsive">
                                <table class="table-dark-custom text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Ticket</th>
                                            <th>Ride/Swing</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT rt.ticket_code, s.name, rul.usage_time 
                                                             FROM ride_usage_logs rul 
                                                             JOIN ride_tickets rt ON rul.ride_ticket_id = rt.id 
                                                             JOIN swings s ON rt.swing_id = s.id 
                                                             ORDER BY rul.usage_time DESC LIMIT 5");
                                        while($row = $stmt->fetch()) {
                                            echo "<tr>
                                                    <td class='font-weight-bold'>{$row['ticket_code']}</td>
                                                    <td>{$row['name']}</td>
                                                    <td style='color: #94a3b8;'><i class='far fa-clock mr-1'></i>".date('M d, H:i', strtotime($row['usage_time']))."</td>
                                                  </tr>";
                                        }
                                        if ($stmt->rowCount() == 0) {
                                            echo "<tr><td colspan='3' class='text-center py-4' style='color: #94a3b8;'>No usage logs yet</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Fetch data for charts
// 1. Revenue by Category (Events vs Rides)
$stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE ticket_id IS NOT NULL AND status='completed'");
$eventRev = $stmt->fetchColumn() ?: 0;
$stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE ride_ticket_id IS NOT NULL AND status='completed'");
$rideRev = $stmt->fetchColumn() ?: 0;

// 2. Revenue & Expenses last 7 days
$dates = [];
$daily_rev = [];
$daily_exp = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($date));
    
    // Revenue
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = ? AND status='completed'");
    $stmt->execute([$date]);
    $daily_rev[] = $stmt->fetchColumn() ?: 0;

    // Expenses
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE DATE(expense_date) = ?");
    $stmt->execute([$date]);
    $daily_exp[] = $stmt->fetchColumn() ?: 0;
}

// 3. Tickets Sold per Event
$stmt = $pdo->query("SELECT e.title, COUNT(t.id) as count FROM tickets t JOIN events e ON t.event_id=e.id GROUP BY e.id ORDER BY count DESC LIMIT 5");
$eventNames = [];
$eventTicketCounts = [];
while ($row = $stmt->fetch()) {
    $eventNames[] = $row['title'];
    $eventTicketCounts[] = $row['count'];
}

// 4. Top Used Rides
$stmt = $pdo->query("SELECT s.name, COUNT(rul.id) as count FROM ride_usage_logs rul JOIN ride_tickets rt ON rul.ride_ticket_id = rt.id JOIN swings s ON rt.swing_id = s.id GROUP BY s.id ORDER BY count DESC LIMIT 5");
$rideNames = [];
$rideUsageCounts = [];
while ($row = $stmt->fetch()) {
    $rideNames[] = $row['name'];
    $rideUsageCounts[] = $row['count'];
}
?>

<?php include 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Neon Chart Colors
    const neonCyan = '#22d3ee';
    const neonMagenta = '#f472b6';
    const neonLime = '#a3e635';
    const neonAmber = '#fbbf24';
    const neonPurple = '#c084fc';
    const textDarkGlow = '#cbd5e1';
    const gridDark = 'rgba(255,255,255,0.05)';

    // Global Chart Defaults for Dark Theme
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = textDarkGlow;
        Chart.defaults.font.family = "'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
    }

    // Pie Chart
    var pieChartCanvas = document.getElementById('revenuePieChart');
    if (pieChartCanvas && typeof Chart !== 'undefined') {
        new Chart(pieChartCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Events Revenue', 'Rides Revenue'],
                datasets: [{
                    data: [<?php echo $eventRev; ?>, <?php echo $rideRev; ?>],
                    backgroundColor: [neonCyan, neonMagenta],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: textDarkGlow } }
                },
                cutout: '75%'
            }
        });
    }

    // Bar Chart
    var barChartCanvas = document.getElementById('revenueBarChart');
    if (barChartCanvas && typeof Chart !== 'undefined') {
        new Chart(barChartCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    backgroundColor: neonLime,
                    borderRadius: 4,
                    barPercentage: 0.6,
                    data: <?php echo json_encode($daily_rev); ?>
                }, {
                    label: 'Expenses (Rs.)',
                    backgroundColor: neonMagenta,
                    borderRadius: 4,
                    barPercentage: 0.6,
                    data: <?php echo json_encode($daily_exp); ?>
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { display: true, position: 'top', labels: { color: textDarkGlow } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridDark },
                        border: { display: false },
                        ticks: { color: textDarkGlow }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: textDarkGlow }
                    }
                }
            }
        });
    }

    // Event Tickets Bar Chart
    var eventBarCanvas = document.getElementById('eventTicketsBarChart');
    if (eventBarCanvas && typeof Chart !== 'undefined') {
        new Chart(eventBarCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($eventNames); ?>,
                datasets: [{
                    label: 'Tickets Sold',
                    backgroundColor: neonCyan,
                    borderRadius: 4,
                    barPercentage: 0.6,
                    data: <?php echo json_encode($eventTicketCounts); ?>
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridDark },
                        border: { display: false },
                        ticks: { precision: 0, color: textDarkGlow }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: textDarkGlow }
                    }
                }
            }
        });
    }

    // Top Rides Doughnut
    var ridesDoughnutCanvas = document.getElementById('topRidesDoughnutChart');
    if (ridesDoughnutCanvas && typeof Chart !== 'undefined') {
        new Chart(ridesDoughnutCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($rideNames); ?>,
                datasets: [{
                    data: <?php echo json_encode($rideUsageCounts); ?>,
                    backgroundColor: [neonAmber, neonMagenta, neonLime, neonPurple, neonCyan],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { color: textDarkGlow } } },
                cutout: '75%'
            }
        });
    }
});
</script>
