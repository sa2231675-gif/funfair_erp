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

                <!-- CHART 1: Revenue & Expenses Trend + Event vs Ride Split -->
                <div class="col-md-6 mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0 d-flex align-items-center justify-content-between">
                            <h3 class="card-title text-glow mb-0"><i class="fas fa-chart-line mr-2 text-neon-success"></i> Revenue & Financial Overview</h3>
                            <div style="display:flex;gap:10px;font-size:0.75rem;">
                                <span style="color:#22d3ee"><i class="fas fa-circle mr-1"></i>Events Rev</span>
                                <span style="color:#f472b6"><i class="fas fa-circle mr-1"></i>Rides Rev</span>
                                <span style="color:#fb923c"><i class="fas fa-circle mr-1"></i>Expenses</span>
                            </div>
                        </div>
                        <div class="card-body" style="position:relative;">
                            <canvas id="combinedRevenueChart" style="min-height:300px;height:300px;max-height:300px;max-width:100%;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- CHART 2: Tickets per Event + Top Rides Usage -->
                <div class="col-md-6 mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0 d-flex align-items-center justify-content-between">
                            <h3 class="card-title text-glow mb-0"><i class="fas fa-chart-bar mr-2 text-neon-info"></i> Tickets & Rides Activity</h3>
                            <div style="display:flex;gap:10px;font-size:0.75rem;">
                                <span style="color:#22d3ee"><i class="fas fa-circle mr-1"></i>Event Tickets</span>
                                <span style="color:#fbbf24"><i class="fas fa-circle mr-1"></i>Ride Usage</span>
                            </div>
                        </div>
                        <div class="card-body" style="position:relative;">
                            <canvas id="combinedActivityChart" style="min-height:300px;height:300px;max-height:300px;max-width:100%;"></canvas>
                        </div>
                    </div>
                </div>

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
    if (typeof Chart === 'undefined') return;

    // === Neon Colors ===
    const neonCyan    = '#22d3ee';
    const neonMagenta = '#f472b6';
    const neonAmber   = '#fbbf24';
    const neonLime    = '#a3e635';
    const neonOrange  = '#fb923c';
    const neonPurple  = '#c084fc';
    const neonGreen   = '#34d399';
    const textColor   = '#cbd5e1';
    const gridColor   = 'rgba(255,255,255,0.05)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";

    // =============================================
    // CHART 1: Combined Revenue & Financial Chart
    // Stacked bar: Daily Event Revenue + Rides Revenue + Expenses Line
    // =============================================
    var c1 = document.getElementById('combinedRevenueChart');
    if (c1) {
        // We'll show 3 datasets on same axes:
        // Bar1 = Daily Revenue breakdown (Event Rev portion)
        // Bar2 = Rides Revenue portion
        // Line = Expenses
        // We already have $daily_rev (total), $daily_exp, $eventRev, $rideRev (totals)
        // For daily split: approximate event/ride ratio applied to daily rev
        var totalRev = <?php echo ($eventRev + $rideRev) ?: 1; ?>;
        var eventRatio = <?php echo ($eventRev + $rideRev) > 0 ? round($eventRev / ($eventRev + $rideRev), 4) : 0.5; ?>;
        var rideRatio  = 1 - eventRatio;

        var dailyRevRaw = <?php echo json_encode($daily_rev); ?>;
        var dailyExpRaw = <?php echo json_encode($daily_exp); ?>;
        var dailyEventRev = dailyRevRaw.map(function(v){ return +(v * eventRatio).toFixed(0); });
        var dailyRideRev  = dailyRevRaw.map(function(v){ return +(v * rideRatio).toFixed(0); });

        new Chart(c1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Events Revenue (Rs.)',
                        data: dailyEventRev,
                        backgroundColor: neonCyan,
                        borderRadius: 5,
                        stack: 'revenue',
                        barPercentage: 0.65,
                        order: 2
                    },
                    {
                        label: 'Rides Revenue (Rs.)',
                        data: dailyRideRev,
                        backgroundColor: neonMagenta,
                        borderRadius: 5,
                        stack: 'revenue',
                        barPercentage: 0.65,
                        order: 2
                    },
                    {
                        label: 'Expenses (Rs.)',
                        data: dailyExpRaw,
                        type: 'line',
                        borderColor: neonOrange,
                        backgroundColor: 'rgba(251,146,60,0.12)',
                        borderWidth: 2.5,
                        pointBackgroundColor: neonOrange,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4,
                        order: 1
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: textColor, boxWidth: 12, padding: 14 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.dataset.label + ': Rs. ' + ctx.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: textColor } },
                    y: { stacked: true, beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, callback: function(v){ return 'Rs.'+v.toLocaleString(); } } }
                }
            }
        });
    }

    // =============================================
    // CHART 2: Tickets per Event + Top Rides Usage
    // Combined grouped bar — shared label = top 5 names (events + rides merged)
    // =============================================
    var c2 = document.getElementById('combinedActivityChart');
    if (c2) {
        var eventNames  = <?php echo json_encode(array_values($eventNames)); ?>;
        var eventCounts = <?php echo json_encode(array_values($eventTicketCounts)); ?>;
        var rideNames   = <?php echo json_encode(array_values($rideNames)); ?>;
        var rideCounts  = <?php echo json_encode(array_values($rideUsageCounts)); ?>;

        // Merge all labels (max 5 each), pad shorter arrays with nulls
        var allLabels = [];
        var maxLen = Math.max(eventNames.length, rideNames.length);
        for (var i = 0; i < maxLen; i++) {
            var el = eventNames[i] ? eventNames[i].substring(0,14) + (eventNames[i].length>14?'…':'') : '';
            var rl = rideNames[i]  ? rideNames[i].substring(0,14)  + (rideNames[i].length>14?'…':'')  : '';
            allLabels.push('E: ' + (el||'—') + ' / R: ' + (rl||'—'));
        }

        // Pad arrays to same length
        while (eventCounts.length < maxLen) eventCounts.push(0);
        while (rideCounts.length  < maxLen) rideCounts.push(0);

        new Chart(c2.getContext('2d'), {
            type: 'bar',
            data: {
                labels: allLabels.length > 0 ? allLabels : ['No Data'],
                datasets: [
                    {
                        label: 'Event Tickets Sold',
                        data: eventCounts,
                        backgroundColor: neonCyan,
                        borderRadius: 5,
                        barPercentage: 0.4,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Ride Usage Count',
                        data: rideCounts,
                        backgroundColor: neonAmber,
                        borderRadius: 5,
                        barPercentage: 0.4,
                        categoryPercentage: 0.75
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: textColor, boxWidth: 12, padding: 14 }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                // Show full names in tooltip
                                var idx = items[0].dataIndex;
                                var e = eventNames[idx] || 'N/A';
                                var r = rideNames[idx]  || 'N/A';
                                return 'Event: ' + e + '\nRide: ' + r;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: textColor,
                            maxRotation: 30,
                            font: { size: 10 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { precision: 0, color: textColor }
                    }
                }
            }
        });
    }
});
</script>
