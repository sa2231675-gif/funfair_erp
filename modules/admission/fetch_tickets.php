<?php
require_once '../../config/config.php';

if (!isLoggedIn() || (!hasPermission('validate_entry') && !hasPermission('validate_rides'))) {
    echo json_encode([]);
    exit;
}

// Fetch all paid/pending tickets
$tickets = [];

$stmt = $pdo->query("SELECT ticket_code FROM tickets WHERE status = 'paid'");
while($row = $stmt->fetch()) { $tickets[] = $row['ticket_code']; }

$stmt = $pdo->query("SELECT ticket_code FROM ride_tickets WHERE status = 'paid'");
while($row = $stmt->fetch()) { $tickets[] = $row['ticket_code']; }

$stmt = $pdo->query("SELECT pass_code FROM passes WHERE status IN ('pending', 'active')");
$tickets_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($tickets_data as $row) { $tickets[] = $row['pass_code']; }

echo json_encode($tickets);
?>
