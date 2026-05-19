<?php
$my_shift = null;
if (isLoggedIn() && isset($pdo)) {
    $stmt = $pdo->prepare("
        SELECT es.id, es.status 
        FROM employee_shifts es 
        JOIN employees e ON es.employee_id = e.id 
        WHERE e.user_id = ? AND es.shift_date = ?
    ");
    $stmt->execute([$_SESSION['user_id'], date('Y-m-d')]);
    $my_shift = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <?php if ($my_shift): ?>
        <li class="nav-item mr-3 d-flex align-items-center">
            <?php if (in_array($my_shift['status'], ['scheduled', 'paused'])): ?>
                <button onclick="handleMyShift(<?= $my_shift['id'] ?>, 'checkin')" class="btn btn-sm btn-outline-success border-glow-success" id="myShiftBtn">
                    <i class="fas fa-sign-in-alt"></i> Check In (Start/Resume Work)
                </button>
            <?php elseif ($my_shift['status'] == 'started'): ?>
                <button onclick="handleMyShift(<?= $my_shift['id'] ?>, 'checkout')" class="btn btn-sm btn-outline-warning border-glow-warning" id="myShiftBtn">
                    <i class="fas fa-sign-out-alt"></i> Check Out (Pause/Break)
                </button>
            <?php endif; ?>
        </li>
        <script>
        function handleMyShift(id, action) {
            fetch('<?php echo BASE_URL; ?>modules/hr/ajax_shift_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id + '&action=' + action
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        </script>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link" href="#" id="theme-toggle" title="Toggle Theme">
                <i class="fas fa-moon" id="theme-icon"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php" role="button">
                <i class="fas fa-sign-out-alt text-neon-danger"></i> Logout
            </a>
        </li>
    </ul>
</nav>
