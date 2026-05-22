<?php
require_once '../../config/config.php';

if (!isLoggedIn() || (!hasPermission('validate_entry') && !hasPermission('validate_rides'))) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

function getTicketTypeLabel($code) {
    if (strpos($code, 'EVT-') === 0 || strpos($code, 'ENT-') === 0) return 'Event Entry';
    if (strpos($code, 'RID-') === 0) return 'Ride Ticket';
    if (strpos($code, 'PASS-') === 0) return 'Special Pass';
    return 'Unknown';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_code'])) {
    $code = trim($_POST['ticket_code']);
    $type = getTicketTypeLabel($code);

    try {
        if ($type == 'Event Entry') {
            $stmt = $pdo->prepare("SELECT t.*, e.title as item_name FROM tickets t JOIN events e ON t.event_id = e.id WHERE t.ticket_code = ?");
            $stmt->execute([$code]);
            $ticket = $stmt->fetch();

            if (!$ticket) throw new Exception("Invalid Event Ticket Code!");
            if ($ticket['status'] === 'used') throw new Exception("Already USED at " . $ticket['created_at']);
            if ($ticket['status'] === 'cancelled') throw new Exception("Ticket is CANCELLED.");

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE tickets SET status = 'used' WHERE id = ?")->execute([$ticket['id']]);
            $pdo->prepare("INSERT INTO admission_logs (ticket_id, operator_id) VALUES (?, ?)")->execute([$ticket['id'], $_SESSION['user_id']]);
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'ENTRY GRANTED!', 'info' => ['code' => $code, 'type' => $type, 'item' => $ticket['item_name']]]);
            exit();

        } elseif ($type == 'Ride Ticket') {
            $stmt = $pdo->prepare("SELECT rt.*, s.name as item_name FROM ride_tickets rt JOIN swings s ON rt.swing_id = s.id WHERE rt.ticket_code = ?");
            $stmt->execute([$code]);
            $ticket = $stmt->fetch();

            if (!$ticket) throw new Exception("Invalid Ride Ticket Code!");
            if ($ticket['status'] === 'used') throw new Exception("Already USED at " . $ticket['created_at']);
            if ($ticket['status'] === 'cancelled') throw new Exception("Ticket is CANCELLED.");

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE ride_tickets SET status = 'used' WHERE id = ?")->execute([$ticket['id']]);
            $pdo->prepare("INSERT INTO ride_usage_logs (ride_ticket_id, operator_id) VALUES (?, ?)")->execute([$ticket['id'], $_SESSION['user_id']]);
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'RIDE ACCESS GRANTED!', 'info' => ['code' => $code, 'type' => $type, 'item' => $ticket['item_name']]]);
            exit();

        } elseif ($type == 'Special Pass') {
            $stmt = $pdo->prepare("SELECT * FROM passes WHERE pass_code = ?");
            $stmt->execute([$code]);
            $pass = $stmt->fetch();
            
            if (!$pass) throw new Exception("Invalid Pass Code!");
            
            $current_time = date('Y-m-d H:i:s');

            $just_activated = false;
            if ($pass['status'] == 'pending') {
                $valid_until = date('Y-m-d H:i:s', strtotime('+3 hours'));
                $pdo->prepare("UPDATE passes SET status = 'active', valid_from = ?, valid_until = ? WHERE id = ?")->execute([$current_time, $valid_until, $pass['id']]);
                $pass['status'] = 'active';
                $pass['valid_until'] = $valid_until;
                $just_activated = true;
            }

            if ($pass['status'] == 'expired' || ($pass['status'] == 'active' && $current_time > $pass['valid_until'])) {
                if ($pass['status'] != 'expired') $pdo->prepare("UPDATE passes SET status = 'expired' WHERE id = ?")->execute([$pass['id']]);
                throw new Exception("Pass EXPIRED at " . date('h:i A', strtotime($pass['valid_until'])));
            }
            if ($pass['status'] != 'active') throw new Exception("Pass inactive.");

            $swing_id = $_POST['swing_id'] ?? null;
            if (!$swing_id) {
                $msg = $just_activated ? 'PASS ACTIVATED! Timer Started.' : 'PASS IS VALID!';
                echo json_encode(['success' => true, 'message' => $msg . ' (Expires: ' . date('h:i A', strtotime($pass['valid_until'])) . ')', 'info' => ['code' => $code, 'type' => $type, 'item' => 'Universal Access']]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM ride_usage_logs WHERE pass_id = ? AND swing_id = ?");
                $stmt->execute([$pass['id'], $swing_id]);
                $count = $stmt->fetchColumn();
                if ($count >= 3) throw new Exception("RIDE LIMIT REACHED (3/3)!");
                
                $pdo->prepare("INSERT INTO ride_usage_logs (pass_id, swing_id, operator_id) VALUES (?, ?, ?)")->execute([$pass['id'], $swing_id, $_SESSION['user_id']]);
                
                $msg = $just_activated ? 'PASS ACTIVATED & ACCESS GRANTED!' : 'RIDE ACCESS GRANTED!';
                echo json_encode(['success' => true, 'message' => $msg . ' (Usage ' . ($count + 1) . '/3)', 'info' => ['code' => $code, 'type' => $type, 'item' => 'Ride Station Access']]);
            }
            exit();
        } else {
            throw new Exception("Unknown Barcode Format.");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
