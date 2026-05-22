<?php
require_once '../../config/config.php';
requireLogin();

// Only Admin and above can record expenses
if (!hasRole(['superadmin', 'admin', 'cashier'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$page_title = 'Record Expense';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $amount = (float)$_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $description = $_POST['description'];
    $recorded_by = $_SESSION['user_id'];
    
    if (empty($category) || empty($expense_date) || empty($description)) {
        $error = "Category, Date, and Description are required.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than zero.";
        } else {
            $query = "INSERT INTO expenses (category, amount, expense_date, description, recorded_by) 
                      VALUES (?, ?, ?, ?, ?)";
            try {
                $stmt = $pdo->prepare($query);
                $stmt->execute([$category, $amount, $expense_date, $description, $recorded_by]);
                
                // Track this heavily in Activity Logs
                $stmtAct = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
                $stmtAct->execute([$recorded_by, 'Expense Added', "Recorded Rs. {$amount} for {$category}"]);
                
                header("Location: " . BASE_URL . "modules/expenses/index.php?success=added");
                exit();
            } catch(PDOException $e) {
            $error = "Error recording expense: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow"><i class="fas fa-file-invoice-dollar text-neon-warning"></i> Expense Tracking</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Expenses</a></li>
                        <li class="breadcrumb-item active">Record</li>
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
                            <h3 class="card-title text-glow">Record New Operational Expense</h3>
                            <div class="card-tools">
                                <a href="index.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-list"></i> View All Records
                                </a>
                            </div>
                        </div>
                        <form method="POST" action="">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="category">Expense Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Maintenance & Parts">Maintenance & Parts</option>
                                        <option value="Utilities (Electricity/Water)">Utilities (Electricity/Water)</option>
                                        <option value="Operational Staff Salary">Operational Staff Salary</option>
                                        <option value="Sewing Cost">Sewing Cost</option>
                                        <option value="Marketing & Print">Marketing & Print</option>
                                        <option value="Cleaning & Supplies">Cleaning & Supplies</option>
                                        <option value="Miscellaneous">Miscellaneous</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="amount">Amount (PKR) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-dark border-secondary">Rs.</span>
                                            </div>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="expense_date">Date of Expense <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="expense_date" name="expense_date" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Additional Details/Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter invoice number or remarks..."></textarea>
                                </div>
                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer border-0 bg-transparent text-right">
                                <button type="reset" class="btn btn-outline-secondary mr-2">Clear Form</button>
                                <button type="submit" class="btn btn-outline-success"><i class="fas fa-save mr-1"></i> Record Data</button>
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
