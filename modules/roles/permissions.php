<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_roles');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$role_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$role_id]);
$role = $stmt->fetch();

if (!$role) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perms = $_POST['perms'] ?? [];
    
    // Clear existing permissions
    $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    
    // Insert new permissions
    if (!empty($perms)) {
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($perms as $perm_id) {
            $stmt->execute([$role_id, $perm_id]);
        }
    }
    header("Location: permissions.php?id=$role_id&success=saved");
    exit;
}

// All available permissions
$stmt = $pdo->query("SELECT * FROM permissions");
$all_permissions = $stmt->fetchAll();

// Current role permissions
$stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$stmt->execute([$role_id]);
$current_perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Manage Permissions - " . $role['name'];
include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Permissions for: <?php echo $role['name']; ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="card card-dark">
            <div class="card-body">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Permissions updated successfully!</div>
                <?php endif; ?>
                
                <form action="" method="post">
                    <div class="row">
                        <?php foreach ($all_permissions as $p): ?>
                        <div class="col-md-3">
                            <div class="custom-control custom-checkbox mb-3">
                                <input class="custom-control-input" type="checkbox" name="perms[]" id="perm_<?php echo $p['id']; ?>" value="<?php echo $p['id']; ?>" <?php echo in_array($p['id'], $current_perms) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="perm_<?php echo $p['id']; ?>">
                                    <?php echo $p['name']; ?> <br>
                                    <small class="text-muted"><?php echo $p['slug']; ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="index.php" class="btn btn-secondary">Back to Roles</a>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
