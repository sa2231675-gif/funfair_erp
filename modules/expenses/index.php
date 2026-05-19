<?php
require_once '../../config/config.php';
requireLogin();

// Only Admin and above can manage expenses
if (!hasRole(['superadmin', 'admin'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$page_title = 'Expense Tracking';

include '../../includes/header.php';
// Handle expense deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->exec("DELETE FROM expenses WHERE id = $id");
    header("Location: " . BASE_URL . "modules/expenses/index.php?success=deleted");
    exit();
}

// Fetch all expenses
$query = "SELECT e.*, u.username as recorded_by_user 
          FROM expenses e 
          LEFT JOIN users u ON e.recorded_by = u.id 
          ORDER BY e.expense_date DESC, e.created_at DESC";
$result = $pdo->query($query);

// Calculate total expenses shown
$total_expense = 0;
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow"><i class="fas fa-file-invoice-dollar text-neon-warning mr-2"></i> Expense Tracking</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item active">Expenses</li>
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
                    <?php 
                        if($_GET['success'] == 'updated') echo "Expense record updated successfully.";
                        elseif($_GET['success'] == 'added') echo "New expense recorded successfully.";
                        elseif($_GET['success'] == 'deleted') echo "Expense record deleted successfully.";
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    <?php 
                        if($_GET['error'] == 'notfound') echo "Expense record not found.";
                    ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-dark">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow">Expense Records</h3>
                            <div class="card-tools">
                                <a href="add.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Record Expense
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="expensesTable" class="table table-dark-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Amount (PKR)</th>
                                        <th>Recorded By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                        <?php $total_expense += $row['amount']; ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($row['category']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td class="font-weight-bold text-neon-danger">- Rs. <?php echo number_format($row['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['recorded_by_user'] ?? 'System'); ?></td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info mr-1" title="Edit Expense">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this expense record?');" title="Delete Expense">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Total Expenses:</th>
                                        <th colspan="3" class="text-neon-danger text-glow" style="font-size: 1.2rem;">
                                            Rs. <?php echo number_format($total_expense, 2); ?>
                                        </th>
                                    </tr>
                                </tfoot>
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
        $('#expensesTable').DataTable({
            "destroy": true,
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, 'desc']] // Sort by Date descending
        });
    });
</script>
