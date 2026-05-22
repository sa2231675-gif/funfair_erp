<?php
require_once '../../config/config.php';
requireLogin();

if (!hasRole(['superadmin', 'admin']) && !hasPermission('view_reports')) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit;
}

$pageTitle = "System Activity Logs";

$stmt = $pdo->query("SELECT a.*, u.username, u.full_name, r.name as role_name
                     FROM activity_logs a
                     JOIN users u ON a.user_id = u.id
                     LEFT JOIN roles r ON u.role_id = r.id
                     ORDER BY a.created_at DESC LIMIT 500");
$logs = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">System Activity Logs</h1>
            <p class="text-muted text-sm">Detailed tracking of user actions across the platform.</p>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark">
            <div class="card-header border-0">
                <h3 class="card-title text-neon-primary"><i class="fas fa-history mr-2"></i> Recent Activities</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-dark-custom datatable m-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User (Role)</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                <small class="text-neon-info">@<?php echo htmlspecialchars($log['username']); ?> (<?php echo htmlspecialchars($log['role_name'] ?? 'N/A'); ?>)</small>
                            </td>
                            <td><span class="badge badge-primary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
