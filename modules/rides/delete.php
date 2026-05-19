<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_swings');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if tickets are sold for this ride
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ride_tickets WHERE swing_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: index.php?error=tickets_sold");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM swings WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?success=ride_deleted");
exit;
?>
