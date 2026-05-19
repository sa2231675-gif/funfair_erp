<?php
require_once '../../config/config.php';
requireLogin();

$pageTitle = "My Profile";
$user_id = $_SESSION['user_id'];
$message = '';
$alertType = 'info';

$stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Generate User Stats
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admission_logs WHERE operator_id = ? AND DATE(entry_time) = ?");
$stmt->execute([$user_id, $today]);
$events_validated = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ride_usage_logs WHERE operator_id = ? AND DATE(usage_time) = ?");
$stmt->execute([$user_id, $today]);
$rides_validated = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
$stmt->execute([$user_id]);
$my_activities = $stmt->fetchAll();

// Employee Shift Check-in/out logic
$shift_result = null;
$stmt = $pdo->prepare("SELECT es.* FROM employee_shifts es JOIN employees e ON es.employee_id = e.id WHERE e.user_id = ? AND es.shift_date = ? LIMIT 1");
$stmt->execute([$user_id, $today]);
$shift_result = $stmt->fetch();

// Removed old check in/out logic, now handled via AJAX

if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
    $alertType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                throw new Exception("Passwords do not match!");
            }
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $hashed, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $user_id]);
        }
        
        // Handle Profile Picture Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_info = pathinfo($_FILES['profile_pic']['name']);
            $file_ext = strtolower($file_info['extension']);
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_path = '../../uploads/profile_pics/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $stmt->execute([$new_filename, $user_id]);
                } else {
                    throw new Exception("Failed to upload image.");
                }
            } else {
                throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.");
            }
        }
        
        $_SESSION['full_name'] = $full_name; // Update session name
        $message = "Profile updated successfully!";
        $alertType = 'success';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        
        // Log Profile Update
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, 'Profile Updated', "User updated their name/email or password."]);
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $alertType = 'danger';
    }
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>User Profile</h1>
        </div>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="card card-dark card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center position-relative">
                            <a href="#" data-toggle="modal" data-target="#profilePicModal" style="display: inline-block;">
                                <?php if(!empty($user['profile_pic'])): ?>
                                    <img class="profile-user-img img-fluid img-circle" src="<?php echo BASE_URL; ?>uploads/profile_pics/<?php echo $user['profile_pic']; ?>" alt="User profile picture" style="width: 120px; height: 120px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-5x text-muted border border-secondary rounded-circle p-2" style="width: 120px; height: 120px; display: inline-flex; justify-content: center; align-items: center;"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                        <h3 class="profile-username text-center"><?php echo $user['full_name']; ?></h3>
                        <p class="text-muted text-center"><?php echo $user['role_name']; ?></p>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Username</b> <a class="float-right"><?php echo $user['username']; ?></a>
                            </li>
                            <li class="list-group-item">
                                <b>Status</b> <a class="float-right"><span class="badge badge-success"><?php echo strtoupper($user['status']); ?></span></a>
                            </li>
                        </ul>
                        <div class="mt-4">
                            <h5 class="text-glow mb-3"><i class="fas fa-chart-line text-neon-success mr-2"></i> Today's Performance</h5>
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <span><i class="fas fa-door-open text-neon-primary mr-1"></i> Entries Validated</span> 
                                    <strong class="float-right text-neon-primary"><?php echo $events_validated; ?></strong>
                                </li>
                                <li class="list-group-item">
                                    <span><i class="fas fa-horse text-neon-warning mr-1"></i> Rides Handled</span> 
                                    <strong class="float-right text-neon-warning"><?php echo $rides_validated; ?></strong>
                                </li>
                            </ul>
                        </div>
                        <?php if($shift_result): ?>
                        <div class="mt-4">
                            <h5 class="text-glow mb-3"><i class="fas fa-clock text-neon-info mr-2"></i> My Shift Today</h5>
                            <div class="p-3 rounded border border-info bg-white text-center shadow-sm">
                                <p class="mb-2"><strong class="text-dark">Time:</strong> <span class="text-neon-info"><?= date('h:i A', strtotime($shift_result['start_time'])) ?></span> - <span class="text-neon-warning"><?= date('h:i A', strtotime($shift_result['end_time'])) ?></span></p>
                                <p class="mb-3">
                                    <strong class="text-dark">Status:</strong> 
                                    <span id="my-shift-status">
                                    <?php 
                                        if ($shift_result['status'] == 'scheduled') echo '<span class="badge badge-info">Scheduled</span>';
                                        elseif ($shift_result['status'] == 'started') echo '<span class="badge badge-primary">Started at '.date('h:i A', strtotime($shift_result['check_in_time'])).'</span>';
                                        elseif ($shift_result['status'] == 'paused') echo '<span class="badge badge-warning">Paused</span>';
                                        elseif ($shift_result['status'] == 'completed') echo '<span class="badge badge-success">Completed</span>';
                                        elseif ($shift_result['status'] == 'cancelled') echo '<span class="badge badge-danger">Cancelled</span>';
                                    ?>
                                    </span>
                                </p>
                                <p class="mb-3">
                                    <strong class="text-dark">Worked:</strong> 
                                    <span id="my-shift-hours" class="text-neon-success font-weight-bold">
                                        <?php 
                                            $total_m = (int)$shift_result['total_working_minutes'];
                                            $h = floor($total_m / 60);
                                            $m = $total_m % 60;
                                            echo $h . 'h ' . $m . 'm';
                                        ?>
                                    </span>
                                </p>
                                <div id="my-shift-actions">
                                    <?php if(in_array($shift_result['status'], ['scheduled', 'paused', 'completed'])): ?>
                                        <button onclick="handleMyShiftAction(<?= $shift_result['id'] ?>, 'checkin')" class="btn btn-outline-success btn-block"><i class="fas fa-sign-in-alt mt-1"></i> Start Shift (Check In)</button>
                                    <?php elseif($shift_result['status'] == 'started'): ?>
                                        <button onclick="handleMyShiftAction(<?= $shift_result['id'] ?>, 'checkout')" class="btn btn-outline-warning btn-block"><i class="fas fa-sign-out-alt mt-1"></i> End Shift (Check Out)</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card card-dark">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#settings" data-toggle="tab">Settings</a></li>
                            <li class="nav-item"><a class="nav-link" href="#activity" data-toggle="tab">Recent Activity</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $alertType; ?>"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <div class="tab-content">
                            <!-- Settings Tab -->
                            <div class="tab-pane active" id="settings">
                        <form class="form-horizontal" method="post" action="" enctype="multipart/form-data">
                            
                            <!-- Hidden File Input triggered by clicking camera icon -->
                            <input type="file" name="profile_pic" id="profile_pic_input" style="display: none;" accept="image/*" onchange="previewImage(event)">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Full Name</label>
                                <div class="col-sm-9">
                                    <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>">
                                </div>
                            </div>
                            <hr>
                            <h5>Change Password (Optional)</h5>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">New Password</label>
                                <div class="col-sm-9">
                                    <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Confirm Password</label>
                                <div class="col-sm-9">
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="offset-sm-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </form>
                            </div>
                            <!-- /.tab-pane -->
                            
                            <!-- Activity Tab -->
                            <div class="tab-pane" id="activity">
                                <div class="timeline timeline-inverse">
                                    <?php if (empty($my_activities)): ?>
                                        <div class="alert alert-info">No recent activity found.</div>
                                    <?php else: ?>
                                        <?php 
                                        $last_date = '';
                                        foreach ($my_activities as $act): 
                                            $date = date('d M Y', strtotime($act['created_at']));
                                            if ($date != $last_date) {
                                                echo '<div class="time-label"><span class="bg-success">'.$date.'</span></div>';
                                                $last_date = $date;
                                            }
                                        ?>
                                        <div>
                                            <i class="fas fa-bolt bg-primary"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($act['created_at'])); ?></span>
                                                <h3 class="timeline-header font-weight-bold text-glow"><?php echo htmlspecialchars($act['action']); ?></h3>
                                                <?php if($act['details']): ?>
                                                <div class="timeline-body">
                                                    <?php echo htmlspecialchars($act['details']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <div>
                                            <i class="far fa-clock bg-gray"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Profile Picture Modal -->
<div class="modal fade" id="profilePicModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-dark" style="background: rgba(15, 23, 42, 0.95) !important; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1);">
            <div class="modal-header border-0">
                <h5 class="modal-title text-glow">Profile Picture</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center pb-4">
                <?php if(!empty($user['profile_pic'])): ?>
                    <img id="modal_profile_pic" src="<?php echo BASE_URL; ?>uploads/profile_pics/<?php echo $user['profile_pic']; ?>" class="img-fluid rounded" alt="Profile Picture" style="max-height: 400px; object-fit: contain; box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);">
                <?php else: ?>
                    <i id="modal_profile_icon" class="fas fa-user-circle text-muted" style="font-size: 10rem;"></i>
                    <img id="modal_profile_pic" src="" class="img-fluid rounded" style="display:none; max-height: 400px; object-fit: contain; box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);">
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-outline-info" onclick="document.getElementById('profile_pic_input').click();">
                    <i class="fas fa-edit mr-1"></i> Edit Picture
                </button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        // Update either the img or the fa-user-circle icon placeholder with the new image
        var imgElement = document.querySelector('.profile-user-img');
        if(imgElement) {
            imgElement.src = reader.result;
        } else {
            // If they had no pic before, replace the icon with an image tag
            var container = document.querySelector('.box-profile .text-center a');
            var icon = container.querySelector('i.fa-user-circle');
            if(icon) {
                var newImg = document.createElement('img');
                newImg.className = 'profile-user-img img-fluid img-circle';
                newImg.src = reader.result;
                newImg.style.width = '120px';
                newImg.style.height = '120px';
                newImg.style.objectFit = 'cover';
                icon.parentNode.replaceChild(newImg, icon);
            }
        }
        
        // Update Modal Image Context
        var modalImg = document.getElementById('modal_profile_pic');
        var modalIcon = document.getElementById('modal_profile_icon');
        if(modalImg) {
            modalImg.src = reader.result;
            modalImg.style.display = 'inline-block';
            if(modalIcon) modalIcon.style.display = 'none';
        }
    };
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>

<script>
function handleMyShiftAction(id, action) {
    $.ajax({
        url: '<?php echo BASE_URL; ?>modules/hr/ajax_shift_action.php',
        type: 'POST',
        data: { id: id, action: action },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.status === 'started') {
                    $('#my-shift-status').html('<span class="badge badge-primary">Started</span>');
                    $('#my-shift-actions').html('<button onclick="handleMyShiftAction(' + id + ', \'checkout\')" class="btn btn-outline-warning btn-block"><i class="fas fa-sign-out-alt mt-1"></i> End Shift (Check Out)</button>');
                } else if (response.status === 'paused' || response.status === 'completed') {
                    var statBadge = (response.status === 'completed') ? 'success' : 'warning';
                    var statText = (response.status === 'completed') ? 'Completed' : 'Paused';
                    $('#my-shift-status').html('<span class="badge badge-' + statBadge + '">' + statText + '</span>');
                    
                    var hrs = Math.floor(response.total_minutes / 60);
                    var mins = response.total_minutes % 60;
                    $('#my-shift-hours').text(hrs + 'h ' + mins + 'm');
                    
                    $('#my-shift-actions').html('<button onclick="handleMyShiftAction(' + id + ', \'checkin\')" class="btn btn-outline-success btn-block"><i class="fas fa-sign-in-alt mt-1"></i> Start Shift (Check In)</button>');
                }
            } else {
                alert("Error: " + response.error);
            }
        },
        error: function() {
            alert("Network error. Please try again.");
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
