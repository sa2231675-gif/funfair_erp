<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_roles');

$pageTitle = "Roles & Permissions";

$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Roles Management</h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark">
            <div class="card-body">
                <table class="table table-dark-custom table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo $role['id']; ?></td>
                            <td><?php echo $role['name']; ?></td>
                            <td>
                                <a href="permissions.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-key"></i> Manage Permissions</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
