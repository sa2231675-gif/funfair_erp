<?php
require_once '../../config/config.php';
requireLogin();

requireAnyPermission(['validate_entry', 'validate_rides']);

$pageTitle = "Universal Validation Scanner";
$message = '';
$alertType = 'info';
$ticket_info = null;

// Fetch swings for pass validation dropdown
$swings = $pdo->query("SELECT id, name FROM swings WHERE status = 'active'")->fetchAll();

// Fetch pending queues (recent un-used items) without date limits/permission constraints
$general_entry_queue = $pdo->query("SELECT ticket_code, created_at FROM tickets WHERE status = 'paid' AND ticket_code LIKE 'ENT-%' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$event_tickets_queue = $pdo->query("SELECT ticket_code, created_at FROM tickets WHERE status = 'paid' AND ticket_code LIKE 'EVT-%' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$ride_tickets_queue = $pdo->query("SELECT ticket_code, created_at FROM ride_tickets WHERE status = 'paid' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$special_passes_queue = $pdo->query("SELECT pass_code as ticket_code, created_at FROM passes WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>



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
                            <div class="form-group">
                                <label>If testing a Special Pass, select your station (Ride):</label>
                                <select name="swing_id" id="swing_id" class="form-control mb-3">
                                    <option value="">-- Auto-detect (Entry/Tickets) or Check Pass Validity --</option>
                                    <?php foreach ($swings as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
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
                
                <div class="card card-dark card-tabs mt-4">
                    <div class="card-header p-0 pt-1 border-bottom-0">
                        <ul class="nav nav-tabs" id="validation-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="general-entry-tab" data-toggle="pill" href="#general-entry" role="tab" aria-controls="general-entry" aria-selected="true">
                                    <i class="fas fa-sign-in-alt text-neon-success mr-1"></i> Entry (<span id="count-general-entry"><?php echo count($general_entry_queue); ?></span>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="event-tickets-tab" data-toggle="pill" href="#event-tickets" role="tab" aria-controls="event-tickets" aria-selected="false">
                                    <i class="fas fa-calendar-day text-neon-info mr-1"></i> Events (<span id="count-event-tickets"><?php echo count($event_tickets_queue); ?></span>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="ride-tickets-tab" data-toggle="pill" href="#ride-tickets" role="tab" aria-controls="ride-tickets" aria-selected="false">
                                    <i class="fas fa-ticket-alt text-neon-warning mr-1"></i> Rides (<span id="count-ride-tickets"><?php echo count($ride_tickets_queue); ?></span>)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="special-passes-tab" data-toggle="pill" href="#special-passes" role="tab" aria-controls="special-passes" aria-selected="false">
                                    <i class="fas fa-id-card text-neon-danger mr-1"></i> Passes (<span id="count-special-passes"><?php echo count($special_passes_queue); ?></span>)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content" id="validation-tabs-content">
                            <!-- General Entry Tab -->
                            <div class="tab-pane fade show active" id="general-entry" role="tabpanel" aria-labelledby="general-entry-tab">
                                <table class="table table-dark-custom m-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="body-general-entry">
                                        <?php foreach ($general_entry_queue as $q): ?>
                                        <tr id="row-<?php echo $q['ticket_code']; ?>" data-category="general-entry">
                                            <td><strong class="text-glow"><?php echo $q['ticket_code']; ?></strong></td>
                                            <td><?php echo date('H:i:s', strtotime($q['created_at'])); ?></td>
                                            <td>
                                                <button type="button" onclick="validateTicket('<?php echo $q['ticket_code']; ?>')" class="btn btn-xs btn-outline-success"><i class="fas fa-check"></i> Validate</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($general_entry_queue)): ?>
                                        <tr class="no-data"><td colspan="3" class="text-center text-muted p-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>No pending entry tickets.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Event Tickets Tab -->
                            <div class="tab-pane fade" id="event-tickets" role="tabpanel" aria-labelledby="event-tickets-tab">
                                <table class="table table-dark-custom m-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="body-event-tickets">
                                        <?php foreach ($event_tickets_queue as $q): ?>
                                        <tr id="row-<?php echo $q['ticket_code']; ?>" data-category="event-tickets">
                                            <td><strong class="text-glow"><?php echo $q['ticket_code']; ?></strong></td>
                                            <td><?php echo date('H:i:s', strtotime($q['created_at'])); ?></td>
                                            <td>
                                                <button type="button" onclick="validateTicket('<?php echo $q['ticket_code']; ?>')" class="btn btn-xs btn-outline-success"><i class="fas fa-check"></i> Validate</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($event_tickets_queue)): ?>
                                        <tr class="no-data"><td colspan="3" class="text-center text-muted p-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>No pending event tickets.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ride Tickets Tab -->
                            <div class="tab-pane fade" id="ride-tickets" role="tabpanel" aria-labelledby="ride-tickets-tab">
                                <table class="table table-dark-custom m-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="body-ride-tickets">
                                        <?php foreach ($ride_tickets_queue as $q): ?>
                                        <tr id="row-<?php echo $q['ticket_code']; ?>" data-category="ride-tickets">
                                            <td><strong class="text-glow"><?php echo $q['ticket_code']; ?></strong></td>
                                            <td><?php echo date('H:i:s', strtotime($q['created_at'])); ?></td>
                                            <td>
                                                <button type="button" onclick="validateTicket('<?php echo $q['ticket_code']; ?>')" class="btn btn-xs btn-outline-success"><i class="fas fa-check"></i> Validate</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($ride_tickets_queue)): ?>
                                        <tr class="no-data"><td colspan="3" class="text-center text-muted p-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>No pending ride tickets.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Special Passes Tab -->
                            <div class="tab-pane fade" id="special-passes" role="tabpanel" aria-labelledby="special-passes-tab">
                                <table class="table table-dark-custom m-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="body-special-passes">
                                        <?php foreach ($special_passes_queue as $q): ?>
                                        <tr id="row-<?php echo $q['ticket_code']; ?>" data-category="special-passes">
                                            <td><strong class="text-glow"><?php echo $q['ticket_code']; ?></strong></td>
                                            <td><?php echo date('H:i:s', strtotime($q['created_at'])); ?></td>
                                            <td>
                                                <button type="button" onclick="validateTicket('<?php echo $q['ticket_code']; ?>')" class="btn btn-xs btn-outline-success"><i class="fas fa-check"></i> Validate</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($special_passes_queue)): ?>
                                        <tr class="no-data"><td colspan="3" class="text-center text-muted p-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>No pending special passes.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('start-camera-btn');
    const syncBtn = document.getElementById('sync-offline-btn');
    const qrReaderDiv = document.getElementById('qr-reader');
    const inputCode = document.getElementById('ticket_code');
    const valForm = document.getElementById('validation-form');
    const offlineBadge = document.getElementById('offline-badge');
    let isScanning = false;
    let html5QrCode = null;

    // Register validateTicket immediately so it works regardless of camera initialization status
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

        fetch('<?php echo BASE_URL; ?>modules/admission/ajax_validate.php', {
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
                if (row) {
                    const category = row.getAttribute('data-category');
                    row.remove();
                    if (category) {
                        const countSpan = document.getElementById('count-' + category);
                        if (countSpan) {
                            let currentCount = parseInt(countSpan.textContent) || 0;
                            if (currentCount > 0) {
                                countSpan.textContent = currentCount - 1;
                            }
                            if (currentCount - 1 === 0) {
                                const tbody = document.getElementById('body-' + category);
                                if (tbody) {
                                    let labelText = "items";
                                    if (category === 'general-entry') labelText = "entry tickets";
                                    else if (category === 'event-tickets') labelText = "event tickets";
                                    else if (category === 'ride-tickets') labelText = "ride tickets";
                                    else if (category === 'special-passes') labelText = "special passes";
                                    
                                    tbody.innerHTML = `<tr class="no-data"><td colspan="3" class="text-center text-muted p-4"><i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>No pending ${labelText}.</td></tr>`;
                                }
                            }
                        }
                    }
                }
                
                if (inputCode) {
                    inputCode.value = '';
                    inputCode.focus();
                }
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
            if (inputCode) {
                inputCode.value = '';
            }
        } else {
            Swal.fire('Not Found', 'Ticket code not in local database. Try syncing when online.', 'error');
        }
    }

    // Monitor Online/Offline Status (Prevent duplicate handlers)
    if (window.funfairOnlineHandler) {
        window.removeEventListener('online', window.funfairOnlineHandler);
    }
    window.funfairOnlineHandler = function() {
        const badge = document.getElementById('offline-badge');
        if (badge) badge.style.display = 'none';
    };
    window.addEventListener('online', window.funfairOnlineHandler);

    if (window.funfairOfflineHandler) {
        window.removeEventListener('offline', window.funfairOfflineHandler);
    }
    window.funfairOfflineHandler = function() {
        const badge = document.getElementById('offline-badge');
        if (badge) badge.style.display = 'block';
    };
    window.addEventListener('offline', window.funfairOfflineHandler);
    
    // Initial check
    if (navigator.onLine) {
        if (offlineBadge) offlineBadge.style.display = 'none';
    } else {
        if (offlineBadge) offlineBadge.style.display = 'block';
    }

    if (valForm) {
        valForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (inputCode) {
                validateTicket(inputCode.value);
            }
        });
    }

    if (syncBtn) {
        syncBtn.addEventListener('click', function() {
            syncBtn.disabled = true;
            syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            
            fetch('<?php echo BASE_URL; ?>modules/admission/fetch_tickets.php')
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
    }

    // Camera Scanner Setup
    function initCameraScanner() {
        if (typeof Html5Qrcode === 'undefined') return;
        try {
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }
        } catch (e) {
            console.error("Failed to instantiate Html5Qrcode:", e);
        }
    }

    // Autoload the Html5Qrcode library immediately if it is not present globally
    if (typeof Html5Qrcode === 'undefined') {
        const script = document.createElement('script');
        script.src = '<?php echo BASE_URL; ?>assets/js/html5-qrcode.min.js';
        script.onload = function() {
            initCameraScanner();
        };
        document.head.appendChild(script);
    } else {
        setTimeout(initCameraScanner, 50);
    }

    if (startBtn) {
        startBtn.addEventListener('click', function() {
            if (typeof Html5Qrcode === 'undefined') {
                Swal.fire({
                    title: 'Loading Camera Library...',
                    text: 'Please wait a moment while the scanner library loads.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                const script = document.createElement('script');
                script.src = '<?php echo BASE_URL; ?>assets/js/html5-qrcode.min.js';
                script.onload = function() {
                    Swal.close();
                    initCameraScanner();
                    toggleCamera();
                };
                script.onerror = function() {
                    Swal.fire('Error', 'Failed to load camera scanner library. Please check your connection.', 'error');
                };
                document.head.appendChild(script);
            } else {
                if (!html5QrCode) {
                    initCameraScanner();
                }
                toggleCamera();
            }
        });
    }

    function toggleCamera() {
        if (!html5QrCode) {
            Swal.fire('Error', 'Camera scanner not initialized.', 'error');
            return;
        }

        if (isScanning) {
            html5QrCode.stop().then(() => {
                if (qrReaderDiv) qrReaderDiv.style.display = 'none';
                startBtn.innerHTML = '<i class="fas fa-camera"></i> Camera';
                isScanning = false;
            });
        } else {
            if (qrReaderDiv) qrReaderDiv.style.display = 'block';
            startBtn.innerHTML = '<i class="fas fa-times"></i> Stop';
            isScanning = true;
            
            const config = { fps: 15, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
                validateTicket(decodedText);
            }).catch(err => {
                console.error(err);
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length > 0) {
                        html5QrCode.start(cameras[cameras.length-1].id, config, (decodedText) => {
                            validateTicket(decodedText);
                        });
                    } else {
                        Swal.fire('Camera Error', 'Camera not found or permission denied.', 'error');
                        if (qrReaderDiv) qrReaderDiv.style.display = 'none';
                        isScanning = false;
                        startBtn.innerHTML = '<i class="fas fa-camera"></i> Camera';
                    }
                }).catch(e => {
                    Swal.fire('Camera Error', 'Could not access camera list.', 'error');
                    if (qrReaderDiv) qrReaderDiv.style.display = 'none';
                    isScanning = false;
                    startBtn.innerHTML = '<i class="fas fa-camera"></i> Camera';
                });
            });
        }
    }

    // SPA Navigation Cleanup: stop camera when user navigates away
    const cleanupInterval = setInterval(function() {
        if (!document.getElementById('qr-reader')) {
            clearInterval(cleanupInterval);
            if (html5QrCode) {
                if (isScanning) {
                    html5QrCode.stop().then(() => {
                        console.log("Scanner stopped successfully on navigation.");
                    }).catch(err => {
                        console.error("Error stopping scanner on navigation:", err);
                    });
                }
            }
        }
    }, 500);
});
</script>
