<?php
// Detect current page URL for active state
$currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
function isActive($path) {
    global $currentUrl;
    return strpos($currentUrl, $path) !== false;
}
function isGroupActive($paths) {
    foreach ($paths as $p) {
        if (isActive($p)) return true;
    }
    return false;
}
?>

<style>
/* ===== ACCORDION SIDEBAR STYLES ===== */
.main-sidebar { background: #0d0d1a !important; border-right: 1px solid rgba(56,189,248,0.1); }

.sidebar { padding-bottom: 20px; }

/* User Panel */
.user-panel-custom {
    padding: 20px 15px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    margin-bottom: 8px;
    text-align: center;
}
.user-panel-custom .user-avatar {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #0ea5e9, #818cf8);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 10px;
    box-shadow: 0 0 18px rgba(56,189,248,0.35);
}
.user-panel-custom .user-name {
    font-size: 0.95rem; font-weight: 700; color: #f1f5f9; letter-spacing: 0.5px;
}
.user-panel-custom .user-role {
    font-size: 0.72rem; color: #38bdf8; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase; margin-top: 3px;
}

/* Nav wrapper */
.sidebar-nav { list-style: none; margin: 0; padding: 6px 0; }

/* Dashboard link (standalone) */
.sidebar-nav .nav-standalone > a {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 18px; color: #94a3b8;
    text-decoration: none; font-size: 0.88rem; font-weight: 500;
    border-radius: 8px; margin: 2px 10px;
    transition: all 0.22s ease;
}
.sidebar-nav .nav-standalone > a:hover,
.sidebar-nav .nav-standalone > a.active {
    background: rgba(56,189,248,0.12);
    color: #38bdf8;
}
.sidebar-nav .nav-standalone > a .nav-si {
    width: 22px; text-align: center; font-size: 0.95rem;
}

/* === ACCORDION PARENT ITEM === */
.acc-item { margin: 3px 10px; border-radius: 10px; overflow: hidden; }

.acc-header {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; cursor: pointer;
    color: #94a3b8; font-size: 0.88rem; font-weight: 600;
    border-radius: 10px;
    transition: all 0.22s ease;
    user-select: none; position: relative;
}
.acc-header:hover { background: rgba(255,255,255,0.05); color: #cbd5e1; }
.acc-header.open, .acc-header.group-active {
    background: rgba(56,189,248,0.10);
    color: #38bdf8;
}
.acc-header .acc-icon { width: 22px; text-align: center; font-size: 1rem; flex-shrink: 0; }
.acc-header .acc-label { flex: 1; }
.acc-header .acc-arrow {
    font-size: 0.72rem; color: #64748b;
    transition: transform 0.3s ease;
    margin-left: auto;
}
.acc-header.open .acc-arrow { transform: rotate(-90deg); color: #38bdf8; }

/* === SUB ITEMS === */
.acc-body {
    display: none;
    background: rgba(0,0,0,0.2);
    border-radius: 0 0 10px 10px;
    padding: 4px 0 6px;
    border-top: 1px solid rgba(255,255,255,0.04);
}
.acc-body.show { display: block; }

.acc-body a {
    display: flex; align-items: center; gap: 9px;
    padding: 8px 14px 8px 20px;
    color: #94a3b8; text-decoration: none;
    font-size: 0.83rem; font-weight: 500;
    transition: all 0.2s ease;
    border-left: 2px solid transparent;
    margin: 1px 8px 1px 10px;
    border-radius: 6px;
}
.acc-body a:hover {
    color: #e2e8f0;
    background: rgba(255,255,255,0.05);
    border-left-color: rgba(56,189,248,0.4);
}
.acc-body a.active {
    color: #38bdf8;
    background: rgba(56,189,248,0.1);
    border-left-color: #38bdf8;
    font-weight: 600;
}
.acc-body a .sub-icon {
    width: 18px; text-align: center; font-size: 0.8rem; flex-shrink: 0;
}
.acc-body a .sub-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #475569; flex-shrink: 0;
    transition: background 0.2s;
}
.acc-body a:hover .sub-dot,
.acc-body a.active .sub-dot { background: #38bdf8; }

/* Section separator */
.sidebar-sep {
    height: 1px; background: rgba(255,255,255,0.05);
    margin: 8px 18px;
}

/* Scrollbar */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(56,189,248,0.3); border-radius: 2px; }
</style>

<aside class="main-sidebar elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo BASE_URL; ?>index.php" class="brand-link d-flex align-items-center" style="padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); text-decoration:none;">
        <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="FunFair Logo" style="max-height: 32px; margin-right: 10px; filter: drop-shadow(0 0 6px rgba(56,189,248,0.4));">
        <span style="font-size:1.2rem; font-weight:800; color:#f8fafc; letter-spacing:1px; text-shadow: 0 0 12px rgba(56,189,248,0.4);">FunFair<span style="font-weight:300; color:#94a3b8;">ERP</span></span>
    </a>

    <div class="sidebar">
        <!-- User Panel -->
        <div class="user-panel-custom">
            <div class="user-avatar">
                <?php
                $roleName = $_SESSION['role_name'] ?? '';
                $emoji = '👤';
                switch (strtolower($roleName)) {
                    case 'super admin': $emoji = '👑'; break;
                    case 'admin': $emoji = '🛡️'; break;
                    case 'event manager': $emoji = '📅'; break;
                    case 'cashier': $emoji = '💵'; break;
                    case 'gate operator': $emoji = '⛩️'; break;
                    case 'ride operator': $emoji = '🎢'; break;
                    case 'user/student': $emoji = '🎓'; break;
                }
                echo $emoji;
                ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Guest'); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($roleName); ?></div>
        </div>

        <!-- Navigation -->
        <nav>
            <ul class="sidebar-nav">

                <!-- Dashboard -->
                <li class="nav-standalone">
                    <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo isActive('/index.php') || $_SERVER['REQUEST_URI'] == '/' || isActive('/funfair_erp/') ? 'active' : ''; ?>">
                        <span class="nav-si"><i class="fas fa-tachometer-alt" style="color:#38bdf8"></i></span>
                        Dashboard
                    </a>
                </li>

                <div class="sidebar-sep"></div>

                <?php 
                // ===========================
                // USER MANAGEMENT
                // ===========================
                $showUserMgmt = hasPermission('manage_users') || hasRole(['superadmin']);
                if ($showUserMgmt): 
                    $ugroupActive = isGroupActive(['/modules/users/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $ugroupActive ? 'open group-active' : ''; ?>" data-target="acc-users">
                        <span class="acc-icon"><i class="fas fa-users-cog" style="color:#818cf8"></i></span>
                        <span class="acc-label">User Management</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $ugroupActive ? 'show' : ''; ?>" id="acc-users">
                        <?php if (hasPermission('manage_users')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/users/index.php" class="<?php echo isActive('/modules/users/index') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Users List
                        </a>
                        <?php endif; ?>
                        <?php if (hasRole(['superadmin'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/users/permissions.php" class="<?php echo isActive('/modules/users/permissions') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Role Permissions
                        </a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php 
                // ===========================
                // EVENT TICKETING
                // ===========================
                $showEvents = hasPermission('manage_events') || (hasPermission('book_tickets') && !hasRole(['ride operator'])) || hasRole(['user/student']);
                if ($showEvents): 
                    $egroupActive = isGroupActive(['/modules/events/', '/modules/admission/', '/modules/tickets/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $egroupActive ? 'open group-active' : ''; ?>" data-target="acc-events">
                        <span class="acc-icon"><i class="fas fa-ticket-alt" style="color:#f43f5e"></i></span>
                        <span class="acc-label">Event Ticketing</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $egroupActive ? 'show' : ''; ?>" id="acc-events">
                        <?php if (hasPermission('manage_events')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/events/index.php" class="<?php echo isActive('/modules/events/index') || isActive('/modules/events/list') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Events Master
                        </a>
                        <?php endif; ?>
                        <?php if (hasRole(['user/student'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/events/list.php" class="<?php echo isActive('/modules/events/list') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Upcoming Events
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('book_tickets') && !hasRole(['ride operator'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/admission/buy.php" class="<?php echo isActive('/modules/admission/buy') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Sell General Entry
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/tickets/index.php" class="<?php echo isActive('/modules/tickets/') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Events Tickets
                        </a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php 
                // ===========================
                // ACTIVITY & RIDES
                // ===========================
                $showRides = hasPermission('manage_swings') || hasPermission('validate_rides') || hasPermission('view_swings');
                if ($showRides): 
                    $rgroupActive = isGroupActive(['/modules/rides/', '/modules/passes/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $rgroupActive ? 'open group-active' : ''; ?>" data-target="acc-rides">
                        <span class="acc-icon"><i class="fas fa-horse" style="color:#f59e0b"></i></span>
                        <span class="acc-label">Activity &amp; Rides</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $rgroupActive ? 'show' : ''; ?>" id="acc-rides">
                        <?php if (hasPermission('manage_swings') || hasPermission('view_swings')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/rides/index.php" class="<?php echo isActive('/modules/rides/index') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> <?php echo hasRole(['user/student']) ? 'Swings & Rides' : 'Manage Swings'; ?>
                        </a>
                        <?php endif; ?>
                        <?php if (!hasRole(['user/student']) && !hasRole(['ride operator'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/rides/booking.php" class="<?php echo isActive('/modules/rides/booking') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Ride Tickets
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('book_tickets') && !hasRole(['ride operator'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/passes/buy.php" class="<?php echo isActive('/modules/passes/buy') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Sell Special Pass
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('view_reports')): ?>
                        <a href="<?php echo BASE_URL; ?>modules/passes/index.php" class="<?php echo isActive('/modules/passes/index') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Special Passes List
                        </a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php 
                // ===========================
                // VALIDATION
                // ===========================
                if (hasPermission('validate_entry') || hasPermission('validate_rides')): 
                    $vgroupActive = isGroupActive(['/modules/admission/validate']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $vgroupActive ? 'open group-active' : ''; ?>" data-target="acc-validation">
                        <span class="acc-icon"><i class="fas fa-qrcode" style="color:#10b981"></i></span>
                        <span class="acc-label">Validation</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $vgroupActive ? 'show' : ''; ?>" id="acc-validation">
                        <a href="<?php echo BASE_URL; ?>modules/admission/validate.php" class="<?php echo isActive('/modules/admission/validate') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Universal Scanner
                        </a>
                    </div>
                </li>
                <?php endif; ?>

                <?php 
                // ===========================
                // REPORTS
                // ===========================
                if (hasPermission('view_reports')): 
                    $repgroupActive = isGroupActive(['/modules/reports/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $repgroupActive ? 'open group-active' : ''; ?>" data-target="acc-reports">
                        <span class="acc-icon"><i class="fas fa-chart-bar" style="color:#38bdf8"></i></span>
                        <span class="acc-label">Reports</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $repgroupActive ? 'show' : ''; ?>" id="acc-reports">
                        <?php if (hasRole(['superadmin', 'admin', 'event manager'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/reports/revenue.php" class="<?php echo isActive('/modules/reports/revenue') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Revenue Summary
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/reports/sales.php" class="<?php echo isActive('/modules/reports/sales') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Sales Reports
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/reports/admissions.php" class="<?php echo isActive('/modules/reports/admissions') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Validation Logs
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/reports/activity_logs.php" class="<?php echo isActive('/modules/reports/activity') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Activity Logs
                        </a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>

                <?php 
                // ===========================
                // EXPENSE TRACKING
                // ===========================
                if (hasRole(['superadmin', 'admin', 'cashier'])): 
                    $expgroupActive = isGroupActive(['/modules/expenses/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $expgroupActive ? 'open group-active' : ''; ?>" data-target="acc-expenses">
                        <span class="acc-icon"><i class="fas fa-file-invoice-dollar" style="color:#fb923c"></i></span>
                        <span class="acc-label">Expense Tracking</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $expgroupActive ? 'show' : ''; ?>" id="acc-expenses">
                        <a href="<?php echo BASE_URL; ?>modules/expenses/index.php" class="<?php echo isActive('/modules/expenses/index') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> View Expenses
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/expenses/add.php" class="<?php echo isActive('/modules/expenses/add') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Record Expense
                        </a>
                    </div>
                </li>

                <!-- HR Management -->
                <?php 
                    $hrgroupActive = isGroupActive(['/modules/hr/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $hrgroupActive ? 'open group-active' : ''; ?>" data-target="acc-hr">
                        <span class="acc-icon"><i class="fas fa-users-cog" style="color:#a78bfa"></i></span>
                        <span class="acc-label">HR Management</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $hrgroupActive ? 'show' : ''; ?>" id="acc-hr">
                        <a href="<?php echo BASE_URL; ?>modules/hr/index.php" class="<?php echo isActive('/modules/hr/index') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Staff Directory
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/hr/shifts.php" class="<?php echo isActive('/modules/hr/shifts') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Manage Staff &amp; Shifts
                        </a>
                    </div>
                </li>
                <?php endif; ?>

                <div class="sidebar-sep"></div>

                <?php 
                // ===========================
                // ACCOUNT
                // ===========================
                $acgroupActive = isGroupActive(['/modules/reports/shift_close', '/modules/profile/']);
                ?>
                <li class="acc-item">
                    <div class="acc-header <?php echo $acgroupActive ? 'open group-active' : ''; ?>" data-target="acc-account">
                        <span class="acc-icon"><i class="fas fa-user-circle" style="color:#64748b"></i></span>
                        <span class="acc-label">Account</span>
                        <i class="fas fa-angle-left acc-arrow"></i>
                    </div>
                    <div class="acc-body <?php echo $acgroupActive ? 'show' : ''; ?>" id="acc-account">
                        <?php if (hasRole(['superadmin', 'cashier', 'eventmanager'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/reports/shift_close.php" class="<?php echo isActive('/modules/reports/shift_close') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> Shift Closing
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>modules/profile/index.php" class="<?php echo isActive('/modules/profile/') ? 'active' : ''; ?>">
                            <span class="sub-dot"></span> My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>logout.php" style="color:#f87171 !important;">
                            <span class="sub-dot" style="background:#f87171"></span> Logout
                        </a>
                    </div>
                </li>

            </ul>
        </nav>
    </div>
</aside>

<script>
// ===== ACCORDION LOGIC =====
(function() {
    const headers = document.querySelectorAll('.acc-header');

    headers.forEach(function(header) {
        header.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const body = document.getElementById(targetId);
            const isOpen = this.classList.contains('open');

            // Close ALL
            headers.forEach(function(h) {
                h.classList.remove('open');
                const bid = h.getAttribute('data-target');
                const b = document.getElementById(bid);
                if (b) b.classList.remove('show');
            });

            // If it was closed, open it
            if (!isOpen) {
                this.classList.add('open');
                if (body) body.classList.add('show');
            }
        });
    });
})();
</script>
