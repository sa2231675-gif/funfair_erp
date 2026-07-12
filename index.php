<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = "Dashboard";

// ============================================================
// COMMON DATA (for all roles)
// ============================================================
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

// ============================================================
// SUPERADMIN-ONLY EXTENDED DATA
// ============================================================
$isSuperAdmin = function_exists('hasRole') && hasRole(['superadmin']);

if ($isSuperAdmin) {
    // --- Today's Revenue ---
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = ? AND status = 'completed'");
    $stmt->execute([$today]);
    $todayRevenue = $stmt->fetchColumn() ?: 0;

    // --- Yesterday's Revenue (for comparison) ---
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = ? AND status = 'completed'");
    $stmt->execute([$yesterday]);
    $yesterdayRevenue = $stmt->fetchColumn() ?: 0;

    // Revenue change percentage
    $revenueChange = ($yesterdayRevenue > 0) ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1) : ($todayRevenue > 0 ? 100 : 0);

    // --- Today's Tickets ---
    $stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = ? AND status IN ('paid','used')) + (SELECT COUNT(*) FROM ride_tickets WHERE DATE(created_at) = ? AND status IN ('paid','used'))");
    $stmt->execute([$today, $today]);
    $todayTickets = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = ? AND status IN ('paid','used')) + (SELECT COUNT(*) FROM ride_tickets WHERE DATE(created_at) = ? AND status IN ('paid','used'))");
    $stmt->execute([$yesterday, $yesterday]);
    $yesterdayTickets = $stmt->fetchColumn() ?: 0;
    $ticketChange = ($yesterdayTickets > 0) ? round((($todayTickets - $yesterdayTickets) / $yesterdayTickets) * 100, 1) : ($todayTickets > 0 ? 100 : 0);

    // --- Active Rides/Swings ---
    $stmt = $pdo->query("SELECT COUNT(*) FROM swings WHERE status = 'active'");
    $activeRides = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM swings");
    $totalRides = $stmt->fetchColumn();

    // --- Staff on Duty (checked in today) ---
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM employee_shifts WHERE shift_date = ? AND check_in_time IS NOT NULL");
    $stmt->execute([$today]);
    $staffOnDuty = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    $totalStaff = $stmt->fetchColumn();

    // --- Last 7 days sparkline data for stats ---
    $spark_revenue = [];
    $spark_tickets = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = ? AND status='completed'");
        $stmt->execute([$d]);
        $spark_revenue[] = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM tickets WHERE DATE(created_at) = ? AND status IN ('paid','used')) + (SELECT COUNT(*) FROM ride_tickets WHERE DATE(created_at) = ? AND status IN ('paid','used'))");
        $stmt->execute([$d, $d]);
        $spark_tickets[] = (int)($stmt->fetchColumn() ?: 0);
    }

    // --- Revenue by category ---
    $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE ticket_id IS NOT NULL AND status='completed'");
    $eventRev = (float)($stmt->fetchColumn() ?: 0);
    $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE ride_ticket_id IS NOT NULL AND status='completed'");
    $rideRev = (float)($stmt->fetchColumn() ?: 0);
    $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE pass_id IS NOT NULL AND status='completed'");
    $passRev = (float)($stmt->fetchColumn() ?: 0);

    // --- 30-day revenue trend ---
    $trend_dates = [];
    $trend_event_rev = [];
    $trend_ride_rev = [];
    $trend_expenses = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trend_dates[] = date('M d', strtotime($d));

        $stmt = $pdo->prepare("SELECT SUM(p.amount) FROM payments p JOIN tickets t ON p.ticket_id = t.id WHERE DATE(p.created_at) = ? AND p.status='completed'");
        $stmt->execute([$d]);
        $trend_event_rev[] = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare("SELECT SUM(p.amount) FROM payments p JOIN ride_tickets rt ON p.ride_ticket_id = rt.id WHERE DATE(p.created_at) = ? AND p.status='completed'");
        $stmt->execute([$d]);
        $trend_ride_rev[] = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE DATE(expense_date) = ?");
        $stmt->execute([$d]);
        $trend_expenses[] = (float)($stmt->fetchColumn() ?: 0);
    }

    // --- Hourly traffic today ---
    $hourly_labels = [];
    $hourly_data = [];
    for ($h = 8; $h <= 23; $h++) {
        $hourly_labels[] = date('g A', strtotime("$h:00"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE DATE(created_at) = ? AND HOUR(created_at) = ? AND status='completed'");
        $stmt->execute([$today, $h]);
        $hourly_data[] = (int)$stmt->fetchColumn();
    }
    $peakHourIdx = array_search(max($hourly_data), $hourly_data);

    // --- Top Rides ---
    $stmt = $pdo->query("SELECT s.name, COUNT(rul.id) as count FROM ride_usage_logs rul JOIN ride_tickets rt ON rul.ride_ticket_id = rt.id JOIN swings s ON rt.swing_id = s.id GROUP BY s.id ORDER BY count DESC LIMIT 6");
    $topRideNames = [];
    $topRideCounts = [];
    while ($row = $stmt->fetch()) {
        $topRideNames[] = $row['name'];
        $topRideCounts[] = (int)$row['count'];
    }

    // --- Expense Breakdown by Category ---
    $stmt = $pdo->query("SELECT category, SUM(amount) as total FROM expenses GROUP BY category ORDER BY total DESC");
    $expCatNames = [];
    $expCatAmounts = [];
    while ($row = $stmt->fetch()) {
        $expCatNames[] = $row['category'];
        $expCatAmounts[] = (float)$row['total'];
    }

    // --- Recent Payments (last 10) ---
    $stmt = $pdo->query("SELECT p.amount, p.payment_method, p.status, p.created_at,
        CASE 
            WHEN p.ticket_id IS NOT NULL THEN 'Event Ticket'
            WHEN p.ride_ticket_id IS NOT NULL THEN 'Ride Ticket'
            WHEN p.pass_id IS NOT NULL THEN 'Special Pass'
            ELSE 'Other'
        END as type
        FROM payments p ORDER BY p.created_at DESC LIMIT 10");
    $recentPayments = $stmt->fetchAll();

    // --- Staff Attendance Today ---
    $stmt = $pdo->prepare("SELECT e.first_name, e.last_name, e.designation, es.status, es.check_in_time, es.check_out_time, es.start_time
        FROM employee_shifts es 
        JOIN employees e ON es.employee_id = e.id 
        WHERE es.shift_date = ? 
        ORDER BY es.check_in_time DESC
        LIMIT 10");
    $stmt->execute([$today]);
    $staffAttendance = $stmt->fetchAll();

    // --- Live Activity Feed (combined recent activities) ---
    $feedItems = [];

    // Recent ticket sales
    $stmt = $pdo->query("SELECT 'ticket' as type, t.ticket_code as code, e.title as name, p.amount, t.created_at 
        FROM tickets t JOIN events e ON t.event_id = e.id JOIN payments p ON p.ticket_id = t.id 
        WHERE p.status = 'completed'
        ORDER BY t.created_at DESC LIMIT 5");
    while ($row = $stmt->fetch()) $feedItems[] = $row;

    // Recent ride usage
    $stmt = $pdo->query("SELECT 'ride' as type, rt.ticket_code as code, s.name, p.amount, rt.created_at
        FROM ride_tickets rt JOIN swings s ON rt.swing_id = s.id JOIN payments p ON p.ride_ticket_id = rt.id
        WHERE p.status = 'completed'
        ORDER BY rt.created_at DESC LIMIT 5");
    while ($row = $stmt->fetch()) $feedItems[] = $row;

    // Recent expenses
    $stmt = $pdo->query("SELECT 'expense' as type, '' as code, category as name, amount, expense_date as created_at
        FROM expenses ORDER BY created_at DESC LIMIT 3");
    while ($row = $stmt->fetch()) $feedItems[] = $row;

    // Recent staff check-ins
    $stmt = $pdo->prepare("SELECT 'staff' as type, '' as code, CONCAT(e.first_name, ' ', e.last_name) as name, 0 as amount, es.check_in_time as created_at
        FROM employee_shifts es JOIN employees e ON es.employee_id = e.id
        WHERE es.shift_date = ? AND es.check_in_time IS NOT NULL
        ORDER BY es.check_in_time DESC LIMIT 3");
    $stmt->execute([$today]);
    while ($row = $stmt->fetch()) $feedItems[] = $row;

    // Sort all feed items by time
    usort($feedItems, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $feedItems = array_slice($feedItems, 0, 15);

    // Monthly revenue goal (assume 500k target)
    $monthlyGoal = 500000;
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND status = 'completed'");
    $stmt->execute();
    $monthlyRevenue = (float)($stmt->fetchColumn() ?: 0);
    $goalPercent = min(100, round(($monthlyRevenue / $monthlyGoal) * 100, 1));
}

include 'includes/header.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php if ($isSuperAdmin): ?>
    <!-- ============================================================ -->
    <!-- 🎪 SUPER ADMIN PREMIUM FUNFAIR DASHBOARD -->
    <!-- ============================================================ -->
    <div class="content pt-3">
        <div class="container-fluid px-4">

            <!-- ===== HERO WELCOME SECTION ===== -->
            <div class="dashboard-hero">
                <div class="dashboard-particles">
                    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
                    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
                    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
                    <div class="particle"></div>
                </div>
                <div class="dashboard-hero-content">
                    <div>
                        <h1>
                            <span class="emoji-wave">🎪</span> Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!
                        </h1>
                        <p class="hero-subtitle">
                            <i class="fas fa-crown" style="color: #fbbf24; margin-right: 6px;"></i>
                            Super Admin Control Center — FunFair ERP
                        </p>
                        <div class="hero-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('l, F j, Y — g:i A'); ?>
                        </div>
                    </div>
                    <div class="hero-live-stats">
                        <div class="hero-live-stat">
                            <span class="stat-val"><span class="pulse-dot"></span>Online</span>
                            <span class="stat-lbl">System Status</span>
                        </div>
                        <div class="hero-live-stat">
                            <span class="stat-val">Rs. <?php echo number_format($todayRevenue); ?></span>
                            <span class="stat-lbl">Today's Revenue</span>
                        </div>
                        <div class="hero-live-stat">
                            <span class="stat-val"><?php echo $staffOnDuty; ?>/<?php echo $totalStaff; ?></span>
                            <span class="stat-lbl">Staff on Duty</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION: KEY METRICS ===== -->
            <div class="dashboard-section-title">
                <i class="fas fa-chart-pie" style="color: #22d3ee;"></i> Key Metrics
            </div>

            <!-- ===== 6 PREMIUM STAT CARDS ===== -->
            <div class="row">
                <!-- Card 1: Today's Revenue -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-4">
                    <a href="modules/reports/revenue.php" class="dashboard-stat-card card-cyan">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fas fa-coins icon-neon-pulse"></i></div>
                            <div class="sparkline-container"><canvas id="sparkRevenue"></canvas></div>
                        </div>
                        <div class="stat-number">Rs. <?php echo number_format($todayRevenue); ?></div>
                        <div class="stat-label">Today's Revenue</div>
                        <div class="stat-bottom">
                            <div class="stat-change <?php echo $revenueChange >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-arrow-<?php echo $revenueChange >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($revenueChange); ?>% vs yesterday
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 2: Total Tickets Sold -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-4">
                    <a href="modules/tickets/index.php" class="dashboard-stat-card card-purple">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fas fa-ticket-alt icon-neon-pulse"></i></div>
                            <div class="sparkline-container"><canvas id="sparkTickets"></canvas></div>
                        </div>
                        <div class="stat-number"><?php echo number_format($ticketSales); ?></div>
                        <div class="stat-label">Total Tickets</div>
                        <div class="stat-bottom">
                            <div class="stat-change <?php echo $ticketChange >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-arrow-<?php echo $ticketChange >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($ticketChange); ?>% today
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 3: Net Profit -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-4">
                    <a href="modules/reports/revenue.php" class="dashboard-stat-card card-green">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fas fa-chart-line icon-neon-pulse"></i></div>
                        </div>
                        <div class="stat-number"><?php echo $netProfit >= 0 ? 'Rs. ' . number_format($netProfit) : '-Rs. ' . number_format(abs($netProfit)); ?></div>
                        <div class="stat-label">Net Profit</div>
                        <div class="stat-bottom">
                            <div class="stat-change <?php echo $netProfit >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-<?php echo $netProfit >= 0 ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                <?php echo $netProfit >= 0 ? 'Profitable' : 'In Loss'; ?>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 4: Active Rides -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-4">
                    <a href="modules/rides/index.php" class="dashboard-stat-card card-amber">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fas fa-dharmachakra icon-ferris"></i></div>
                        </div>
                        <div class="stat-number"><?php echo $activeRides; ?>/<?php echo $totalRides; ?></div>
                        <div class="stat-label">Active Rides</div>
                        <div class="stat-bottom">
                            <div class="stat-change up">
                                <i class="fas fa-horse"></i> Swings Running
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 5: Staff on Duty -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-4">
                    <a href="modules/hr/shifts.php" class="dashboard-stat-card card-pink">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fas fa-user-clock icon-neon-pulse"></i></div>
                        </div>
                        <div class="stat-number"><?php echo $staffOnDuty; ?></div>
                        <div class="stat-label">Staff on Duty</div>
                        <div class="stat-bottom">
                            <div class="stat-change neutral">
                                <i class="fas fa-users"></i> of <?php echo $totalStaff; ?> total
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Card 6: Active Passes -->
                <div class="col-xl-2 col-lg-4 col-md-4 col-6 mb-4">
                    <a href="modules/passes/index.php" class="dashboard-stat-card card-orange">
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fas fa-id-badge icon-neon-pulse"></i></div>
                        </div>
                        <div class="stat-number"><?php echo $activePasses; ?></div>
                        <div class="stat-label">Active Passes</div>
                        <div class="stat-bottom">
                            <div class="stat-change neutral">
                                <i class="fas fa-star"></i> Special Passes
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- ===== SECTION: ANALYTICS ===== -->
            <div class="dashboard-section-title">
                <i class="fas fa-chart-area" style="color: #a78bfa;"></i> Analytics & Insights
            </div>

            <!-- ===== ROW 2: MAIN CHARTS ===== -->
            <div class="row">
                <!-- Chart 1: 30-Day Revenue Trend -->
                <div class="col-lg-8 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-chart-area" style="color: #22d3ee;"></i> Revenue Trend (30 Days)
                            </h3>
                            <div class="chart-legend-custom">
                                <span><span class="dot" style="background: #22d3ee;"></span> Events</span>
                                <span><span class="dot" style="background: #a78bfa;"></span> Rides</span>
                                <span><span class="dot" style="background: #fb923c;"></span> Expenses</span>
                            </div>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="revenueTrendChart" style="height: 320px; max-height: 320px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Chart 2: Revenue Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-chart-pie" style="color: #f472b6;"></i> Revenue Split
                            </h3>
                        </div>
                        <div class="chart-card-body d-flex align-items-center justify-content-center">
                            <canvas id="revenueDoughnutChart" style="max-height: 280px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ROW 3: Hourly Traffic + Live Feed + Top Rides ===== -->
            <div class="row">
                <!-- Hourly Traffic -->
                <div class="col-lg-5 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-clock" style="color: #fbbf24;"></i> Hourly Traffic Today
                            </h3>
                            <span class="badge-sm badge-completed">Peak: <?php echo $hourly_labels[$peakHourIdx] ?? 'N/A'; ?></span>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="hourlyTrafficChart" style="height: 280px; max-height: 280px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Live Activity Feed -->
                <div class="col-lg-4 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-bolt" style="color: #fbbf24;"></i> Live Activity Feed
                            </h3>
                            <span style="font-size: 0.7rem; color: #64748b;"><span class="pulse-dot" style="width:6px;height:6px;display:inline-block;border-radius:50%;background:#22c55e;animation:pulseDot 2s infinite;vertical-align:middle;margin-right:4px;"></span> Live</span>
                        </div>
                        <div class="chart-card-body p-0">
                            <div class="dashboard-feed" style="padding: 12px 16px;">
                                <?php if (empty($feedItems)): ?>
                                    <div class="text-center py-5" style="color: #64748b;">
                                        <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                        No recent activity
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($feedItems as $fi): ?>
                                        <?php
                                        $iconClass = 'feed-ticket';
                                        $iconFA = 'fa-ticket-alt';
                                        if ($fi['type'] === 'ride') { $iconClass = 'feed-ride'; $iconFA = 'fa-horse'; }
                                        elseif ($fi['type'] === 'expense') { $iconClass = 'feed-expense'; $iconFA = 'fa-receipt'; }
                                        elseif ($fi['type'] === 'staff') { $iconClass = 'feed-staff'; $iconFA = 'fa-user-check'; }
                                        elseif ($fi['type'] === 'pass') { $iconClass = 'feed-pass'; $iconFA = 'fa-id-badge'; }
                                        ?>
                                        <div class="feed-item">
                                            <div class="feed-icon <?php echo $iconClass; ?>">
                                                <i class="fas <?php echo $iconFA; ?>"></i>
                                            </div>
                                            <div class="feed-text">
                                                <div class="feed-title">
                                                    <?php 
                                                    if ($fi['type'] === 'ticket') echo 'Event Ticket — ' . htmlspecialchars($fi['name']);
                                                    elseif ($fi['type'] === 'ride') echo 'Ride — ' . htmlspecialchars($fi['name']);
                                                    elseif ($fi['type'] === 'expense') echo 'Expense — ' . htmlspecialchars($fi['name']);
                                                    elseif ($fi['type'] === 'staff') echo 'Check-in — ' . htmlspecialchars($fi['name']);
                                                    ?>
                                                </div>
                                                <div class="feed-meta">
                                                    <i class="far fa-clock"></i>
                                                    <?php echo date('M d, g:i A', strtotime($fi['created_at'])); ?>
                                                    <?php if ($fi['code']): ?>
                                                        &nbsp;·&nbsp;<span style="color: #94a3b8;"><?php echo htmlspecialchars($fi['code']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($fi['amount'] > 0): ?>
                                                <div class="feed-amount <?php echo $fi['type'] === 'expense' ? 'expense-amount' : ''; ?>">
                                                    <?php echo $fi['type'] === 'expense' ? '-' : '+'; ?>Rs. <?php echo number_format($fi['amount']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expense Breakdown -->
                <div class="col-lg-3 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-receipt" style="color: #fb923c;"></i> Expense Split
                            </h3>
                        </div>
                        <div class="chart-card-body d-flex align-items-center justify-content-center">
                            <canvas id="expensePieChart" style="max-height: 250px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SECTION: DATA TABLES ===== -->
            <div class="dashboard-section-title">
                <i class="fas fa-table" style="color: #34d399;"></i> Recent Data
            </div>

            <!-- ===== ROW 4: TABLES ===== -->
            <div class="row">
                <!-- Recent Transactions -->
                <div class="col-lg-7 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-exchange-alt" style="color: #22d3ee;"></i> Recent Transactions
                            </h3>
                        </div>
                        <div class="chart-card-body p-0">
                            <div class="table-responsive">
                                <table class="dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentPayments)): ?>
                                            <tr><td colspan="5" class="text-center py-4" style="color: #64748b;">No transactions found</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($recentPayments as $pay): ?>
                                                <?php
                                                $typeBadge = 'badge-event-type';
                                                if (strpos($pay['type'], 'Ride') !== false) $typeBadge = 'badge-ride-type';
                                                elseif (strpos($pay['type'], 'Pass') !== false) $typeBadge = 'badge-pass-type';
                                                $statusBadge = $pay['status'] === 'completed' ? 'badge-completed' : 'badge-pending';
                                                ?>
                                                <tr>
                                                    <td><span class="badge-sm <?php echo $typeBadge; ?>"><?php echo $pay['type']; ?></span></td>
                                                    <td style="font-weight: 700; color: #34d399;">Rs. <?php echo number_format($pay['amount']); ?></td>
                                                    <td style="text-transform: capitalize;"><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                                                    <td><span class="badge-sm <?php echo $statusBadge; ?>"><?php echo ucfirst($pay['status']); ?></span></td>
                                                    <td style="color: #64748b; font-size: 0.78rem;">
                                                        <i class="far fa-clock mr-1"></i><?php echo date('M d, g:i A', strtotime($pay['created_at'])); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Attendance Today -->
                <div class="col-lg-5 mb-4">
                    <div class="dashboard-chart-card h-100">
                        <div class="chart-card-header">
                            <h3 class="chart-card-title">
                                <i class="fas fa-user-check" style="color: #a78bfa;"></i> Staff Attendance Today
                            </h3>
                        </div>
                        <div class="chart-card-body p-0">
                            <div class="table-responsive">
                                <table class="dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Check-in</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($staffAttendance)): ?>
                                            <tr><td colspan="4" class="text-center py-4" style="color: #64748b;">No shifts assigned today</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($staffAttendance as $sa): ?>
                                                <?php
                                                $attStatus = 'Scheduled';
                                                $attBadge = 'badge-scheduled';
                                                if ($sa['check_in_time']) {
                                                    $scheduledTime = strtotime($today . ' ' . $sa['start_time']);
                                                    $checkInTime = strtotime($sa['check_in_time']);
                                                    if ($checkInTime > $scheduledTime + 900) {
                                                        $attStatus = 'Late';
                                                        $attBadge = 'badge-late';
                                                    } else {
                                                        $attStatus = 'Present';
                                                        $attBadge = 'badge-present';
                                                    }
                                                } else {
                                                    if (strtotime($today . ' ' . $sa['start_time']) < time()) {
                                                        $attStatus = 'Absent';
                                                        $attBadge = 'badge-absent';
                                                    }
                                                }
                                                ?>
                                                <tr>
                                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($sa['first_name'] . ' ' . $sa['last_name']); ?></td>
                                                    <td style="color: #94a3b8; font-size: 0.78rem;"><?php echo htmlspecialchars($sa['designation']); ?></td>
                                                    <td><span class="badge-sm <?php echo $attBadge; ?>"><?php echo $attStatus; ?></span></td>
                                                    <td style="color: #64748b; font-size: 0.78rem;">
                                                        <?php echo $sa['check_in_time'] ? date('g:i A', strtotime($sa['check_in_time'])) : '—'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== QUICK ACTIONS + REVENUE GOAL ===== -->
            <div class="dashboard-quick-actions">
                <div class="revenue-progress-wrap">
                    <div class="progress-label">
                        <span><i class="fas fa-bullseye" style="color: #a78bfa; margin-right: 4px;"></i> Monthly Revenue Goal</span>
                        <span>Rs. <?php echo number_format($monthlyRevenue); ?> / Rs. <?php echo number_format($monthlyGoal); ?> (<?php echo $goalPercent; ?>%)</span>
                    </div>
                    <div class="revenue-progress-bar">
                        <div class="progress-fill" style="width: <?php echo $goalPercent; ?>%;"></div>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap" style="gap: 10px;">
                    <a href="modules/events/index.php" class="quick-action-btn qa-event">
                        <i class="fas fa-calendar-plus"></i> Add Event
                    </a>
                    <a href="modules/admission/buy.php" class="quick-action-btn qa-ticket">
                        <i class="fas fa-ticket-alt"></i> Sell Ticket
                    </a>
                    <a href="modules/expenses/add.php" class="quick-action-btn qa-expense">
                        <i class="fas fa-receipt"></i> Record Expense
                    </a>
                    <a href="modules/reports/revenue.php" class="quick-action-btn qa-report">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </div>
            </div>

        </div>
    </div>

    <?php else: ?>
    <!-- ============================================================ -->
    <!-- STANDARD DASHBOARD FOR OTHER ROLES -->
    <!-- ============================================================ -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow">Dashboard Overview</h1>
                </div>
            </div>
        </div>
    </div>

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
            <?php
            // Chart data for non-superadmin roles
            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE ticket_id IS NOT NULL AND status='completed'");
            $eventRev = $stmt->fetchColumn() ?: 0;
            $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE ride_ticket_id IS NOT NULL AND status='completed'");
            $rideRev = $stmt->fetchColumn() ?: 0;

            $dates = [];
            $daily_rev = [];
            $daily_exp = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dates[] = date('M d', strtotime($date));
                $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE DATE(created_at) = ? AND status='completed'");
                $stmt->execute([$date]);
                $daily_rev[] = $stmt->fetchColumn() ?: 0;
                $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE DATE(expense_date) = ?");
                $stmt->execute([$date]);
                $daily_exp[] = $stmt->fetchColumn() ?: 0;
            }

            $stmt = $pdo->query("SELECT e.title, COUNT(t.id) as count FROM tickets t JOIN events e ON t.event_id=e.id GROUP BY e.id ORDER BY count DESC LIMIT 5");
            $eventNames = [];
            $eventTicketCounts = [];
            while ($row = $stmt->fetch()) {
                $eventNames[] = $row['title'];
                $eventTicketCounts[] = $row['count'];
            }

            $stmt = $pdo->query("SELECT s.name, COUNT(rul.id) as count FROM ride_usage_logs rul JOIN ride_tickets rt ON rul.ride_ticket_id = rt.id JOIN swings s ON rt.swing_id = s.id GROUP BY s.id ORDER BY count DESC LIMIT 5");
            $rideNames = [];
            $rideUsageCounts = [];
            while ($row = $stmt->fetch()) {
                $rideNames[] = $row['name'];
                $rideUsageCounts[] = $row['count'];
            }
            ?>
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0 d-flex align-items-center justify-content-between">
                            <h3 class="card-title text-glow mb-0"><i class="fas fa-chart-line mr-2 text-neon-success"></i> Revenue & Financial Overview</h3>
                        </div>
                        <div class="card-body" style="position:relative;">
                            <canvas id="combinedRevenueChart" style="min-height:300px;height:300px;max-height:300px;max-width:100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card-dark h-100 p-2">
                        <div class="card-header border-0 d-flex align-items-center justify-content-between">
                            <h3 class="card-title text-glow mb-0"><i class="fas fa-chart-bar mr-2 text-neon-info"></i> Tickets & Rides Activity</h3>
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
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<?php if ($isSuperAdmin): ?>
<!-- ============================================================ -->
<!-- SUPERADMIN CHART SCRIPTS -->
<!-- ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const neonCyan    = '#22d3ee';
    const neonPurple  = '#a78bfa';
    const neonPink    = '#f472b6';
    const neonAmber   = '#fbbf24';
    const neonOrange  = '#fb923c';
    const neonGreen   = '#34d399';
    const neonRed     = '#f87171';
    const textColor   = '#94a3b8';
    const gridColor   = 'rgba(255,255,255,0.04)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, Arial, sans-serif";
    Chart.defaults.font.size = 11;

    // ============================
    // SPARKLINE: Revenue (7 days)
    // ============================
    var sparkRevEl = document.getElementById('sparkRevenue');
    if (sparkRevEl) {
        new Chart(sparkRevEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['','','','','','',''],
                datasets: [{
                    data: <?php echo json_encode($spark_revenue); ?>,
                    borderColor: neonCyan,
                    borderWidth: 2,
                    fill: true,
                    backgroundColor: 'rgba(34,211,238,0.08)',
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });
    }

    // ============================
    // SPARKLINE: Tickets (7 days)
    // ============================
    var sparkTickEl = document.getElementById('sparkTickets');
    if (sparkTickEl) {
        new Chart(sparkTickEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['','','','','','',''],
                datasets: [{
                    data: <?php echo json_encode($spark_tickets); ?>,
                    borderColor: neonPurple,
                    borderWidth: 2,
                    fill: true,
                    backgroundColor: 'rgba(167,139,250,0.08)',
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });
    }

    // ============================
    // CHART: 30-Day Revenue Trend
    // ============================
    var trendEl = document.getElementById('revenueTrendChart');
    if (trendEl) {
        var ctx = trendEl.getContext('2d');
        var gradCyan = ctx.createLinearGradient(0, 0, 0, 320);
        gradCyan.addColorStop(0, 'rgba(34,211,238,0.25)');
        gradCyan.addColorStop(1, 'rgba(34,211,238,0)');
        var gradPurple = ctx.createLinearGradient(0, 0, 0, 320);
        gradPurple.addColorStop(0, 'rgba(167,139,250,0.25)');
        gradPurple.addColorStop(1, 'rgba(167,139,250,0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_dates); ?>,
                datasets: [
                    {
                        label: 'Events Revenue',
                        data: <?php echo json_encode($trend_event_rev); ?>,
                        borderColor: neonCyan,
                        backgroundColor: gradCyan,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: neonCyan
                    },
                    {
                        label: 'Rides Revenue',
                        data: <?php echo json_encode($trend_ride_rev); ?>,
                        borderColor: neonPurple,
                        backgroundColor: gradPurple,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: neonPurple
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode($trend_expenses); ?>,
                        borderColor: neonOrange,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [6, 4],
                        fill: false,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: neonOrange
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)',
                        borderColor: 'rgba(56,189,248,0.2)',
                        borderWidth: 1,
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.dataset.label + ': Rs. ' + ctx.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, maxRotation: 45, font: { size: 9 }, maxTicksLimit: 10 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, callback: function(v) { return 'Rs.' + (v/1000).toFixed(0) + 'k'; } }
                    }
                }
            }
        });
    }

    // ============================
    // CHART: Revenue Doughnut
    // ============================
    var doughEl = document.getElementById('revenueDoughnutChart');
    if (doughEl) {
        new Chart(doughEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Events', 'Rides', 'Passes'],
                datasets: [{
                    data: [<?php echo $eventRev; ?>, <?php echo $rideRev; ?>, <?php echo $passRev; ?>],
                    backgroundColor: [neonCyan, neonPurple, neonGreen],
                    borderColor: 'rgba(15,23,42,0.9)',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: textColor, padding: 16, usePointStyle: true, pointStyleWidth: 10, font: { size: 11 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)',
                        borderColor: 'rgba(56,189,248,0.2)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': Rs. ' + ctx.parsed.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================
    // CHART: Hourly Traffic
    // ============================
    var hourlyEl = document.getElementById('hourlyTrafficChart');
    if (hourlyEl) {
        var hourlyData = <?php echo json_encode($hourly_data); ?>;
        var peakIdx = <?php echo $peakHourIdx; ?>;
        var barColors = hourlyData.map(function(v, i) {
            return i === peakIdx ? neonAmber : 'rgba(34,211,238,0.5)';
        });

        new Chart(hourlyEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($hourly_labels); ?>,
                datasets: [{
                    label: 'Transactions',
                    data: hourlyData,
                    backgroundColor: barColors,
                    borderRadius: 6,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)',
                        borderColor: 'rgba(56,189,248,0.2)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 10
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 9 }, maxRotation: 45 }
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

    // ============================
    // CHART: Expense Breakdown Pie
    // ============================
    var expPieEl = document.getElementById('expensePieChart');
    if (expPieEl) {
        var expColors = [neonOrange, neonPink, neonCyan, neonAmber, neonPurple, neonGreen, neonRed];
        new Chart(expPieEl.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($expCatNames); ?>,
                datasets: [{
                    data: <?php echo json_encode($expCatAmounts); ?>,
                    backgroundColor: expColors.slice(0, <?php echo count($expCatNames); ?>),
                    borderColor: 'rgba(15,23,42,0.9)',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '55%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: textColor, padding: 10, usePointStyle: true, pointStyleWidth: 8, font: { size: 10 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.95)',
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.label + ': Rs. ' + ctx.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // ============================
    // Animate progress bar on load
    // ============================
    setTimeout(function() {
        var progressFill = document.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = progressFill.style.width; // trigger reflow
        }
    }, 300);
});
</script>

<?php else: ?>
<!-- ============================================================ -->
<!-- STANDARD ROLE CHART SCRIPTS -->
<!-- ============================================================ -->
<?php if (function_exists('hasRole') && hasRole(['admin', 'event manager'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const neonCyan    = '#22d3ee';
    const neonMagenta = '#f472b6';
    const neonAmber   = '#fbbf24';
    const neonOrange  = '#fb923c';
    const textColor   = '#cbd5e1';
    const gridColor   = 'rgba(255,255,255,0.05)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Segoe UI', Roboto, Arial, sans-serif";

    var c1 = document.getElementById('combinedRevenueChart');
    if (c1) {
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

    var c2 = document.getElementById('combinedActivityChart');
    if (c2) {
        var eventNames  = <?php echo json_encode(array_values($eventNames)); ?>;
        var eventCounts = <?php echo json_encode(array_values($eventTicketCounts)); ?>;
        var rideNames   = <?php echo json_encode(array_values($rideNames)); ?>;
        var rideCounts  = <?php echo json_encode(array_values($rideUsageCounts)); ?>;

        var allLabels = [];
        var maxLen = Math.max(eventNames.length, rideNames.length);
        for (var i = 0; i < maxLen; i++) {
            var el = eventNames[i] ? eventNames[i].substring(0,14) + (eventNames[i].length>14?'…':'') : '';
            var rl = rideNames[i]  ? rideNames[i].substring(0,14)  + (rideNames[i].length>14?'…':'')  : '';
            allLabels.push('E: ' + (el||'—') + ' / R: ' + (rl||'—'));
        }

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
                        ticks: { color: textColor, maxRotation: 30, font: { size: 10 } }
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
<?php endif; ?>
<?php endif; ?>
