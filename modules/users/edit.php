<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_users');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $status = $_POST['status'];
    
    // Check if password is being updated
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, password=?, role_id=?, status=? WHERE id=?");
        $stmt->execute([$full_name, $username, $email, $password, $role_id, $status, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, role_id=?, status=? WHERE id=?");
        $stmt->execute([$full_name, $username, $email, $role_id, $status, $id]);
    }
    header("Location: index.php?success=user_updated");
    exit;
}

$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll();

$pageTitle = "Edit User";
include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Edit User: <?php echo $user['username']; ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark card-info">
            <div class="card-body">
                <form action="" method="post">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo $user['username']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $user['email']; ?>" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Password (Leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Role</label>
                            <select name="role_id" class="form-control" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                        <?php echo $role['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-info">Update User</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
