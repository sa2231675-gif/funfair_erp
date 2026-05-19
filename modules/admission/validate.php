<?php
require_once '../../config/config.php';
requireLogin();

// Note: Ensure user has at least one validation permission
if (!hasPermission('validate_entry') && !hasPermission('validate_rides')) {
    die("<h2>Unauthorized Access</h2>");
}

$pageTitle = "Universal Validation Scanner";
$message = '';
$alertType = 'info';
$ticket_info = null;

// Note: Validation logic moved to ajax_validate.php for smoother UX without page refresh.

// Fetch swings for pass validation dropdown
$swings = [];
// Fetch pending tickets queue (recent un-used tickets created today)
$pending_queue = [];
$stmt_evt = $pdo->query("SELECT ticket_code, created_at, 'Event/Entry' as type FROM tickets WHERE status = 'paid' AND DATE(created_at) = CURDATE()");
$pending_queue = array_merge($pending_queue, $stmt_evt->fetchAll());

$stmt_rid = $pdo->query("SELECT ticket_code, created_at, 'Ride Ticket' as type FROM ride_tickets WHERE status = 'paid' AND DATE(created_at) = CURDATE()");
$pending_queue = array_merge($pending_queue, $stmt_rid->fetchAll());

$stmt_pass = $pdo->query("SELECT pass_code as ticket_code, created_at, 'Special Pass' as type FROM passes WHERE status = 'pending' AND DATE(created_at) = CURDATE()");
$pending_queue = array_merge($pending_queue, $stmt_pass->fetchAll(PDO::FETCH_ASSOC));

usort($pending_queue, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']); // Newest first
});
$pending_queue = array_slice($pending_queue, 0, 10); // Show up to 10 latest pending

if (hasPermission('validate_rides')) {
    $stmt = $pdo->query("SELECT id, name FROM swings WHERE status = 'active'");
    $swings = $stmt->fetchAll();
}
include '../../includes/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="text-glow">Universal Validation Scanner</h1>
            <p class="text-muted text-sm">Scan Event Entries, Ride Tickets, or Special Passes</p>
        </div>
    </section>

    <section class="content">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-dark card-outline card-success">
                    <div class="card-body">
                        <form id="validation-form" class="mb-4">
                            <?php if (hasPermission('validate_rides')): ?>
                            <div class="form-group">
                                <label>If testing a Special Pass, select your station (Ride):</label>
                                <select name="swing_id" id="swing_id" class="form-control mb-3">
                                    <option value="">-- Auto-detect (Entry/Tickets) or Check Pass Validity --</option>
                                    <?php foreach ($swings as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div id="qr-reader" style="width:100%; margin-bottom: 20px; border-radius: 10px; overflow: hidden; display: none;"></div>
                            
                            <div class="input-group input-group-lg">
                                <input type="text" name="ticket_code" id="ticket_code" class="form-control" placeholder="Scan Barcode (ENT-, RID-, PASS-)" value="" required autofocus autocomplete="off">
                                <span class="input-group-append">
                                    <button type="submit" class="btn btn-success btn-flat"><i class="fas fa-barcode"></i> Scan</button>
                                </span>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-6">
                                    <button type="button" id="start-camera-btn" class="btn btn-outline-info btn-block"><i class="fas fa-camera"></i> Camera</button>
                                </div>
                                <div class="col-6">
                                    <button type="button" id="sync-offline-btn" class="btn btn-outline-warning btn-block" title="Sync tickets for offline use"><i class="fas fa-sync"></i> Sync Offline</button>
                                </div>
                            </div>
                            <div id="offline-badge" class="badge badge-danger mt-2 w-100 py-2" style="display: none;"><i class="fas fa-wifi-slash"></i> WORKING OFFLINE - Using local data</div>
                        </form>

                        <!-- Results shown via SweetAlert2 popups now -->

                    </div>
                </div>
                
                <div class="card card-dark mt-4">
                    <div class="card-header border-0">
                        <h3 class="card-title text-neon-info"><i class="fas fa-list mt-1 mr-1"></i> Pending Validation Queue</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-dark-custom m-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_queue as $q): ?>
                                <tr id="row-<?php echo $q['ticket_code']; ?>">
                                    <td><strong class="text-glow"><?php echo $q['ticket_code']; ?></strong></td>
                                    <td><span class="badge badge-secondary"><?php echo $q['type']; ?></span></td>
                                    <td><?php echo date('H:i:s', strtotime($q['created_at'])); ?></td>
                                    <td>
                                        <button type="button" onclick="validateTicket('<?php echo $q['ticket_code']; ?>')" class="btn btn-xs btn-outline-success"><i class="fas fa-check"></i> Validate</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pending_queue)): ?>
                                <tr><td colspan="4" class="text-center text-muted p-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>All caught up! The queue is empty.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const html5QrCode = new Html5Qrcode("qr-reader");
    const startBtn = document.getElementById('start-camera-btn');
    const syncBtn = document.getElementById('sync-offline-btn');
    const qrReaderDiv = document.getElementById('qr-reader');
    const inputCode = document.getElementById('ticket_code');
    const valForm = document.getElementById('validation-form');
    const offlineBadge = document.getElementById('offline-badge');
    let isScanning = false;

    // Monitor Online/Offline Status
    function updateOnlineStatus() {
        if (navigator.onLine) {
            offlineBadge.style.display = 'none';
        } else {
            offlineBadge.style.display = 'block';
        }
    }
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    valForm.addEventListener('submit', function(e) {
        e.preventDefault();
        validateTicket(inputCode.value);
    });

    syncBtn.addEventListener('click', function() {
        syncBtn.disabled = true;
        syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
        
        fetch('fetch_tickets.php')
            .then(res => res.json())
            .then(data => {
                localStorage.setItem('offline_tickets', JSON.stringify(data));
                localStorage.setItem('last_sync', new Date().toLocaleString());
                Swal.fire('Synced!', `Downloaded ${data.length} tickets for offline use.`, 'success');
            })
            .catch(err => Swal.fire('Error', 'Failed to sync. Are you online?', 'error'))
            .finally(() => {
                syncBtn.disabled = false;
                syncBtn.innerHTML = '<i class="fas fa-sync"></i> Sync Offline';
            });
    });

    window.validateTicket = function(code) {
        if (!code) return;
        const swingId = document.getElementById('swing_id') ? document.getElementById('swing_id').value : '';
        
        // If OFFLINE, perform local check
        if (!navigator.onLine) {
            handleOfflineValidation(code);
            return;
        }

        // Online Validation
        Swal.fire({
            title: 'Validating...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('ajax_validate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ticket_code=' + encodeURIComponent(code) + '&swing_id=' + swingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Access Granted!',
                    html: `<div class="text-left mt-3"><p>${data.message}</p><p>Code: ${data.info.code}</p></div>`,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                const row = document.getElementById('row-' + code);
                if (row) row.remove();
                
                inputCode.value = '';
                inputCode.focus();
            } else {
                Swal.fire('Error', data.error, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Network Issue', 'Switching to offline check...', 'warning');
            handleOfflineValidation(code);
        });
    };

    function handleOfflineValidation(code) {
        const offlineData = JSON.parse(localStorage.getItem('offline_tickets') || '[]');
        const usedOffline = JSON.parse(localStorage.getItem('used_offline') || '[]');

        if (usedOffline.includes(code)) {
            Swal.fire('Already Used', 'This ticket was already scanned offline.', 'error');
        } else if (offlineData.includes(code)) {
            usedOffline.push(code);
            localStorage.setItem('used_offline', JSON.stringify(usedOffline));
            
            Swal.fire({
                icon: 'success',
                title: 'Offline Granted!',
                text: 'Ticket is valid. Mark as used locally.',
                footer: 'Sync when online to update server.'
            });
            inputCode.value = '';
        } else {
            Swal.fire('Not Found', 'Ticket code not in local database. Try syncing when online.', 'error');
        }
    }

    startBtn.addEventListener('click', function() {
        if (isScanning) {
            html5QrCode.stop().then(() => {
                qrReaderDiv.style.display = 'none';
                startBtn.innerHTML = '<i class="fas fa-camera"></i> Camera';
                isScanning = false;
            });
        } else {
            qrReaderDiv.style.display = 'block';
            startBtn.innerHTML = '<i class="fas fa-times"></i> Stop';
            isScanning = true;
            
            // Modern initialization
            const config = { fps: 15, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
                validateTicket(decodedText);
                // Optionally stop after one scan or continue
                // html5QrCode.stop()...
            }).catch(err => {
                console.error(err);
                // Fallback for some browsers/devices
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length > 0) {
                        html5QrCode.start(cameras[cameras.length-1].id, config, (decodedText) => {
                            validateTicket(decodedText);
                        });
                    } else {
                        alert("Camera not found or permission denied.");
                        qrReaderDiv.style.display = 'none';
                        isScanning = false;
                        startBtn.innerHTML = '<i class="fas fa-camera"></i> Camera';
                    }
                });
            });
        }
    });
});
</script>
