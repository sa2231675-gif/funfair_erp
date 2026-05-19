<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_swings');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM swings WHERE id = ?");
$stmt->execute([$id]);
$swing = $stmt->fetch();

if (!$swing) {
    header("Location: index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE swings SET name=?, price=?, duration=?, capacity=?, status=? WHERE id=?");
        $stmt->execute([$name, $price, $duration, $capacity, $status, $id]);
        header("Location: index.php?success=ride_updated");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Edit Ride";
include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Edit Ride: <?php echo $swing['name']; ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-info">
            <div class="card-body">
                <form action="" method="post">
                    <div class="form-group">
                        <label>Ride Name</label>
                        <input type="text" name="name" value="<?php echo $swing['name']; ?>" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Price per Person</label>
                            <input type="number" step="0.01" name="price" value="<?php echo $swing['price']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Duration (Minutes)</label>
                            <input type="number" name="duration" value="<?php echo $swing['duration']; ?>" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Max Capacity</label>
                            <input type="number" name="capacity" value="<?php echo $swing['capacity']; ?>" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo $swing['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="maintenance" <?php echo $swing['status'] == 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                            <option value="inactive" <?php echo $swing['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">Update Ride</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
