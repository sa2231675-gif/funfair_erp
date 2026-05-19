<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = "Add Ride";
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO swings (name, price, duration, capacity, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $duration, $capacity, $status]);
        header("Location: index.php?success=ride_added");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Add New Swing/Ride</h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-info">
            <div class="card-body">
                <form action="" method="post">
                    <div class="form-group">
                        <label>Ride Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Price per Person</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Duration (Minutes)</label>
                            <input type="number" name="duration" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Max Capacity</label>
                            <input type="number" name="capacity" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">Save Ride</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
