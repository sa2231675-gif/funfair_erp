<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_events');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, event_date=?, price=?, capacity=?, status=? WHERE id=?");
        $stmt->execute([$title, $description, $event_date, $price, $capacity, $status, $id]);
        header("Location: index.php?success=event_updated");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Edit Event";
include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Edit Event: <?php echo $event['title']; ?></h1>
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
                        <input type="text" name="title" value="<?php echo $event['title']; ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo $event['description']; ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Date</label>
                            <input type="date" name="event_date" value="<?php echo $event['event_date']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" value="<?php echo $event['price']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Capacity</label>
                            <input type="number" name="capacity" value="<?php echo $event['capacity']; ?>" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?php echo $event['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $event['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Event</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
