<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(['superadmin', 'admin', 'cashier'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$page_title = 'Staff & Shifts';
$error = '';

// Add Employee Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $department = $_POST['department'];
    $designation = $_POST['designation'];
    $date_of_joining = $_POST['date_of_joining'];
    $base_salary = (float)$_POST['base_salary'];
    $status = $_POST['status'];
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $default_shift_start = '14:00:00';
    $default_shift_end = '00:00:00';

    if (empty($first_name) || empty($last_name) || empty($department)) {
        $error = "First Name, Last Name, and Department are required.";
    } elseif ($base_salary < 0) {
        $error = "Salary cannot be negative.";
    } else {
        $query = "INSERT INTO employees (user_id, first_name, last_name, department, designation, date_of_joining, base_salary, status, default_shift_start, default_shift_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user_id, $first_name, $last_name, $department, $designation, $date_of_joining, $base_salary, $status, $default_shift_start, $default_shift_end]);
            
            $new_employee_id = $pdo->lastInsertId();
            
            // Assign today's shift immediately upon creation
            $shift_date = date('Y-m-d');
            $shift_stmt = $pdo->prepare("INSERT INTO employee_shifts (employee_id, shift_date, start_time, end_time, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $shift_stmt->execute([$new_employee_id, $shift_date, $default_shift_start, $default_shift_end]);
            
            header("Location: shifts.php?success=added");
            exit();
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Assign Shift Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_shift'])) {
    $employee_id = $_POST['employee_id'];
    $shift_date = $_POST['shift_date'];
    $start_time = '14:00:00';
    $end_time = '00:00:00';

    // Check if shift already exists
    $check_stmt = $pdo->prepare("SELECT id FROM employee_shifts WHERE employee_id = ? AND shift_date = ?");
    $check_stmt->execute([$employee_id, $shift_date]);
    if ($check_stmt->rowCount() > 0) {
        $error = "This employee already has a shift assigned for this date.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO employee_shifts (employee_id, shift_date, start_time, end_time, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$employee_id, $shift_date, $start_time, $end_time]);
            header("Location: shifts.php?date=" . urlencode($shift_date) . "&success=assigned");
            exit();
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Cancel Shift Logic
if (isset($_GET['cancel'])) {
    $id = (int)$_GET['cancel'];
    $pdo->exec("UPDATE employee_shifts SET status = 'cancelled' WHERE id = $id");
    header("Location: shifts.php?success=cancelled");
    exit();
}

// Manual Check-In/Out for employees without dashboard
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action == 'checkin') {
        $pdo->exec("UPDATE employee_shifts SET status = 'started', check_in_time = NOW() WHERE id = $id AND status = 'scheduled'");
        header("Location: shifts.php?success=checked_in");
        exit();
    } elseif ($action == 'checkout') {
        $pdo->exec("UPDATE employee_shifts SET status = 'completed', check_out_time = NOW() WHERE id = $id AND status = 'started'");
        header("Location: shifts.php?success=checked_out");
        exit();
    }
}

// 1. Auto Check-out logic (for today's shifts that are still 'started' but past end_time)
$now_time = date('H:i:s');
$today_date = date('Y-m-d');
$auto_stmt = $pdo->prepare("SELECT id FROM employee_shifts WHERE status = 'started' AND shift_date = ? AND end_time < ?");
$auto_stmt->execute([$today_date, $now_time]);
$to_checkout = $auto_stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($to_checkout as $shift_id) {
    $s_stmt = $pdo->prepare("SELECT * FROM employee_shifts WHERE id = ?");
    $s_stmt->execute([$shift_id]);
    $s_data = $s_stmt->fetch(PDO::FETCH_ASSOC);
    
    $check_time_col = !empty($s_data['last_check_in_time']) ? 'last_check_in_time' : 'check_in_time';
    $mins_stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, $check_time_col, NOW()) FROM employee_shifts WHERE id = ?");
    $mins_stmt->execute([$shift_id]);
    $m = (int)$mins_stmt->fetchColumn();
    
    $update_stmt = $pdo->prepare("UPDATE employee_shifts SET status = 'completed', check_out_time = NOW(), total_working_minutes = total_working_minutes + ? WHERE id = ?");
    $update_stmt->execute([$m, $shift_id]);
    
    $pdo->prepare("INSERT INTO employee_attendance_logs (shift_id, employee_id, action_type) VALUES (?, ?, 'check_out')")->execute([$shift_id, $s_data['employee_id']]);
}

// 2. Fetch Shifts with action counts for selected date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Auto-assign default shifts for TODAY if they do not exist
if ($selected_date == date('Y-m-d')) {
    $missing_stmt = $pdo->query("SELECT id FROM employees WHERE status = 'active' AND id NOT IN (SELECT employee_id FROM employee_shifts WHERE shift_date = CURRENT_DATE)");
    $missing = $missing_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($missing as $emp) {
        $pdo->prepare("INSERT INTO employee_shifts (employee_id, shift_date, start_time, end_time, status) VALUES (?, CURRENT_DATE, '14:00:00', '00:00:00', 'scheduled')")
            ->execute([$emp['id']]);
    }
}

$query = "SELECT s.id, s.start_time, s.end_time, s.status, s.shift_date, s.check_in_time, s.check_out_time, s.check_in_count, s.check_out_count, s.total_working_minutes, 
                 e.id as employee_id, e.first_name, e.last_name, e.designation, IFNULL(s.status, 'absent') as shift_status
          FROM employees e 
          LEFT JOIN employee_shifts s ON e.id = s.employee_id AND s.shift_date = ?
          WHERE e.status = 'active' OR s.id IS NOT NULL 
          ORDER BY e.first_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$selected_date]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$employees = $pdo->query("SELECT * FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
$users_query = "SELECT u.id, u.username, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'active'";
$users_result = $pdo->query($users_query)->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0 text-glow"><i class="fas fa-users-cog text-neon-primary mr-2"></i> Manage Staff & Shifts</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Success!</h5> Action completed successfully.
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5> <?= $error; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-dark card-tabs">
                        <div class="card-header p-0 pt-1 border-bottom-0">
                            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true"><i class="fas fa-calendar-alt mr-1"></i> Shift Roster</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-one-profile-tab" data-toggle="pill" href="#custom-tabs-one-profile" role="tab" aria-controls="custom-tabs-one-profile" aria-selected="false"><i class="fas fa-user-plus mr-1"></i> Add New Employee</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="custom-tabs-one-tabContent">
                                <!-- Shift Roster Tab -->
                                <div class="tab-pane fade show active" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                                    <div class="d-flex justify-content-between mb-3">
                                        <form method="GET" class="form-inline">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-dark border-secondary text-info"><i class="fas fa-calendar-day"></i></span>
                                                </div>
                                                <input type="date" name="date" class="form-control bg-dark border-secondary text-white" value="<?= htmlspecialchars($selected_date) ?>">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-info btn-sm">Filter</button>
                                                </div>
                                            </div>
                                            <?php if ($selected_date != date('Y-m-d')): ?>
                                                <a href="shifts.php" class="btn btn-outline-secondary btn-sm ml-2">Today</a>
                                            <?php endif; ?>
                                        </form>
                                        <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#assignShiftModal">
                                            <i class="fas fa-calendar-plus mr-1"></i> Assign Shift
                                        </button>
                                    </div>
                                    <table id="shiftsTable" class="table table-dark-custom table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th>Employee</th>
                                                <th>Role</th>
                                                <th>Date</th>
                                                <th>Timing</th>
                                                <th>Status</th>
                                                <th>Check In/Out</th>
                                                <th>Worked Hrs</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($shifts as $row): ?>
                                                <tr>
                                                    <td class="font-weight-bold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['designation']) ?></td>
                                                    <td><?= $row['shift_date'] ? date('M d, Y', strtotime($row['shift_date'])) : date('M d, Y', strtotime($selected_date)) ?></td>
                                                    <td>
                                                        <?php if ($row['start_time']): ?>
                                                            <span class="text-neon-info"><?= date('h:i A', strtotime($row['start_time'])) ?></span> - <span class="text-neon-warning"><?= date('h:i A', strtotime($row['end_time'])) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">No Shift</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span id="status-<?= $row['id'] ?? '' ?>">
                                                        <?php 
                                                            if ($row['shift_status'] == 'scheduled') echo '<span class="badge badge-info">Scheduled</span>';
                                                            elseif ($row['shift_status'] == 'started') echo '<span class="badge badge-primary">Shift Started</span>';
                                                            elseif ($row['shift_status'] == 'paused') echo '<span class="badge badge-warning">Paused</span>';
                                                            elseif ($row['shift_status'] == 'completed') echo '<span class="badge badge-success">Completed</span>';
                                                            elseif ($row['shift_status'] == 'cancelled') echo '<span class="badge badge-danger">Cancelled</span>';
                                                            else echo '<span class="badge badge-secondary">Absent</span>';
                                                        ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['id']): ?>
                                                            <div class="small">
                                                                <div class="text-neon-primary mb-1">In: <span id="checkin-<?= $row['id'] ?>"><?= $row['check_in_time'] ? date('h:i A', strtotime($row['check_in_time'])) : '-' ?></span> (<span id="checkin-count-<?= $row['id'] ?>"><?= $row['check_in_count'] ?></span>)</div>
                                                                <div class="text-neon-warning">Out: <span id="checkout-<?= $row['id'] ?>"><?= $row['check_out_time'] ? date('h:i A', strtotime($row['check_out_time'])) : '-' ?></span> (<span id="checkout-count-<?= $row['id'] ?>"><?= $row['check_out_count'] ?></span>)</div>
                                                            </div>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['id']): ?>
                                                            <strong id="hours-<?= $row['id'] ?>" class="text-neon-success">
                                                                <?php 
                                                                    $total_m = (int)$row['total_working_minutes'];
                                                                    $h = floor($total_m / 60);
                                                                    $m = $total_m % 60;
                                                                    echo $h . 'h ' . $m . 'm';
                                                                ?>
                                                            </strong>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td id="actions-<?= $row['id'] ?? '' ?>">
                                                        <?php if ($selected_date == date('Y-m-d') && $row['id']): ?>
                                                            <?php if(in_array($row['status'], ['scheduled', 'paused', 'completed'])): ?>
                                                                <button onclick="handleShiftAction(<?= $row['id'] ?>, 'checkin')" class="btn btn-sm btn-outline-success mb-1 w-100"><i class="fas fa-sign-in-alt"></i> Check In</button>
                                                                <?php if ($row['status'] == 'scheduled'): ?>
                                                                    <a href="?cancel=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Cancel this shift?');"><i class="fas fa-times"></i> Cancel</a>
                                                                <?php endif; ?>
                                                            <?php elseif($row['status'] == 'started'): ?>
                                                                <button onclick="handleShiftAction(<?= $row['id'] ?>, 'checkout')" class="btn btn-sm btn-outline-warning w-100"><i class="fas fa-sign-out-alt"></i> Check Out</button>
                                                            <?php endif; ?>
                                                        <?php elseif ($selected_date < date('Y-m-d')): ?>
                                                            <span class="text-muted small">Locked</span>
                                                        <?php else: ?>
                                                             <?php if (!$row['id']): ?>
                                                                 <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#assignShiftModal" onclick="$('select[name=employee_id]').val(<?= $row['employee_id'] ?>); $('input[name=shift_date]').val('<?= $selected_date ?>');"><i class="fas fa-plus"></i> Assign</button>
                                                             <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Add Employee Tab -->
                                <div class="tab-pane fade" id="custom-tabs-one-profile" role="tabpanel" aria-labelledby="custom-tabs-one-profile-tab">
                                    <h4 class="text-glow mb-4 p-2 bg-gradient-dark rounded"><i class="fas fa-id-card mr-2 text-neon-primary"></i> Registration Form</h4>
                                    <form method="POST" action="">
                                        <input type="hidden" name="add_employee" value="1">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" required placeholder="Ali">
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" required placeholder="Khan">
                                            </div>
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6 form-group">
                                                <label for="department">Department <span class="text-danger">*</span></label>
                                                <select class="form-control" id="department" name="department" required>
                                                    <option value="">Select Department</option>
                                                    <option value="Operations">Operations</option>
                                                    <option value="Sales & Ticketing">Sales & Ticketing</option>
                                                    <option value="Security">Security</option>
                                                    <option value="Maintenance">Maintenance</option>
                                                    <option value="Administration">Administration</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="designation">Designation / Role Title <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="designation" name="designation" required placeholder="e.g. Senior Ride Operator">
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-md-4 form-group">
                                                <label for="base_salary">Base Salary (PKR) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-dark border-secondary">Rs.</span>
                                                    </div>
                                                    <input type="number" step="0.01" class="form-control" id="base_salary" name="base_salary" required min="0" value="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="date_of_joining">Date of Joining <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" required value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="status">Current Status <span class="text-danger">*</span></label>
                                                <select class="form-control bg-dark border-secondary" id="status" name="status" required>
                                                    <option value="active" selected>Active</option>
                                                    <option value="on_leave">On Leave</option>
                                                    <option value="terminated">Terminated</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="user_id"><i class="fas fa-link text-neon-warning mr-1"></i> Link to System Account (Optional)</label>
                                            <select class="form-control" id="user_id" name="user_id">
                                                <option value="">-- No System Access Required --</option>
                                                <?php foreach ($users_result as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>">
                                                        @<?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['role']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-white-50 mt-1">Select an existing system user account if this employee needs to log into the ERP. Otherwise, leave blank.</small>
                                        </div>

                                        <!-- Shift times are now hardcoded to 3 PM - 12 AM -->
                                        <input type="hidden" name="default_shift_start" value="14:00">
                                        <input type="hidden" name="default_shift_end" value="00:00">

                                        <hr class="mt-4 mb-3 border-secondary">
                                        <div class="text-right">
                                            <button type="reset" class="btn btn-outline-secondary mr-2">Clear Form</button>
                                            <button type="submit" class="btn btn-outline-success"><i class="fas fa-user-check mr-1"></i> Register Employee</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Assign Shift Modal -->
<div class="modal fade" id="assignShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-glow">Assign Shift to Employee</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="assign_shift" value="1">
                <div class="form-group">
                    <label>Employee <span class="text-danger">*</span></label>
                    <select class="form-control" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= $emp['first_name'].' '.$emp['last_name'] ?> (<?= $emp['designation'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="shift_date" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group text-center">
                    <span class="badge badge-primary p-2 w-100">Shift Timing: 02:00 PM - 12:00 AM</span>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-info">Assign Shift</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
    $(function () {
        $('#shiftsTable').DataTable({
            "destroy": true,
            "responsive": true, "order": [[0, 'asc']]
        });
        
        // Handle URL Hash for opening specific tabs
        var hash = window.location.hash;
        if (hash === '#add') {
            $('#custom-tabs-one-profile-tab').tab('show');
        }
    });

    function handleShiftAction(id, action) {
        $.ajax({
            url: 'ajax_shift_action.php',
            type: 'POST',
            data: { id: id, action: action },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var now = new Date();
                    var timeStr = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    if (response.status === 'started') {
                        $('#status-' + id).html('<span class="badge badge-primary">Shift Started</span>');
                        $('#checkin-' + id).text(timeStr);
                        $('#checkin-count-' + id).text(response.in_count);
                        $('#checkout-count-' + id).text(response.out_count);
                        $('#actions-' + id).html('<button onclick="handleShiftAction(' + id + ', \'checkout\')" class="btn btn-sm btn-outline-warning w-100"><i class="fas fa-sign-out-alt"></i> Check Out</button>');
                    } else if (response.status === 'paused') {
                        $('#status-' + id).html('<span class="badge badge-warning">Paused</span>');
                        $('#checkout-' + id).text(timeStr);
                        $('#checkin-count-' + id).text(response.in_count);
                        $('#checkout-count-' + id).text(response.out_count);
                        
                        $('#hours-' + id).text(response.formatted_time || (Math.floor(response.total_minutes / 60) + 'h ' + (response.total_minutes % 60) + 'm'));
                        
                        $('#actions-' + id).html('<button onclick="handleShiftAction(' + id + ', \'checkin\')" class="btn btn-sm btn-outline-success mb-1 w-100"><i class="fas fa-sign-in-alt"></i> Check In</button>');
                    }
                    
                    // Show a quick toast or alert if needed
                    // alert(response.message);
                } else {
                    alert("Error: " + response.error);
                }
            },
            error: function() {
                alert("Something went wrong with the request.");
            }
        });
    }
</script>

