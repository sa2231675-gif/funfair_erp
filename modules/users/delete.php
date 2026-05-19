<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_users');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Prevent self-deletion
    if ($id == $_SESSION['user_id']) {
        header("Location: index.php?error=self_delete");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?success=user_deleted");
exit;
?>
