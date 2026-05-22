<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo BASE_URL; ?>index.php" class="brand-link d-flex align-items-center">
        <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="FunFair Logo" class="brand-image img-circle elevation-3" style="opacity: 0.9; max-height: 33px; filter: drop-shadow(0 0 5px rgba(79, 70, 229, 0.2));">
        <span class="brand-text font-weight-bold ml-2 text-glow" style="font-size: 1.4rem; letter-spacing: 1px;"><?php echo SITE_NAME; ?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex flex-column align-items-center">
            <div class="info text-center">
                <a href="#" class="d-block mb-1" style="font-size: 1.1rem;"><?php echo $_SESSION['full_name'] ?? 'Guest'; ?></a>
                <div class="text-muted">
                    <?php 
                        $roleName = $_SESSION['role_name'] ?? ''; 
                        $emoji = '';
                        switch (strtolower($roleName)) {
                            case 'super admin': $emoji = '👑'; break;
                            case 'admin': $emoji = '🛡️'; break;
                            case 'event manager': $emoji = '📅'; break;
                            case 'cashier': $emoji = '💵'; break;
                            case 'gate operator': $emoji = '⛩️'; break;
                            case 'ride operator': $emoji = '🎢'; break;
                            case 'user/student': $emoji = '👤'; break;
                        }
                        if (strtolower($roleName) === 'super admin') {
                            $roleName = "SAMI ULLAH";
                        }
                        echo '<div style="font-size: 1.5rem; text-align: center; margin-bottom: 5px;">' . $emoji . '</div>';
                        echo '<div style="text-align: center; color: #38bdf8; font-weight: bold; font-size: 0.9rem; letter-spacing: 1px;">' . $roleName . '</div>';
                    ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt text-neon-info"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if (hasPermission('manage_users')): ?>
                <li class="nav-header text-muted">USER MANAGEMENT</li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/users/index.php" class="nav-link">
                        <i class="nav-icon fas fa-users text-neon-primary"></i>
                        <p>Users</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole(['superadmin'])): ?>
                <?php if (!hasPermission('manage_users')): ?><li class="nav-header text-muted">USER MANAGEMENT</li><?php endif; ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/users/permissions.php" class="nav-link">
                        <i class="nav-icon fas fa-shield-alt text-neon-danger"></i>
                        <p>Role Permissions</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('manage_events') || (hasPermission('book_tickets') && !hasRole(['ride operator'])) || hasRole(['user/student'])): ?>
                <li class="nav-header text-muted">EVENT TICKETING</li>
                <?php if (hasPermission('manage_events')): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/events/index.php" class="nav-link">
                        <i class="nav-icon fas fa-calendar-alt text-neon-danger"></i>
                        <p>Events Master</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasRole(['user/student'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/events/list.php" class="nav-link">
                        <i class="nav-icon fas fa-calendar-alt text-neon-danger"></i>
                        <p>Upcoming Events</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('book_tickets') && !hasRole(['ride operator'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/admission/buy.php" class="nav-link">
                        <i class="nav-icon fas fa-door-open text-neon-success"></i>
                        <p>Sell General Entry</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/tickets/index.php" class="nav-link">
                        <i class="nav-icon fas fa-ticket-alt text-neon-info"></i>
                        <p>Events Tickets</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (hasPermission('manage_swings') || hasPermission('validate_rides') || hasPermission('view_swings')): ?>
                <li class="nav-header text-muted">ACTIVITY & RIDES</li>
                <?php if (hasPermission('manage_swings') || hasPermission('view_swings')): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/rides/index.php" class="nav-link">
                        <i class="nav-icon fas fa-horse text-neon-warning"></i>
                        <p><?php echo hasRole(['user/student']) ? 'Swings & Rides' : 'Manage Swings'; ?></p>
                    </a>
                </li>
                <?php if (!hasRole(['user/student'])): ?>
                <?php if (!hasRole(['ride operator'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/rides/booking.php" class="nav-link">
                        <i class="nav-icon fas fa-vr-cardboard text-neon-primary"></i>
                        <p>Ride Tickets</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('book_tickets') && !hasRole(['ride operator'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/passes/buy.php" class="nav-link">
                        <i class="nav-icon fas fa-cart-plus text-neon-warning"></i>
                        <p>Sell Special Pass</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasPermission('view_reports')): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/passes/index.php" class="nav-link">
                        <i class="nav-icon fas fa-id-card text-neon-warning"></i>
                        <p>Special Passes List</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (hasPermission('validate_entry') || hasPermission('validate_rides')): ?>
                <li class="nav-header text-muted">VALIDATION</li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/admission/validate.php" class="nav-link">
                        <i class="nav-icon fas fa-qrcode text-neon-success"></i>
                        <p>Universal Scanner</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (hasPermission('view_reports')): ?>
                <li class="nav-header text-muted">REPORTS</li>
                
                <?php if (hasRole(['superadmin', 'admin', 'event manager'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/reports/revenue.php" class="nav-link">
                        <i class="nav-icon fas fa-chart-pie text-neon-success"></i>
                        <p>Revenue Summary</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/reports/sales.php" class="nav-link">
                        <i class="nav-icon fas fa-chart-line text-neon-info"></i>
                        <p>Sales Reports</p>
                    </a>
                </li>
                <?php endif; ?>
                

                
                <?php if (hasRole(['superadmin', 'admin', 'event manager'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/reports/admissions.php" class="nav-link">
                        <i class="nav-icon fas fa-door-open text-neon-primary"></i>
                        <p>Validation Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/reports/activity_logs.php" class="nav-link">
                        <i class="nav-icon fas fa-history text-neon-danger"></i>
                        <p>Activity Logs</p>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php endif; ?>

                <?php if (hasRole(['superadmin', 'admin', 'cashier'])): ?>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar text-neon-warning"></i>
                        <p>
                            Expense Tracking
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>modules/expenses/index.php" class="nav-link">
                                <i class="far fa-circle nav-icon text-neon-warning"></i>
                                <p>View Expenses</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>modules/expenses/add.php" class="nav-link">
                                <i class="far fa-circle nav-icon text-neon-warning"></i>
                                <p>Record Expense</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users-cog text-neon-primary"></i>
                        <p>
                            HR Management
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>modules/hr/index.php" class="nav-link">
                                <i class="far fa-circle nav-icon text-neon-primary"></i>
                                <p>Staff Directory</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>modules/hr/shifts.php" class="nav-link">
                                <i class="far fa-circle nav-icon text-neon-success"></i>
                                <p>Manage Staff & Shifts</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-header text-muted">ACCOUNT</li>
                <?php if (hasRole(['superadmin', 'cashier', 'eventmanager'])): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/reports/shift_close.php" class="nav-link">
                        <i class="nav-icon fas fa-cash-register text-neon-warning"></i>
                        <p>Shift Closing</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>modules/profile/index.php" class="nav-link">
                        <i class="nav-icon fas fa-user-cog text-neon-info"></i>
                        <p>My Profile</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
