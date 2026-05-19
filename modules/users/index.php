<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_users');

$pageTitle = "Users List";

$stmt = $pdo->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id");
$users = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="text-glow">Users Management</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add User</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-outline card-primary">
            <div class="card-body">
                <table class="table table-dark-custom table-dark-custom datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['full_name']; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><span class="badge badge-info"><?php echo $user['role_name']; ?></span></td>
                            <td><span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo $user['status']; ?></span></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
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
