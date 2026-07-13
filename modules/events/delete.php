<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('manage_events');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Check if active (non-cancelled) tickets exist for this event
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status != 'cancelled'");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: index.php?error=tickets_sold");
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete any cancelled tickets first (to avoid FK constraint)
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE event_id = ? AND status = 'cancelled'");
        $stmt->execute([$id]);
        
        // Now delete the event
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: index.php?error=delete_failed");
        exit;
    }
}

header("Location: index.php?success=event_deleted");
exit;
?>
