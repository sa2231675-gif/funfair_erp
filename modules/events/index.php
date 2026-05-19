<?php
require_once '../../config/config.php';
requireLogin();
requireAnyPermission(['manage_events', 'view_events']);

$pageTitle = "Events Management";

$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="text-glow">Events</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <?php if (hasPermission('manage_events')): ?>
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Event</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-outline card-danger">
            <div class="card-body p-0">
                <table class="table table-dark-custom table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Price</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo $event['title']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                            <td>Rs. <?php echo number_format($event['price'], 2); ?></td>
                            <td><?php echo $event['capacity']; ?></td>
                            <td><span class="badge badge-<?php echo $event['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo $event['status']; ?></span></td>
                            <td>
                                <?php if (hasPermission('manage_events')): ?>
                                <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="delete.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                <?php endif; ?>
                                <?php if (hasPermission('book_tickets')): ?>
                                <a href="../tickets/book.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-success">Book Ticket</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($events)): ?>
                            <tr><td colspan="6" class="text-center">No events found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
