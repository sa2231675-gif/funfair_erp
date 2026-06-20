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
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR (email = ? AND email != '')");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or Email already exists.";
        } else {
            $roleName = ($userType === 'Student') ? 'User/Student' : 'Visitor';
            
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
                $success = "Registration successful. You can now <a href='login.php' style='color:#10b981;font-weight:700;'>login here</a>.";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green-light: #34d399;
      --green-main:  #10b981;
      --green-dark:  #059669;
      --dark:        #020b08;
      --card-bg:     rgba(4, 20, 15, 0.85);
      --border:      rgba(16, 185, 129, 0.2);
    }

    body, html { height: 100%; font-family: 'Inter', sans-serif; background: var(--dark); overflow-x: hidden; color: #fff; }

    .split-container { display: flex; min-height: 100vh; width: 100%; }

    /* Left Panel - Guide */
    .left-panel {
      flex: 1;
      position: relative;
      background: radial-gradient(circle at top left, #06241a 0%, var(--dark) 100%);
      padding: 4rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      border-right: 1px solid var(--border);
    }
    
    #particles-js { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }

    .guide-content { position: relative; z-index: 10; }
    .brand-title { font-family: 'Orbitron', sans-serif; font-size: 3rem; font-weight: 900; color: #fff; margin-bottom: 2rem; letter-spacing: 2px; }
    .brand-title span { color: var(--green-main); text-shadow: 0 0 15px rgba(16, 185, 129, 0.5); }
    
    .step { display: flex; align-items: flex-start; margin-bottom: 2rem; }
    .step-icon {
      width: 50px; height: 50px; min-width: 50px;
      border-radius: 12px; background: rgba(16, 185, 129, 0.15);
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; color: var(--green-main); margin-right: 1.5rem;
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
    }
    .step-text h3 { font-size: 1.2rem; margin-bottom: 5px; font-weight: 600; }
    .step-text p { color: #9ca3af; font-size: 0.95rem; line-height: 1.5; }

    /* Right Panel - Form */
    .right-panel {
      flex: 1.2;
      display: flex; align-items: center; justify-content: center;
      padding: 2rem; position: relative;
    }

    .register-card {
      width: 100%; max-width: 600px; padding: 3rem;
      background: var(--card-bg);
      backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--border); border-radius: 24px;
      box-shadow: 0 30px 60px rgba(0,0,0,0.5), inset 0 0 20px rgba(16, 185, 129, 0.05);
    }

    .register-header { margin-bottom: 2rem; text-align: center; }
    .register-header h2 { font-size: 2rem; font-weight: 700; font-family: 'Orbitron', sans-serif; }
    .register-header p { color: #9ca3af; margin-top: 10px; }

    /* Custom Radio Toggle */
    .account-type-toggle {
      display: flex; background: rgba(0,0,0,0.4); border-radius: 12px; padding: 5px; margin-bottom: 2rem;
      border: 1px solid rgba(255,255,255,0.1);
    }
    .account-type-toggle label {
      flex: 1; text-align: center; padding: 12px; cursor: pointer; border-radius: 8px;
      font-weight: 600; color: #9ca3af; transition: all 0.3s;
    }
    .account-type-toggle input { display: none; }
    .account-type-toggle input:checked + label { background: var(--green-main); color: #fff; box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3); }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .form-group.full { grid-column: span 2; }

    .input-group { position: relative; }
    .input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--green-main); }
    
    .form-control {
      width: 100%; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255,255,255,0.1);
      border-radius: 12px; padding: 1rem 1rem 1rem 3rem; color: #fff; font-size: 1rem; transition: all 0.3s;
    }
    .form-control:focus { outline: none; border-color: var(--green-main); box-shadow: 0 0 15px rgba(16, 185, 129, 0.2); background: rgba(0, 0, 0, 0.6); }

    .password-strength { height: 4px; border-radius: 2px; background: #374151; margin-top: 8px; overflow: hidden; }
    .password-strength-bar { height: 100%; width: 0%; transition: all 0.3s; }

    .btn-register {
      width: 100%; padding: 1.2rem;
      background: linear-gradient(135deg, var(--green-light), var(--green-dark));
      border: none; border-radius: 12px; color: #000; font-weight: 800; font-size: 1.1rem;
      text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: all 0.3s;
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); margin-top: 1rem;
    }
    .btn-register:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.5); }

    .login-link { text-align: center; margin-top: 2rem; color: #9ca3af; }
    .login-link a { color: var(--green-main); text-decoration: none; font-weight: 600; }

    .alert { padding: 15px; border-radius: 12px; margin-bottom: 2rem; font-weight: 500; text-align: center; }
    .alert-danger { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
    .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; }

    @media (max-width: 992px) {
      .split-container { flex-direction: column; }
      .left-panel { padding: 2rem; border-right: none; border-bottom: 1px solid var(--border); }
      .form-grid { grid-template-columns: 1fr; gap: 1rem; }
      .form-group.full { grid-column: span 1; }
      .register-card { padding: 2rem; }
    }
  </style>
</head>
<body>

<div class="split-container">
  <div class="left-panel">
    <div id="particles-js"></div>
    <div class="guide-content">
      <h1 class="brand-title">FunFair <span>ERP</span></h1>
      
      <div class="step">
        <div class="step-icon"><i class="fas fa-id-card"></i></div>
        <div class="step-text">
          <h3>Create Profile</h3>
          <p>Fill in your basic information to get started with the system.</p>
        </div>
      </div>
      
      <div class="step">
        <div class="step-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="step-text">
          <h3>Secure Access</h3>
          <p>Set up a strong password to protect your account and data.</p>
        </div>
      </div>
      
      <div class="step">
        <div class="step-icon"><i class="fas fa-rocket"></i></div>
        <div class="step-text">
          <h3>Start Exploring</h3>
          <p>Access your personalized dashboard based on your account type.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="right-panel">
    <div class="register-card">
      <div class="register-header">
        <h2>Join FunFair</h2>
        <p>Create your new membership account</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?></div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?></div>
      <?php else: ?>

      <form action="" method="post" autocomplete="off">
        <div class="account-type-toggle">
          <input type="radio" id="type_student" name="user_type" value="Student" required <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Student') ? 'checked' : ''; ?>>
          <label for="type_student"><i class="fas fa-user-graduate mr-2"></i> Student</label>
          
          <input type="radio" id="type_visitor" name="user_type" value="Visitor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'Visitor') ? 'checked' : ''; ?>>
          <label for="type_visitor"><i class="fas fa-user-tag mr-2"></i> Visitor</label>
        </div>

        <div class="form-grid">
          <div class="form-group full">
            <div class="input-group">
              <i class="fas fa-user input-icon"></i>
              <input type="text" name="full_name" class="form-control" placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <i class="fas fa-at input-icon"></i>
              <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autocomplete="off">
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <i class="fas fa-envelope input-icon"></i>
              <input type="email" name="email" class="form-control" placeholder="Email (Optional)" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
          </div>

          <div class="form-group full">
            <div class="input-group">
              <i class="fas fa-lock input-icon"></i>
              <input type="password" name="password" id="reg-password" class="form-control" placeholder="Create Password" required autocomplete="new-password">
            </div>
            <div class="password-strength">
              <div class="password-strength-bar" id="strength-bar"></div>
            </div>
          </div>

          <div class="form-group full">
            <div class="input-group">
              <i class="fas fa-check-circle input-icon"></i>
              <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required autocomplete="new-password">
            </div>
          </div>
        </div>

        <button type="submit" class="btn-register">Create Account</button>
      </form>
      
      <?php endif; ?>

      <div class="login-link">
        Already have an account? <a href="login.php">Sign in here</a>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
  // Particles JS
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
      "color": { "value": ["#10b981", "#34d399"] },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.4, "random": true },
      "size": { "value": 4, "random": true },
      "line_linked": { "enable": true, "distance": 150, "color": "#10b981", "opacity": 0.2, "width": 1 },
      "move": { "enable": true, "speed": 1, "direction": "top-right", "random": true, "out_mode": "out" }
    },
    "interactivity": {
      "detect_on": "canvas",
      "events": { "onhover": { "enable": true, "mode": "bubble" }, "resize": true },
      "modes": { "bubble": { "distance": 200, "size": 6, "duration": 2, "opacity": 0.8 } }
    },
    "retina_detect": true
  });

  // Password Strength Indicator
  $('#reg-password').on('input', function() {
      const val = $(this).val();
      const $bar = $('#strength-bar');
      let strength = 0;
      
      if (val.length > 5) strength += 25;
      if (val.match(/[A-Z]/)) strength += 25;
      if (val.match(/[0-9]/)) strength += 25;
      if (val.match(/[^a-zA-Z0-9]/)) strength += 25;
      
      $bar.css('width', strength + '%');
      
      if (strength <= 25) $bar.css('background', '#ef4444');
      else if (strength <= 50) $bar.css('background', '#f59e0b');
      else if (strength <= 75) $bar.css('background', '#3b82f6');
      else $bar.css('background', '#10b981');
  });
</script>
</body>
</html>
