<?php
require_once '../../config/config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    
    // Get shift
    $stmt = $pdo->prepare("SELECT * FROM employee_shifts WHERE id = ?");
    $stmt->execute([$id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shift) {
        echo json_encode(['success' => false, 'error' => 'Shift not found.']);
        exit();
    }
    
    if ($shift['shift_date'] < date('Y-m-d')) {
        echo json_encode(['success' => false, 'error' => 'Backdated check-ins are not allowed.']);
        exit();
    }
    
    if ($action == 'checkin') {
        if (in_array($shift['status'], ['scheduled', 'paused', 'completed'])) {
            // First checkin ever
            if (empty($shift['check_in_time'])) {
                $query = "UPDATE employee_shifts SET status = 'started', check_in_time = NOW(), last_check_in_time = NOW(), check_in_count = check_in_count + 1 WHERE id = ?";
            } else {
                $query = "UPDATE employee_shifts SET status = 'started', last_check_in_time = NOW(), check_in_count = check_in_count + 1 WHERE id = ?";
            }
            $pdo->prepare($query)->execute([$id]);
            
            // Insert log
            $pdo->prepare("INSERT INTO employee_attendance_logs (shift_id, employee_id, action_type) VALUES (?, ?, 'check_in')")->execute([$id, $shift['employee_id']]);
            
            // Get new counts for return
            $stmt = $pdo->prepare("SELECT check_in_count, check_out_count FROM employee_shifts WHERE id = ?");
            $stmt->execute([$id]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'status' => 'started', 'in_count' => $counts['check_in_count'], 'out_count' => $counts['check_out_count'], 'message' => 'Checked in successfully']);
            exit();
        }
    } elseif ($action == 'checkout') {
        if ($shift['status'] == 'started') {
            $check_time_col = !empty($shift['last_check_in_time']) ? 'last_check_in_time' : 'check_in_time';
            
            // Calculate minutes since last check in
            $minutes_stmt = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, $check_time_col, NOW()) as session_mins FROM employee_shifts WHERE id = ?");
            $minutes_stmt->execute([$id]);
            $mins = $minutes_stmt->fetchColumn();
            
            $new_total_mins = $shift['total_working_minutes'] + $mins;
            $h = floor($new_total_mins / 60);
            $m = $new_total_mins % 60;
            $formatted_time = $h . "h " . $m . "m";

            // Update main shift record with counts and formatted time
            $query = "UPDATE employee_shifts SET 
                      status = 'paused', 
                      check_out_time = NOW(),
                      total_working_minutes = ?,
                      working_time_formatted = ?,
                      check_out_count = check_out_count + 1,
                      last_check_in_time = NULL 
                      WHERE id = ?";
            $pdo->prepare($query)->execute([$new_total_mins, $formatted_time, $id]);
            
            // Insert log
            $pdo->prepare("INSERT INTO employee_attendance_logs (shift_id, employee_id, action_type) VALUES (?, ?, 'check_out')")->execute([$id, $shift['employee_id']]);
            
            // Get new counts
            $stmt = $pdo->prepare("SELECT check_in_count, check_out_count FROM employee_shifts WHERE id = ?");
            $stmt->execute([$id]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true, 
                'status' => 'paused', 
                'total_minutes' => $new_total_mins, 
                'formatted_time' => $formatted_time,
                'in_count' => $counts['check_in_count'],
                'out_count' => $counts['check_out_count'],
                'message' => 'Checked out successfully'
            ]);
            exit();
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid state for this action.']);
    exit();
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
?>
