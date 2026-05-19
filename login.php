<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';

// Fetch all active users to show in dropdown
$users_query = $pdo->query("SELECT u.username, u.full_name, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'active'");
$all_users = $users_query->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? AND u.status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | <?php echo SITE_NAME; ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
    
    body.login-page {
        background: #050510 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.05)"/></svg>') !important;
        background-size: 50px 50px !important;
        font-family: 'Inter', sans-serif !important;
        position: relative;
        overflow: hidden;
    }
    
    /* Neon glow effect in background */
    body.login-page::before {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 60%);
        top: -100px;
        right: -100px;
        z-index: 0;
        animation: pulse 4s infinite alternate;
    }
    
    body.login-page::after {
        content: '';
        position: absolute;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(129, 140, 248, 0.15) 0%, transparent 60%);
        bottom: -150px;
        left: -150px;
        z-index: 0;
        animation: pulse 5s infinite alternate-reverse;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.8; }
        100% { transform: scale(1.1); opacity: 1; }
    }

    .login-box { 
        position: relative;
        z-index: 1;
        width: 400px;
    }

    .card {
        background: rgba(20, 20, 35, 0.6) !important;
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 30px rgba(56, 189, 248, 0.2) !important;
        border-radius: 20px !important;
    }

    .card-header { 
        background: linear-gradient(180deg, rgba(255,255,255,0.05) 0%, transparent 100%) !important; 
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        padding-top: 2rem !important;
        border-radius: 20px 20px 0 0 !important;
    }

    .brand-text-neon {
        color: #ffffff !important;
        text-shadow: 0 0 15px rgba(56, 189, 248, 0.6), 0 0 30px rgba(56, 189, 248, 0.3);
        font-weight: 800;
        letter-spacing: 1px;
    }

    .form-control {
        background: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #e2e8f0 !important;
        border-radius: 8px 0 0 8px !important;
        padding: 1.25rem 1rem !important;
    }

    .form-control:focus {
        background: rgba(15, 23, 42, 0.9) !important;
        border-color: #38bdf8 !important;
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.3) !important;
        color: #ffffff !important;
    }

    .input-group-text {
        background: rgba(30, 41, 59, 0.6) !important;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-left: none !important;
        color: #94a3b8 !important;
        border-radius: 0 8px 8px 0 !important;
    }

    .btn-primary {
        background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%) !important;
        border: none !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
        padding: 0.75rem !important;
        transition: all 0.3s ease !important;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #0f172a !important; /* Dark text for contrast against bright button */
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.4) !important;
    }

    .login-box-msg {
        color: #94a3b8 !important;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    /* Custom Dropdown Styles */
    .user-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(56, 189, 248, 0.3);
        border-radius: 0 0 8px 8px;
        margin-top: 2px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }
    
    /* Scrollbar styling for dropdown */
    .user-dropdown::-webkit-scrollbar { width: 6px; }
    .user-dropdown::-webkit-scrollbar-track { background: transparent; }
    .user-dropdown::-webkit-scrollbar-thumb { background: rgba(56, 189, 248, 0.5); border-radius: 3px; }

    .dropdown-item-user {
        padding: 10px 15px;
        color: #e2e8f0;
        cursor: pointer;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
    }

    .dropdown-item-user:hover {
        background: rgba(56, 189, 248, 0.15);
        color: #fff;
    }
    
    .dropdown-item-user:last-child {
        border-bottom: none;
    }

    .user-role-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 4px;
        background: rgba(56, 189, 248, 0.2);
        color: #38bdf8;
    }

    label { color: #e2e8f0 !important; font-weight: 600 !important; letter-spacing: 0.5px; }
  </style>
</head>
<body class="hold-transition login-page">
<!-- Particles.js container -->
<div id="particles-js" style="position: absolute; width: 100%; height: 100%; z-index: 0;"></div>
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <div class="mb-3">
        <img src="assets/images/logo.png" alt="FunFair Logo" style="max-height: 80px; filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.3));">
      </div>
      <a href="#" class="h1"><span class="brand-text-neon">FunFair</span><span style="color: #f8fafc; font-weight: 300;">ERP</span></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>

      <form action="" method="post" id="loginForm">
        <div class="input-group mb-3" style="position: relative;">
          <input type="text" name="username" id="usernameInput" class="form-control" placeholder="Username" autocomplete="off" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
          <!-- Dropdown List -->
          <div id="userDropdown" class="user-dropdown">
            <?php foreach($all_users as $u): ?>
              <div class="dropdown-item-user" data-username="<?php echo htmlspecialchars($u['username']); ?>" data-password="<?php echo ($u['role_name'] == 'Super Admin') ? 'admin123' : 'operator123'; ?>">
                  <div>
                      <strong><?php echo htmlspecialchars($u['username']); ?></strong><br>
                      <small class="text-muted"><?php echo htmlspecialchars($u['full_name']); ?></small>
                  </div>
                  <span class="user-role-badge"><?php echo htmlspecialchars($u['role_name']); ?></span>
              </div>
            <?php endforeach; ?>
            <?php if(empty($all_users)): ?>
                <div class="dropdown-item-user text-muted" style="justify-content:center; pointer-events:none;">No active users found</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">Remember Me</label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

      <p class="mb-1 mt-3 text-center">
        <small>Default: admin / admin123</small>
      </p>
      <p class="mb-0 text-center mt-2">
        <a href="register.php" class="text-info" style="font-weight: 500;">Register a new membership</a>
      </p>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Particles.js -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
      "color": { "value": ["#38bdf8", "#818cf8", "#f8fafc"] },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.5, "random": true },
      "size": { "value": 3, "random": true },
      "line_linked": { "enable": true, "distance": 150, "color": "#38bdf8", "opacity": 0.3, "width": 1 },
      "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
    },
    "interactivity": {
      "detect_on": "canvas",
      "events": {
        "onhover": { "enable": true, "mode": "grab" },
        "onclick": { "enable": true, "mode": "push" },
        "resize": true
      },
      "modes": {
        "grab": { "distance": 140, "line_linked": { "opacity": 1 } },
        "push": { "particles_nb": 4 }
      }
    },
    "retina_detect": true
  });

  // Dropdown Logic
  $(document).ready(function() {
      const $input = $('#usernameInput');
      const $dropdown = $('#userDropdown');
      const $items = $('.dropdown-item-user');
      const $password = $('#passwordInput');

      // Show dropdown on click/focus
      $input.on('focus click', function(e) {
          e.stopPropagation();
          $dropdown.slideDown(200);
          filterDropdown(); // Filter based on current text if any
      });

      // Filter logic on typing
      $input.on('input', function() {
          filterDropdown();
      });

      function filterDropdown() {
          const val = $input.val().toLowerCase();
          let hasVisible = false;
          
          $items.each(function() {
              if ($(this).data('username')) {
                 const username = $(this).data('username').toLowerCase();
                 const fullname = $(this).find('small').text().toLowerCase();
                 if (username.includes(val) || fullname.includes(val)) {
                     $(this).show();
                     hasVisible = true;
                 } else {
                     $(this).hide();
                 }
              }
          });
      }

      // Handle item selection
      $items.on('click', function(e) {
          e.stopPropagation(); // prevent document click
          if($(this).data('username')) {
             $input.val($(this).data('username'));
             
             // Autofill password based on our common demo passwords (remove in real prod env)
             const pass = $(this).data('password');
             if(pass) {
                 $password.val(pass);
             } else {
                 $password.val('operator123'); // fallback common demo password
             }
             
             $dropdown.slideUp(200);
          }
      });

      // Close dropdown when clicking outside
      $(document).on('click', function(e) {
          if (!$(e.target).closest('.input-group').length) {
              $dropdown.slideUp(200);
          }
      });
  });
</script>
</body>
</html>
