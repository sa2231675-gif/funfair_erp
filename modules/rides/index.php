<?php
require_once '../../config/config.php';
requireLogin();
requireAnyPermission(['manage_swings', 'view_swings']);

$pageTitle = "Ride Management";

$stmt = $pdo->query("SELECT * FROM swings ORDER BY id DESC");
$swings = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="text-glow">Swings & Rides</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <?php if (hasPermission('manage_swings') && !hasRole(['ride operator'])): ?>
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Ride</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <?php foreach ($swings as $swing): ?>
            <div class="col-md-4">
                <div class="card card-dark card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo $swing['name']; ?></h3>
                        <div class="card card-dark-tools">
                            <?php 
                            $badge = 'success';
                            if ($swing['status'] == 'inactive') $badge = 'secondary';
                            if ($swing['status'] == 'maintenance') $badge = 'warning';
                            ?>
                            <span class="badge badge-<?php echo $badge; ?>"><?php echo strtoupper($swing['status']); ?></span>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($swing['image']): ?>
                            <img src="<?php echo BASE_URL; ?>assets/images/swings/<?php echo $swing['image']; ?>" class="img-fluid mb-3 rounded" style="height: 200px; width: 100%; object-fit: cover;" alt="<?php echo $swing['name']; ?>">
                        <?php else: ?>
                            <div class="bg-light mb-3 rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="text-left">
                            <p><strong>Price:</strong> Rs. <?php echo number_format($swing['price'], 2); ?></p>
                            <p><strong>Duration:</strong> <?php echo $swing['duration']; ?> mins</p>
                            <p><strong>Capacity:</strong> <?php echo $swing['capacity']; ?> persons</p>
                        </div>
                    </div>
                    <div class="card card-dark-footer">
                        <?php if (hasPermission('book_tickets') && !hasRole(['ride operator'])): ?>
                            <?php if ($swing['status'] == 'active'): ?>
                                <a href="booking.php?swing_id=<?php echo $swing['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-ticket-alt"></i> Sell Ride Pass</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled><i class="fas fa-ban"></i> <?php echo $swing['status'] == 'maintenance' ? 'Under Maintenance' : 'Inactive'; ?></button>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_swings')): ?>
                        <div class="float-right">
                            <a href="edit.php?id=<?php echo $swing['id']; ?>" class="btn btn-sm btn-info" title="<?php echo hasRole(['ride operator']) ? 'Update Status' : 'Edit Ride'; ?>"><i class="fas <?php echo hasRole(['ride operator']) ? 'fa-toggle-on' : 'fa-edit'; ?>"></i></a>
                            <?php if (!hasRole(['ride operator'])): ?>
                            <a href="delete.php?id=<?php echo $swing['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
