<?php
require_once '../../config/config.php';
requireLogin();

// Only Super Admin can manage permissions
if (!hasRole(['superadmin'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit();
}

$page_title = 'Role Permissions';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    try {
        $pdo->beginTransaction();
        
        // Clear all existing permissions
        $pdo->exec("DELETE FROM role_permissions");
        
        // Insert new permissions
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($_POST['permissions'] as $role_id => $perm_ids) {
                foreach ($perm_ids as $perm_id) {
                    $stmt->execute([$role_id, $perm_id]);
                }
            }
        }
        
        $pdo->commit();
        $success = "Permissions updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating permissions: " . $e->getMessage();
    }
}

// Fetch Roles (exclude Super Admin as they have all access by default)
$roles = $pdo->query("SELECT * FROM roles WHERE name != 'Super Admin'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Permissions
$permissions = $pdo->query("SELECT * FROM permissions")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Current Mapping
$current_mappings = [];
$map_stmt = $pdo->query("SELECT role_id, permission_id FROM role_permissions");
while ($row = $map_stmt->fetch()) {
    $current_mappings[$row['role_id']][] = $row['permission_id'];
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0 text-glow"><i class="fas fa-shield-alt text-neon-danger mr-2"></i> Role Permissions Matrix</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Success!</h5> <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card card-dark">
                <div class="card-header border-0">
                    <h3 class="card-title text-glow">Manage Access Controls</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="update_permissions" value="1">
                    <div class="card-body table-responsive p-0">
                        <table class="table table-dark-custom table-hover table-bordered text-center">
                            <thead>
                                <tr>
                                    <th class="text-left">Permissions / Roles</th>
                                    <?php foreach ($roles as $role): ?>
                                        <th class="text-neon-info"><?= htmlspecialchars($role['name']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permissions as $perm): ?>
                                    <tr>
                                        <td class="text-left font-weight-bold">
                                            <?= htmlspecialchars($perm['name']) ?>
                                            <div class="small text-muted"><?= htmlspecialchars($perm['slug']) ?></div>
                                        </td>
                                        <?php foreach ($roles as $role): 
                                            $isChecked = isset($current_mappings[$role['id']]) && in_array($perm['id'], $current_mappings[$role['id']]);
                                        ?>
                                            <td>
                                                <div class="icheck-info d-inline">
                                                    <input type="checkbox" id="checkbox_<?= $role['id'] ?>_<?= $perm['id'] ?>" name="permissions[<?= $role['id'] ?>][]" value="<?= $perm['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                                                    <label for="checkbox_<?= $role['id'] ?>_<?= $perm['id'] ?>"></label>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer border-0 text-right">
                        <button type="reset" class="btn btn-outline-secondary mr-2">Reset</button>
                        <button type="submit" class="btn btn-outline-danger"><i class="fas fa-save mr-1"></i> Save Permissions</button>
                    </div>
                </form>
            </div>
            
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
