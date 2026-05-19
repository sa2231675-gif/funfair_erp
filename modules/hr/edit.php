<?php
require_once '../../config/config.php';
requireLogin();

// Only Admin and above can edit employees
if (!hasRole(['superadmin', 'admin'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$page_title = 'Edit Employee';
$error = '';
$success = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $department = $_POST['department'];
    $designation = $_POST['designation'];
    $date_of_joining = $_POST['date_of_joining'];
    $base_salary = (float)$_POST['base_salary'];
    $status = $_POST['status'];
    
    // Optional Linked User Account
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    $default_shift_start = $_POST['default_shift_start'];
    $default_shift_end = $_POST['default_shift_end'];

    if (empty($first_name) || empty($last_name) || empty($department)) {
        $error = "First Name, Last Name, and Department are required.";
    } elseif ($base_salary < 0) {
        $error = "Salary cannot be negative.";
    } else {
        $query = "UPDATE employees SET 
                  user_id = ?,
                  first_name = ?,
                  last_name = ?,
                  department = ?,
                  designation = ?,
                  date_of_joining = ?,
                  base_salary = ?,
                  status = ?,
                  default_shift_start = ?,
                  default_shift_end = ?
                  WHERE id = ?";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user_id, $first_name, $last_name, $department, $designation, $date_of_joining, $base_salary, $status, $default_shift_start, $default_shift_end, $id]);
            $success = "Employee record updated successfully!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch current employee data
$emp_query = "SELECT * FROM employees WHERE id = ?";
$stmt = $pdo->prepare($emp_query);
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header("Location: index.php");
    exit();
}

// Fetch available users to link accounts
$users_query = "SELECT u.id, u.username, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'active'";
$users_result = $pdo->query($users_query)->fetchAll();

include '../../includes/header.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow"><i class="fas fa-user-edit text-neon-info mr-2"></i> Update Employee</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">HR</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card card-dark">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow">Staff Registration Form</h3>
                            <div class="card-tools">
                                <a href="index.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-list"></i> Employee Directory
                                </a>
                            </div>
                        </div>
                        <form method="POST" action="">
                            <div class="card-body">
                                
                                <h5 class="text-neon-primary mb-3"><i class="fas fa-id-card mr-2"></i> Personal Details</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($employee['first_name']); ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($employee['last_name']); ?>">
                                    </div>
                                </div>

                                <hr style="border-top: 1px solid rgba(255,255,255,0.05);">
                                <h5 class="text-neon-success mb-3 mt-4"><i class="fas fa-briefcase mr-2"></i> Employment Info</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="department">Department <span class="text-danger">*</span></label>
                                        <select class="form-control" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <option value="Operations" <?php if($employee['department'] == 'Operations') echo 'selected';?>>Operations</option>
                                            <option value="Sales & Ticketing" <?php if($employee['department'] == 'Sales & Ticketing') echo 'selected';?>>Sales & Ticketing</option>
                                            <option value="Security" <?php if($employee['department'] == 'Security') echo 'selected';?>>Security</option>
                                            <option value="Maintenance" <?php if($employee['department'] == 'Maintenance') echo 'selected';?>>Maintenance</option>
                                            <option value="Administration" <?php if($employee['department'] == 'Administration') echo 'selected';?>>Administration</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="designation">Designation / Role Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="designation" name="designation" required value="<?php echo htmlspecialchars($employee['designation']); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label for="base_salary">Base Salary (PKR) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-dark border-secondary">Rs.</span>
                                            </div>
                                            <input type="number" step="0.01" class="form-control" id="base_salary" name="base_salary" required min="0" value="<?php echo $employee['base_salary']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="date_of_joining">Date of Joining <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date_of_joining" name="date_of_joining" required value="<?php echo $employee['date_of_joining']; ?>">
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="status">Current Status <span class="text-danger">*</span></label>
                                        <select class="form-control bg-dark border-secondary" id="status" name="status" required>
                                            <option value="active" <?php if($employee['status'] == 'active') echo 'selected';?>>Active</option>
                                            <option value="on_leave" <?php if($employee['status'] == 'on_leave') echo 'selected';?>>On Leave</option>
                                            <option value="terminated" <?php if($employee['status'] == 'terminated') echo 'selected';?>>Terminated</option>
                                        </select>
                                    </div>
                                </div>

                                <hr style="border-top: 1px solid rgba(255,255,255,0.05);">
                                <h5 class="text-neon-warning mb-3 mt-4"><i class="fas fa-link mr-2"></i> System Access (Optional)</h5>
                                
                                <div class="form-group">
                                    <label for="user_id">Link to System Account</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">-- No System Access Required --</option>
                                        <?php foreach ($users_result as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php if($employee['user_id'] == $user['id']) echo 'selected';?>>
                                                @<?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['role']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-white-50">Select an existing system user account if this employee needs to log into the ERP. Otherwise, leave blank.</small>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6 form-group">
                                        <label for="default_shift_start"><i class="fas fa-clock text-neon-info mr-1"></i> Default Shift Start Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="default_shift_start" name="default_shift_start" required value="<?php echo date('H:i', strtotime($employee['default_shift_start'])); ?>">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="default_shift_end"><i class="fas fa-clock text-neon-warning mr-1"></i> Default Shift End Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="default_shift_end" name="default_shift_end" required value="<?php echo date('H:i', strtotime($employee['default_shift_end'])); ?>">
                                    </div>
                                </div>

                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer border-0 bg-transparent text-right">
                                <a href="index.php" class="btn btn-outline-secondary mr-2">Cancel</a>
                                <button type="submit" class="btn btn-outline-info"><i class="fas fa-save mr-1"></i> Update Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php 
include '../../includes/footer.php'; 
?>
