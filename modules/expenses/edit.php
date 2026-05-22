<?php
require_once '../../config/config.php';
requireLogin();

// Only Admin and above can edit expenses
if (!hasRole(['superadmin', 'admin', 'cashier'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "modules/expenses/index.php");
    exit();
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
$stmt->execute([$id]);
$expense = $stmt->fetch();

if (!$expense) {
    header("Location: " . BASE_URL . "modules/expenses/index.php?error=notfound");
    exit();
}

$page_title = 'Edit Expense Record';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $amount = (float)$_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $description = $_POST['description'];
    
    if (empty($category) || empty($expense_date) || empty($description)) {
        $error = "Category, Date, and Description are required.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } else {
        $query = "UPDATE expenses SET category = ?, amount = ?, expense_date = ?, description = ? WHERE id = ?";
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$category, $amount, $expense_date, $description, $id]);
            
            // Log Update
            $stmtAct = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
            $stmtAct->execute([$_SESSION['user_id'], 'Expense Updated', "Updated record ID: $id. New amount: Rs. {$amount} for {$category}"]);
            
            header("Location: " . BASE_URL . "modules/expenses/index.php?success=updated");
            exit();
        } catch(PDOException $e) {
            $error = "Error updating expense: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-glow"><i class="fas fa-edit text-neon-info mr-2"></i> Edit Expense</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Expenses</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card card-dark">
                        <div class="card-header border-0">
                            <h3 class="card-title text-glow">Update Operational Expense</h3>
                            <div class="card-tools">
                                <a href="index.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-list"></i> Cancel & Return
                                </a>
                            </div>
                        </div>
                        <form method="POST" action="">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="category">Expense Category <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Maintenance & Parts" <?php echo $expense['category'] == 'Maintenance & Parts' ? 'selected' : ''; ?>>Maintenance & Parts</option>
                                        <option value="Utilities (Electricity/Water)" <?php echo $expense['category'] == 'Utilities (Electricity/Water)' ? 'selected' : ''; ?>>Utilities (Electricity/Water)</option>
                                        <option value="Operational Staff Salary" <?php echo $expense['category'] == 'Operational Staff Salary' ? 'selected' : ''; ?>>Operational Staff Salary</option>
                                        <option value="Sewing Cost" <?php echo $expense['category'] == 'Sewing Cost' ? 'selected' : ''; ?>>Sewing Cost</option>
                                        <option value="Marketing & Print" <?php echo $expense['category'] == 'Marketing & Print' ? 'selected' : ''; ?>>Marketing & Print</option>
                                        <option value="Cleaning & Supplies" <?php echo $expense['category'] == 'Cleaning & Supplies' ? 'selected' : ''; ?>>Cleaning & Supplies</option>
                                        <option value="Miscellaneous" <?php echo $expense['category'] == 'Miscellaneous' ? 'selected' : ''; ?>>Miscellaneous</option>
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="amount">Amount (PKR) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-dark border-secondary">Rs.</span>
                                            </div>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="1" value="<?php echo $expense['amount']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="expense_date">Date of Expense <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="expense_date" name="expense_date" required value="<?php echo $expense['expense_date']; ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Additional Details/Description (Optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter invoice number or remarks..."><?php echo htmlspecialchars($expense['description']); ?></textarea>
                                </div>
                            </div>
                            <div class="card-footer border-0 bg-transparent text-right">
                                <button type="submit" class="btn btn-outline-info btn-block btn-lg"><i class="fas fa-save mr-1"></i> Update Record</button>
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
