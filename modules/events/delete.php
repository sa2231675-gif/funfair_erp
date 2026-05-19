<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_events');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if tickets are sold for this event
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: index.php?error=tickets_sold");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?success=event_deleted");
exit;
?>
