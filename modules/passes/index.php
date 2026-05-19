<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('view_reports');

$pageTitle = "Special Passes";

$stmt = $pdo->query("SELECT p.*, u.username as sold_by 
                     FROM passes p 
                     JOIN users u ON p.booked_by = u.id 
                     ORDER BY p.created_at DESC");
$passes = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="text-glow">Special Passes <small class="text-neon-info">(3-Hour / 3 Rides Per Swing)</small></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="buy.php" class="btn btn-dark-custom btn-glow-success"><i class="fas fa-plus mr-2"></i> Sell New Pass</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-outline card-warning">
            <div class="card-body p-0">
                <table class="table table-dark-custom table-hover">
                    <thead>
                        <tr>
                            <th>Pass Code</th>
                            <th>Amount</th>
                            <th>Valid From</th>
                            <th>Valid Until</th>
                            <th>Status</th>
                            <th>Sold By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($passes as $p): ?>
                        <?php
                            $is_pending = ($p['status'] == 'pending');
                            $expiry = $is_pending ? 0 : strtotime($p['valid_until']);
                            $is_expired = (!$is_pending && time() > $expiry) || $p['status'] == 'expired';
                            
                            $status_class = $is_expired ? 'danger' : ($is_pending ? 'info' : ($p['status'] == 'active' ? 'success' : 'warning'));
                            $status_text = $is_expired ? 'EXPIRED' : strtoupper($p['status']);
                        ?>
                        <tr>
                            <td class="font-weight-bold"><?php echo $p['pass_code']; ?></td>
                            <td>Rs. <?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo $p['valid_from'] ? date('M d, H:i', strtotime($p['valid_from'])) : '<span class="text-muted">-</span>'; ?></td>
                            <td class="<?php echo $is_expired ? 'text-danger' : ($is_pending ? 'text-info' : 'text-neon-success'); ?>">
                                <?php echo $p['valid_until'] ? date('M d, H:i', strtotime($p['valid_until'])) : '<span class="text-muted">TBD</span>'; ?>
                            </td>
                            <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo $p['sold_by']; ?></td>
                            <td>
                                <a href="print.php?code=<?php echo $p['pass_code']; ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-print"></i> Print</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($passes)): ?>
                            <tr><td colspan="7" class="text-center">No passes found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
