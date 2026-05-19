<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $userType = $_POST['user_type'] ?? '';

    if (empty($fullName) || empty($username) || empty($password) || empty($confirmPassword) || empty($userType)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif ($userType !== 'Student' && $userType !== 'Visitor') {
        $error = "Invalid account type selected.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR (email = ? AND email != '')");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            $roleName = ($userType === 'Student') ? 'User/Student' : 'Visitor';
            
            // Get role_id
            $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
            $roleStmt->execute([$roleName]);
            $role = $roleStmt->fetch();
            
            if ($role) {
                $roleId = $role['id'];
            } else {
                $insertRole = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
                $insertRole->execute([$roleName]);
                $roleId = $pdo->lastInsertId();
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($insertStmt->execute([$username, $hashedPassword, $fullName, $email, $roleId])) {
                $success = "Registration successful. You can now <a href='login.php' class='text-info font-weight-bold'>login here</a>.";
            } else {
                $error = "An error occurred during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | <?php echo SITE_NAME; ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
    
    body.register-page {
        background: #050510 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.05)"/></svg>') !important;
        background-size: 50px 50px !important;
        font-family: 'Inter', sans-serif !important;
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Neon glow effect in background */
    body.register-page::before {
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
    
    body.register-page::after {
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

    .register-box { 
        position: relative;
        z-index: 1;
        width: 450px;
        margin: 20px;
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
        color: #0f172a !important;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(56, 189, 248, 0.4) !important;
    }

    .register-box-msg {
        color: #94a3b8 !important;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }
  </style>
</head>
<body class="hold-transition register-page">
<!-- Particles.js container -->
<div id="particles-js" style="position: absolute; width: 100%; height: 100%; z-index: 0;"></div>

<div class="register-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <div class="mb-3">
        <img src="assets/images/logo.png" alt="FunFair Logo" style="max-height: 80px; filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.3));" onerror="this.style.display='none'">
      </div>
      <a href="#" class="h1"><span class="brand-text-neon">FunFair</span><span style="color: #f8fafc; font-weight: 300;">ERP</span></a>
    </div>
    <div class="card-body">
      <p class="register-box-msg">Register a new membership</p>

      <?php if ($error): ?>
        <div class="alert alert-danger p-2 text-center" style="font-size: 0.9rem;"><?php echo $error; ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success p-2 text-center" style="font-size: 0.9rem;"><?php echo $success; ?></div>
      <?php else: ?>
      
      <form action="" method="post" autocomplete="off">
        <div class="input-group mb-3">
          <select name="user_type" class="form-control" style="appearance: auto;" required>
            <option value="" disabled selected>Select Account Type</option>
            <option value="Student" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Student') ? 'selected' : ''; ?>>Student</option>
            <option value="Visitor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Visitor') ? 'selected' : ''; ?>>Visitor</option>
          </select>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-users"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="text" name="full_name" class="form-control" placeholder="Full Name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="text" name="username" class="form-control" placeholder="Username" autocomplete="off" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-id-badge"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email (Optional)" autocomplete="off" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" autocomplete="new-password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="confirm_password" class="form-control" placeholder="Retype password" autocomplete="new-password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Register</button>
          </div>
        </div>
      </form>
      
      <?php endif; ?>

      <p class="mb-0 mt-3 text-center">
        <a href="login.php" class="text-center text-info" style="font-weight: 500;">I already have a membership</a>
      </p>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.register-box -->

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
</script>
</body>
</html>
