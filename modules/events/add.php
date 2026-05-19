<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = "Add Event";
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, price, capacity, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $event_date, $price, $capacity, $status]);
        header("Location: index.php?success=event_created");
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
            <h1>Create New Event</h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-primary">
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>
                <form action="" method="post">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Date</label>
                            <input type="date" name="event_date" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Capacity</label>
                            <input type="number" name="capacity" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
