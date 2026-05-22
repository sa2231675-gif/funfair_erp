<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('book_tickets');

if (hasRole(['ride operator'])) {
    header("Location: " . BASE_URL . "index.php?error=unauthorized");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if ticket is used
    $stmt = $pdo->prepare("SELECT status FROM tickets WHERE id = ?");
    $stmt->execute([$id]);
    $status = $stmt->fetchColumn();
    
    if ($status === 'used') {
        header("Location: index.php?error=already_used");
    } else {
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        
        // Also cancel associated payment
        $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE ticket_id = ?");
        $stmt->execute([$id]);
        
        header("Location: index.php?success=ticket_cancelled");
    }
} else {
    header("Location: index.php");
}
exit;
?>
