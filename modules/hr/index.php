<?php
require_once '../../config/config.php';
requireLogin();

// Only Admin and above can view/manage HR records
if (!hasRole(['superadmin', 'admin', 'cashier'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$page_title = 'HR & Staff Management';

include '../../includes/header.php';
// Handle employee deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->exec("DELETE FROM employees WHERE id = $id");
    header("Location: " . BASE_URL . "modules/hr/index.php?success=deleted");
    exit();
}

// Fetch all employees
$query = "SELECT e.*, u.username 
          FROM employees e 
          LEFT JOIN users u ON e.user_id = u.id 
          ORDER BY e.created_at DESC";
$result = $pdo->query($query);
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow"><i class="fas fa-users-cog text-neon-primary mr-2"></i> HR & Staff Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item active">HR Management</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                    Employee action completed successfully.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-dark">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow">Staff Directory</h3>
                            <div class="card-tools">
                                <a href="shifts.php#add" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-user-plus mr-1"></i> Add New Employee
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="employeesTable" class="table table-dark-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role / Designation</th>
                                        <th>Department</th>
                                        <th>Base Salary (PKR)</th>
                                        <th>Joined On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td class="font-weight-bold">
                                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                <?php if($row['username']): ?>
                                                    <br><small class="text-white-50">Acc: @<?php echo htmlspecialchars($row['username']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                                            <td class="text-neon-success">Rs. <?php echo number_format($row['base_salary'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_of_joining'])); ?></td>
                                            <td>
                                                <?php 
                                                    if ($row['status'] == 'active') {
                                                        echo '<span class="badge badge-success">Active</span>';
                                                    } elseif ($row['status'] == 'on_leave') {
                                                        echo '<span class="badge badge-warning">On Leave</span>';
                                                    } else {
                                                        echo '<span class="badge badge-danger">Terminated</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('WARNING: Are you sure you want to delete this employee? This action cannot be undone.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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

<!-- DataTables setup -->
<script>
    $(function () {
        $('#employeesTable').DataTable({
            "destroy": true,
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, 'asc']] // Sort by Name ascending
        });
    });
</script>
