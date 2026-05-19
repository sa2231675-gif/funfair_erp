<?php
require_once '../../config/config.php';

if (!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

// Fetch all paid/pending tickets from today onwards
$tickets = [];

$stmt = $pdo->query("SELECT ticket_code FROM tickets WHERE status = 'paid'");
while($row = $stmt->fetch()) { $tickets[] = $row['ticket_code']; }

$stmt = $pdo->query("SELECT ticket_code FROM ride_tickets WHERE status = 'paid'");
while($row = $stmt->fetch()) { $tickets[] = $row['ticket_code']; }

$stmt = $pdo->query("SELECT pass_code FROM passes WHERE status IN ('pending', 'active')");
while($row = $stmt->fetch()) { $tickets[] = $row['pass_code']; }

echo json_encode($tickets);
?>
